<?php
namespace App\models\common;

use PDO;

/**
 * DirectMessageModel — bidirectional 1:1 chat between timekeepers and depot officers.
 * Auto-creates the direct_messages table on first use.
 */
class DirectMessageModel extends BaseModel
{
    public function __construct()
    {
        parent::__construct();
        $this->ensureTable();
    }

    private function ensureTable(): void
    {
        try {
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS direct_messages (
                    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    from_user_id INT UNSIGNED NOT NULL,
                    to_user_id   INT UNSIGNED NOT NULL,
                    message      TEXT         NOT NULL,
                    is_read      TINYINT(1)   NOT NULL DEFAULT 0,
                    is_deleted   TINYINT(1)   NOT NULL DEFAULT 0,
                    edited_at    DATETIME     NULL DEFAULT NULL,
                    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_pair_fwd  (from_user_id, to_user_id, created_at),
                    INDEX idx_pair_rev  (to_user_id, from_user_id, created_at),
                    INDEX idx_to_unread (to_user_id, is_read)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        } catch (\Throwable $e) {
            // Ignore — table may already exist
        }
        // Add columns introduced in later versions (silently ignored if already present)
        try { $this->pdo->exec("ALTER TABLE direct_messages ADD COLUMN is_deleted TINYINT(1) NOT NULL DEFAULT 0"); } catch (\Throwable $e) {}
        try { $this->pdo->exec("ALTER TABLE direct_messages ADD COLUMN edited_at DATETIME NULL DEFAULT NULL"); } catch (\Throwable $e) {}
    }

    /** Return DepotOfficer user IDs for an SLTB depot. */
    public function depotOfficerIds(int $depotId): array
    {
        if ($depotId <= 0) return [];
        try {
            $st = $this->pdo->prepare(
                "SELECT user_id FROM users
                 WHERE sltb_depot_id = ? AND role = 'DepotOfficer'
                 ORDER BY user_id ASC"
            );
            $st->execute([$depotId]);
            return array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
        } catch (\Throwable $e) { return []; }
    }

    /** Return DepotOfficer user IDs for all SLTB depots serving the given route IDs. */
    public function depotOfficerIdsForRoutes(array $routeIds): array
    {
        $ids = [];
        foreach ($this->routeDepotOptions($routeIds) as $option) {
            $ids = array_merge($ids, $option['officer_ids'] ?? []);
        }
        return array_values(array_unique(array_filter(array_map('intval', $ids), fn($id) => $id > 0)));
    }

    /**
     * Return route-linked depot chat options for a timekeeper.
     * Each entry contains depot metadata plus the officer ids that back the thread.
     */
    public function routeDepotOptions(array $routeIds, int $fallbackDepotId = 0): array
    {
        $routeIds = array_values(array_unique(array_filter(array_map('intval', $routeIds), fn($id) => $id > 0)));
        $rows = [];

        if (!empty($routeIds)) {
            try {
                $ph = implode(',', array_fill(0, count($routeIds), '?'));
                $st = $this->pdo->prepare(
                    "SELECT DISTINCT dep.sltb_depot_id AS depot_id,
                            COALESCE(sd.name, CONCAT('Depot #', dep.sltb_depot_id)) AS depot_name,
                            COALESCE(sd.code, '') AS depot_code
                     FROM (
                        SELECT DISTINCT sb.sltb_depot_id
                        FROM timetables t
                        JOIN sltb_buses sb ON sb.reg_no = t.bus_reg_no
                        WHERE t.operator_type = 'SLTB'
                          AND t.route_id IN ({$ph})
                          AND sb.sltb_depot_id > 0
                     ) dep
                     LEFT JOIN sltb_depots sd ON sd.sltb_depot_id = dep.sltb_depot_id
                     ORDER BY depot_name ASC"
                );
                $st->execute($routeIds);
                $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
            } catch (\Throwable $e) {
                $rows = [];
            }

            $rowMap = [];
            foreach ($rows as $row) {
                $depotId = (int)($row['depot_id'] ?? 0);
                if ($depotId > 0) {
                    $rowMap[$depotId] = $row;
                }
            }
            foreach ($this->sltbDepotRowsFromRouteStops($routeIds) as $row) {
                $depotId = (int)($row['depot_id'] ?? 0);
                if ($depotId > 0 && !isset($rowMap[$depotId])) {
                    $rowMap[$depotId] = $row;
                }
            }
            if (!empty($rowMap)) {
                uasort($rowMap, static function (array $a, array $b): int {
                    return strcasecmp((string)($a['depot_name'] ?? ''), (string)($b['depot_name'] ?? ''));
                });
                $rows = array_values($rowMap);
            }
        }

        if (empty($rows) && $fallbackDepotId > 0) {
            try {
                $st = $this->pdo->prepare(
                    "SELECT sd.sltb_depot_id AS depot_id,
                            COALESCE(sd.name, CONCAT('Depot #', sd.sltb_depot_id)) AS depot_name,
                            COALESCE(sd.code, '') AS depot_code
                     FROM sltb_depots sd
                     WHERE sd.sltb_depot_id = ?
                     LIMIT 1"
                );
                $st->execute([$fallbackDepotId]);
                $row = $st->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $rows = [$row];
                }
            } catch (\Throwable $e) {
                $rows = [];
            }
        }

        $options = [];
        foreach ($rows as $row) {
            $depotId = (int)($row['depot_id'] ?? 0);
            $officerIds = $this->depotOfficerIds($depotId);
            if (empty($officerIds)) {
                continue;
            }
            $options[] = [
                'depot_id' => $depotId,
                'depot_name' => (string)($row['depot_name'] ?? ('Depot #' . $depotId)),
                'depot_code' => (string)($row['depot_code'] ?? ''),
                'officer_ids' => $officerIds,
            ];
        }

        return $options;
    }

    /**
     * Return route-linked depot officers for a timekeeper as individual chat options.
     * Each entry contains officer metadata plus depot metadata for display.
     */
    public function routeDepotOfficerOptions(array $routeIds, int $fallbackDepotId = 0): array
    {
        return $this->officerOptionsFromDepotRows(
            $this->routeDepotOptions($routeIds, $fallbackDepotId),
            'route'
        );
    }

    /**
     * Resolve depot-officer chat options for a timekeeper.
     * First uses exact route-linked depots, then falls back to staffed depots
     * that best match the route area or preferred location when exact depots
     * are unstaffed.
     */
    public function usefulDepotOfficerChatOptions(array $routeIds, int $fallbackDepotId = 0, ?string $preferredLocation = null): array
    {
        $primary = $this->routeDepotOfficerOptions($routeIds, $fallbackDepotId);
        if (!empty($primary)) {
            return [
                'options' => $primary,
                'mode' => 'route',
                'message' => null,
            ];
        }

        $fallbackDepots = $this->staffedDepotFallbackRows($routeIds, $preferredLocation);
        $fallback = $this->officerOptionsFromDepotRows($fallbackDepots, 'fallback');
        if (!empty($fallback)) {
            $locationLabel = trim((string)$preferredLocation);
            if ($locationLabel !== '' && strcasecmp($locationLabel, 'Common') !== 0) {
                $locationLabel = ucwords(strtolower($locationLabel));
                $message = 'No exact route-linked depot officers were found for your current routes. Showing staffed ' . $locationLabel . '-area depot officers instead.';
            } else {
                $message = 'No exact route-linked depot officers were found for your current routes. Showing the closest staffed depot officers instead.';
            }

            return [
                'options' => $fallback,
                'mode' => 'fallback',
                'message' => $message,
            ];
        }

        if (!empty($routeIds)) {
            $message = 'No route-linked depot officers are currently available for the routes visible to you, and no useful staffed fallback depot officers could be found.';
        } else {
            $message = 'No visible routes were found for this user right now, so direct chat cannot be resolved.';
        }

        return [
            'options' => [],
            'mode' => 'none',
            'message' => $message,
        ];
    }

    /** Count unread direct messages from the given users to the current user. */
    public function unreadCountFromMultiple(int $myId, array $partnerIds): int
    {
        $partnerIds = array_values(array_unique(array_filter(array_map('intval', $partnerIds), fn($id) => $id > 0)));
        if ($myId <= 0 || empty($partnerIds)) return 0;

        try {
            $ph = implode(',', array_fill(0, count($partnerIds), '?'));
            $st = $this->pdo->prepare(
                "SELECT COUNT(*) FROM direct_messages
                 WHERE from_user_id IN ({$ph})
                   AND to_user_id = ?
                   AND is_read = 0
                   AND is_deleted = 0"
            );
            $st->execute(array_merge($partnerIds, [$myId]));
            return (int)$st->fetchColumn();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /** Return a lightweight summary for a timekeeper <-> depot conversation. */
    public function conversationSummaryWithDepot(int $userId, array $doIds): array
    {
        $lastMessage = null;
        $lastTime = null;

        $thread = $this->threadWithDepot($userId, $doIds, 1);
        if (!empty($thread)) {
            $last = $thread[count($thread) - 1];
            $lastMessage = mb_substr((string)($last['message'] ?? ''), 0, 80);
            $lastTime = $last['created_at'] ?? null;
        }

        return [
            'last_message' => $lastMessage,
            'last_time' => $lastTime,
            'unread_count' => $this->unreadCountFromMultiple($userId, $doIds),
        ];
    }

    /** Return a lightweight summary for a 1:1 direct conversation. */
    public function conversationSummaryWithUser(int $userId, int $partnerId): array
    {
        $lastMessage = null;
        $lastTime = null;

        $thread = $this->threadBetween($userId, $partnerId, 1);
        if (!empty($thread)) {
            $last = $thread[count($thread) - 1];
            $lastMessage = mb_substr((string)($last['message'] ?? ''), 0, 80);
            $lastTime = $last['created_at'] ?? null;
        }

        return [
            'last_message' => $lastMessage,
            'last_time' => $lastTime,
            'unread_count' => $this->unreadCountFromMultiple($userId, [$partnerId]),
        ];
    }

    private function officerOptionsFromDepotRows(array $depots, string $sourceScope = 'route'): array
    {
        $options = [];
        $seenUserIds = [];

        foreach ($depots as $depot) {
            $depotId = (int)($depot['depot_id'] ?? 0);
            if ($depotId <= 0) {
                continue;
            }

            $officerIds = array_values(array_unique(array_filter(
                array_map('intval', $depot['officer_ids'] ?? $this->depotOfficerIds($depotId)),
                fn($id) => $id > 0
            )));
            if (empty($officerIds)) {
                continue;
            }

            try {
                $ph = implode(',', array_fill(0, count($officerIds), '?'));
                $st = $this->pdo->prepare(
                    "SELECT user_id, first_name, last_name, role
                     FROM users
                     WHERE user_id IN ({$ph})
                     ORDER BY first_name ASC, last_name ASC, user_id ASC"
                );
                $st->execute($officerIds);
                $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
            } catch (\Throwable $e) {
                $rows = [];
            }

            foreach ($rows as $row) {
                $userId = (int)($row['user_id'] ?? 0);
                if ($userId <= 0 || isset($seenUserIds[$userId])) {
                    continue;
                }

                $seenUserIds[$userId] = true;
                $officerName = trim((string)($row['first_name'] ?? '') . ' ' . (string)($row['last_name'] ?? ''));
                if ($officerName === '') {
                    $officerName = 'Depot Officer #' . $userId;
                }

                $options[] = [
                    'user_id' => $userId,
                    'officer_name' => $officerName,
                    'role' => (string)($row['role'] ?? 'DepotOfficer'),
                    'depot_id' => $depotId,
                    'depot_name' => (string)($depot['depot_name'] ?? ('Depot #' . $depotId)),
                    'depot_code' => (string)($depot['depot_code'] ?? ''),
                    'source_scope' => $sourceScope,
                ];
            }
        }

        usort($options, static function (array $a, array $b): int {
            $cmp = strcasecmp((string)($a['depot_name'] ?? ''), (string)($b['depot_name'] ?? ''));
            if ($cmp !== 0) {
                return $cmp;
            }

            $cmp = strcasecmp((string)($a['officer_name'] ?? ''), (string)($b['officer_name'] ?? ''));
            if ($cmp !== 0) {
                return $cmp;
            }

            return ((int)($a['user_id'] ?? 0)) <=> ((int)($b['user_id'] ?? 0));
        });

        return $options;
    }

    private function staffedDepotFallbackRows(array $routeIds, ?string $preferredLocation = null, int $limit = 3): array
    {
        $preferredToken = $this->normalizeRouteText((string)$preferredLocation);
        $routeTokens = [];
        foreach ($this->routeStopRows($routeIds) as $routeRow) {
            foreach ($this->extractRouteStopNames((string)($routeRow['stops_raw'] ?? '[]')) as $stopName) {
                $token = $this->normalizeRouteText((string)$stopName);
                if ($token !== '') {
                    $routeTokens[$token] = true;
                }
            }
        }
        $routeTokens = array_keys($routeTokens);

        try {
            $rows = $this->pdo->query(
                "SELECT sd.sltb_depot_id AS depot_id,
                        COALESCE(sd.name, CONCAT('Depot #', sd.sltb_depot_id)) AS depot_name,
                        COALESCE(sd.code, '') AS depot_code
                 FROM sltb_depots sd
                 JOIN users u
                   ON u.sltb_depot_id = sd.sltb_depot_id
                  AND u.role = 'DepotOfficer'
                 GROUP BY sd.sltb_depot_id, sd.name, sd.code
                 ORDER BY depot_name ASC"
            )->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }

        $scored = [];
        foreach ($rows as $row) {
            $depotId = (int)($row['depot_id'] ?? 0);
            if ($depotId <= 0) {
                continue;
            }

            $tokens = [];
            $nameToken = $this->normalizeRouteText((string)($row['depot_name'] ?? ''));
            if ($nameToken !== '') {
                $tokens[] = $nameToken;
                if (str_ends_with($nameToken, ' depot')) {
                    $trimmed = trim(substr($nameToken, 0, -6));
                    if ($trimmed !== '') {
                        $tokens[] = $trimmed;
                    }
                }
            }

            $codeToken = $this->normalizeRouteText((string)($row['depot_code'] ?? ''));
            if ($codeToken !== '') {
                $tokens[] = $codeToken;
            }
            $tokens = array_values(array_unique(array_filter($tokens, fn($token) => $token !== '')));

            $score = 0;
            if ($preferredToken !== '' && $preferredToken !== 'common') {
                foreach ($tokens as $token) {
                    if ($token === $preferredToken) {
                        $score = max($score, 100);
                    } elseif (str_contains($token, $preferredToken) || str_contains($preferredToken, $token)) {
                        $score = max($score, 80);
                    }
                }
            }

            foreach ($routeTokens as $routeToken) {
                foreach ($tokens as $token) {
                    if ($routeToken === $token) {
                        $score = max($score, 60);
                        continue;
                    }
                    if ((strlen($token) >= 4 && str_contains($routeToken, $token)) || (strlen($routeToken) >= 4 && str_contains($token, $routeToken))) {
                        $score = max($score, 35);
                    }
                }
            }

            if ($score <= 0) {
                continue;
            }

            $row['_score'] = $score;
            $scored[] = $row;
        }

        usort($scored, static function (array $a, array $b): int {
            $scoreCmp = ((int)($b['_score'] ?? 0)) <=> ((int)($a['_score'] ?? 0));
            if ($scoreCmp !== 0) {
                return $scoreCmp;
            }

            return strcasecmp((string)($a['depot_name'] ?? ''), (string)($b['depot_name'] ?? ''));
        });

        if ($limit > 0 && count($scored) > $limit) {
            $scored = array_slice($scored, 0, $limit);
        }

        return array_map(static function (array $row): array {
            unset($row['_score']);
            return $row;
        }, $scored);
    }

    /**
     * Send a direct message. Returns the new message id or null on failure.
     */
    public function send(int $fromId, int $toId, string $message): ?int
    {
        $message = trim($message);
        if (!$message || $fromId <= 0 || $toId <= 0 || $fromId === $toId) return null;
        try {
            $st = $this->pdo->prepare(
                "INSERT INTO direct_messages (from_user_id, to_user_id, message) VALUES (?, ?, ?)"
            );
            $st->execute([$fromId, $toId, $message]);
            $id = (int)$this->pdo->lastInsertId();
            return $id > 0 ? $id : null;
        } catch (\Throwable $e) { return null; }
    }

    /**
     * Send one message from TK to all depot officers (one row per DO).
     * Returns array of inserted IDs.
     */
    public function sendToMultiple(int $fromId, array $toIds, string $message): array
    {
        $message = trim($message);
        if (!$message || $fromId <= 0 || empty($toIds)) return [];
        $ids = [];
        try {
            $st = $this->pdo->prepare(
                "INSERT INTO direct_messages (from_user_id, to_user_id, message) VALUES (?, ?, ?)"
            );
            foreach ($toIds as $toId) {
                $toId = (int)$toId;
                if ($toId <= 0 || $toId === $fromId) continue;
                $st->execute([$fromId, $toId, $message]);
                $id = (int)$this->pdo->lastInsertId();
                if ($id > 0) $ids[] = $id;
            }
        } catch (\Throwable $e) {}
        return $ids;
    }

    /**
     * Get all messages in a thread between two users, oldest first.
     * $sinceId > 0: only messages with id > sinceId.
     */
    public function threadBetween(int $userA, int $userB, int $limit = 150, int $sinceId = 0): array
    {
        if ($userA <= 0 || $userB <= 0) return [];
        $params = [$userA, $userB, $userB, $userA];
        $sql = "
            SELECT dm.id, dm.from_user_id, dm.to_user_id, dm.message, dm.is_read, dm.created_at, dm.edited_at,
                   u.first_name, u.last_name, u.role
            FROM direct_messages dm
            JOIN users u ON u.user_id = dm.from_user_id
            WHERE ((dm.from_user_id = ? AND dm.to_user_id = ?)
               OR (dm.from_user_id = ? AND dm.to_user_id = ?))
              AND dm.is_deleted = 0
        ";
        if ($sinceId > 0) { $sql .= " AND dm.id > ?"; $params[] = $sinceId; }
        $sql .= " ORDER BY dm.created_at ASC, dm.id ASC LIMIT " . min(200, max(1, $limit));
        try {
            $st = $this->pdo->prepare($sql);
            $st->execute($params);
            return $st->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) { return []; }
    }

    /**
     * Get all messages between a TK and any of the depot officer user IDs.
     * De-duplicates TK→DO messages that were broadcast (same text, same second).
     * NOTE: sinceId filtering is applied AFTER dedup so duplicate copies with
     * higher IDs never sneak through on subsequent polls.
     */
    public function threadWithDepot(int $tkId, array $doIds, int $limit = 150, int $sinceId = 0): array
    {
        $doIds = array_values(array_unique(array_filter(array_map('intval', $doIds), fn($id) => $id > 0)));
        if ($tkId <= 0 || empty($doIds)) return [];
        $ph = implode(',', array_fill(0, count($doIds), '?'));
        $params = array_merge([$tkId], $doIds, [$tkId], $doIds);
        // Fetch the full thread without sinceId — dedup must see all copies
        $sql = "
            SELECT dm.id, dm.from_user_id, dm.to_user_id, dm.message, dm.is_read, dm.created_at, dm.edited_at,
                   u.first_name, u.last_name, u.role
            FROM direct_messages dm
            JOIN users u ON u.user_id = dm.from_user_id
            WHERE ((dm.from_user_id = ? AND dm.to_user_id IN ({$ph}))
               OR (dm.to_user_id = ? AND dm.from_user_id IN ({$ph})))
              AND dm.is_deleted = 0
            ORDER BY dm.created_at ASC, dm.id ASC
        ";
        try {
            $st = $this->pdo->prepare($sql);
            $st->execute($params);
            $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) { return []; }

        // De-duplicate: same sender + same text + same timestamp → keep only first row
        // This covers both TK's broadcast copies (TK→multiple DOs) and any duplicate DO replies
        $seen = [];
        $deduped = [];
        foreach ($rows as $row) {
            $key = $row['from_user_id'] . '|' . $row['message'] . '|' . $row['created_at'];
            if (isset($seen[$key])) continue;
            $seen[$key] = true;
            $deduped[] = $row;
        }

        // Apply sinceId AFTER dedup so duplicate copies with higher IDs don't slip through
        if ($sinceId > 0) {
            $deduped = array_values(array_filter($deduped, fn($r) => (int)$r['id'] > $sinceId));
        }

        // Apply limit (keep most recent)
        if (count($deduped) > $limit) {
            $deduped = array_slice($deduped, -$limit);
        }

        return $deduped;
    }

    /**
     * Get all conversation partners for $userId, with last message preview & unread count.
     * Optionally restrict partners to certain roles.
     */
    public function conversationsForUser(int $userId, array $partnerRoles = []): array
    {
        if ($userId <= 0) return [];
        try {
            // Distinct partners
            $st = $this->pdo->prepare("
                SELECT DISTINCT
                    CASE WHEN dm.from_user_id = ? THEN dm.to_user_id ELSE dm.from_user_id END AS partner_id
                FROM direct_messages dm
                WHERE dm.from_user_id = ? OR dm.to_user_id = ?
            ");
            $st->execute([$userId, $userId, $userId]);
            $partnerIds = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
            if (empty($partnerIds)) return [];

            $results = [];
            foreach ($partnerIds as $pid) {
                $stU = $this->pdo->prepare(
                    "SELECT user_id, first_name, last_name, role FROM users WHERE user_id = ? LIMIT 1"
                );
                $stU->execute([$pid]);
                $u = $stU->fetch(PDO::FETCH_ASSOC);
                if (!$u) continue;
                if (!empty($partnerRoles) && !in_array($u['role'] ?? '', $partnerRoles, true)) continue;

                $stL = $this->pdo->prepare("
                    SELECT message, created_at FROM direct_messages
                    WHERE (from_user_id = ? AND to_user_id = ?) OR (from_user_id = ? AND to_user_id = ?)
                    ORDER BY created_at DESC, id DESC LIMIT 1
                ");
                $stL->execute([$userId, $pid, $pid, $userId]);
                $last = $stL->fetch(PDO::FETCH_ASSOC);

                $stR = $this->pdo->prepare(
                    "SELECT COUNT(*) FROM direct_messages WHERE from_user_id = ? AND to_user_id = ? AND is_read = 0"
                );
                $stR->execute([$pid, $userId]);
                $unread = (int)$stR->fetchColumn();

                $results[] = [
                    'partner_id'   => $pid,
                    'first_name'   => $u['first_name'] ?? '',
                    'last_name'    => $u['last_name']  ?? '',
                    'role'         => $u['role']        ?? '',
                    'last_message' => $last ? mb_substr((string)$last['message'], 0, 80) : '',
                    'last_time'    => $last ? $last['created_at'] : null,
                    'unread_count' => $unread,
                ];
            }
            usort($results, fn($a, $b) => strcmp((string)($b['last_time'] ?? ''), (string)($a['last_time'] ?? '')));
            return $results;
        } catch (\Throwable $e) { return []; }
    }

    /** Mark all messages sent by $partnerId to $myId as read. */
    public function markReadFrom(int $myId, int $partnerId): void
    {
        if ($myId <= 0 || $partnerId <= 0) return;
        try {
            $st = $this->pdo->prepare(
                "UPDATE direct_messages SET is_read = 1
                 WHERE from_user_id = ? AND to_user_id = ? AND is_read = 0"
            );
            $st->execute([$partnerId, $myId]);
        } catch (\Throwable $e) {}
    }

    /** Mark all messages from any of $partnerIds to $myId as read. */
    public function markReadFromMultiple(int $myId, array $partnerIds): void
    {
        $partnerIds = array_filter(array_map('intval', $partnerIds), fn($id) => $id > 0);
        if ($myId <= 0 || empty($partnerIds)) return;
        try {
            $ph = implode(',', array_fill(0, count($partnerIds), '?'));
            $st = $this->pdo->prepare(
                "UPDATE direct_messages SET is_read = 1
                 WHERE from_user_id IN ({$ph}) AND to_user_id = ? AND is_read = 0"
            );
            $st->execute(array_merge($partnerIds, [$myId]));
        } catch (\Throwable $e) {}
    }

    /** Get unread direct message count for a user. */
    public function unreadCount(int $userId): int
    {
        if ($userId <= 0) return 0;
        try {
            $st = $this->pdo->prepare(
                "SELECT COUNT(*) FROM direct_messages WHERE to_user_id = ? AND is_read = 0 AND is_deleted = 0"
            );
            $st->execute([$userId]);
            return (int)$st->fetchColumn();
        } catch (\Throwable $e) { return 0; }
    }

    /**
     * Soft-delete all messages in a conversation between two users.
     * Used when a DO deletes a chat thread from their inbox.
     */
    public function deleteConversation(int $userA, int $userB): bool
    {
        if ($userA <= 0 || $userB <= 0) return false;
        try {
            $st = $this->pdo->prepare(
                "UPDATE direct_messages SET is_deleted = 1
                 WHERE (from_user_id = ? AND to_user_id = ?)
                    OR (from_user_id = ? AND to_user_id = ?)"
            );
            $st->execute([$userA, $userB, $userB, $userA]);
            return true;
        } catch (\Throwable $e) { return false; }
    }

    /** Edit a single direct message (1:1 chat, DO↔TK). Returns true if updated. */
    public function editMessage(int $id, int $fromUserId, string $newText): bool
    {
        $newText = trim($newText);
        if (!$newText || $id <= 0 || $fromUserId <= 0) return false;
        try {
            $st = $this->pdo->prepare(
                "UPDATE direct_messages SET message = ?, edited_at = NOW()
                 WHERE id = ? AND from_user_id = ? AND is_deleted = 0"
            );
            $st->execute([$newText, $id, $fromUserId]);
            return $st->rowCount() > 0;
        } catch (\Throwable $e) { return false; }
    }

    /** Soft-delete a single direct message (1:1 chat). Returns true if deleted. */
    public function deleteMessage(int $id, int $fromUserId): bool
    {
        if ($id <= 0 || $fromUserId <= 0) return false;
        try {
            $st = $this->pdo->prepare(
                "UPDATE direct_messages SET is_deleted = 1 WHERE id = ? AND from_user_id = ?"
            );
            $st->execute([$id, $fromUserId]);
            return $st->rowCount() > 0;
        } catch (\Throwable $e) { return false; }
    }

    /**
     * Edit a broadcast message and all its copies (TK→multiple DOs).
     * Matches all rows with the same sender, message text, and timestamp.
     */
    public function editBroadcast(int $id, int $fromUserId, string $newText): bool
    {
        $newText = trim($newText);
        if (!$newText || $id <= 0 || $fromUserId <= 0) return false;
        try {
            $st = $this->pdo->prepare(
                "SELECT message, created_at FROM direct_messages
                 WHERE id = ? AND from_user_id = ? AND is_deleted = 0 LIMIT 1"
            );
            $st->execute([$id, $fromUserId]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if (!$row) return false;
            $st2 = $this->pdo->prepare(
                "UPDATE direct_messages SET message = ?, edited_at = NOW()
                 WHERE from_user_id = ? AND message = ? AND created_at = ? AND is_deleted = 0"
            );
            $st2->execute([$newText, $fromUserId, $row['message'], $row['created_at']]);
            return $st2->rowCount() > 0;
        } catch (\Throwable $e) { return false; }
    }

    /**
     * Soft-delete a broadcast message and all its copies (TK→multiple DOs).
     * Matches all rows with the same sender, message text, and timestamp.
     */
    public function deleteBroadcast(int $id, int $fromUserId): bool
    {
        if ($id <= 0 || $fromUserId <= 0) return false;
        try {
            $st = $this->pdo->prepare(
                "SELECT message, created_at FROM direct_messages
                 WHERE id = ? AND from_user_id = ? LIMIT 1"
            );
            $st->execute([$id, $fromUserId]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if (!$row) return false;
            $st2 = $this->pdo->prepare(
                "UPDATE direct_messages SET is_deleted = 1
                 WHERE from_user_id = ? AND message = ? AND created_at = ?"
            );
            $st2->execute([$fromUserId, $row['message'], $row['created_at']]);
            return $st2->rowCount() > 0;
        } catch (\Throwable $e) { return false; }
    }
}

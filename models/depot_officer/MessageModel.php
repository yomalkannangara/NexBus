<?php
// ─────────────────────────────────────────────────────────────────────────────
// STEP 2C — models/depot_officer/MessageModel.php
//
// Replace the ENTIRE file with this.
// ─────────────────────────────────────────────────────────────────────────────
namespace App\models\depot_officer;

use App\models\common\BaseModel;
use PDO;

class MessageModel extends BaseModel
{
    /**
     * Send a message to one or more depot staff members.
     *
     * @param int[]  $userIds   Explicit recipient IDs (ignored when $allDepot = true)
     * @param string $priority  'normal' | 'urgent' | 'critical'
     * @param string $scope     'individual' | 'role' | 'depot'
     * @param bool   $allDepot  Fan out to every active user in the depot
     */
    public function send(
        int    $depotId,
        array  $userIds,
        string $text,
        string $priority = 'normal',
        string $scope    = 'individual',
        bool   $allDepot = false
    ): bool {
        $text = trim($text);
        if (!$text) return false;

        // ── Resolve final recipient list ──────────────────────────────────
        if ($allDepot) {
            $st = $this->pdo->prepare(
                "SELECT user_id FROM users WHERE sltb_depot_id = ? AND status = 'active'"
            );
            $st->execute([$depotId]);
            $userIds = $st->fetchAll(PDO::FETCH_COLUMN);
        } else {
            if (empty($userIds)) return false;

            // Validate IDs actually belong to this depot
            $in  = implode(',', array_fill(0, count($userIds), '?'));
            $chk = $this->pdo->prepare(
                "SELECT user_id FROM users WHERE sltb_depot_id = ? AND user_id IN ($in)"
            );
            $chk->execute(array_merge([$depotId], array_map('intval', $userIds)));
            $userIds = $chk->fetchAll(PDO::FETCH_COLUMN);
        }

        if (!$userIds) return false;

        // ── Map priority → notification type ─────────────────────────────
        $type = match ($priority) {
            'critical' => 'Alert',
            'urgent'   => 'Urgent',
            default    => 'Message',
        };

        // ── Insert one notification row per recipient ─────────────────────
        $ins = $this->pdo->prepare(
            "INSERT INTO notifications (user_id, type, message, is_seen, created_at)
             VALUES (?, ?, ?, 0, NOW())"
        );

        $this->pdo->beginTransaction();
        try {
            foreach ($userIds as $uid) {
                $ins->execute([$uid, $type, $text]);
            }
            $this->pdo->commit();
            return true;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    /**
     * Fetch recent inbox messages for a depot.
     *
     * @param string $filter  'all' | 'unread' | 'alert' | 'message'
     */
    public function recent(int $depotId, int $limit = 50, string $filter = 'all'): array
    {
        $where  = 'u.sltb_depot_id = ?';
        $params = [$depotId];

        match ($filter) {
            'unread'  => $where .= " AND n.is_seen = 0",
            'alert'   => $where .= " AND n.type IN ('Alert','Urgent','Delay','Breakdown')",
            'message' => $where .= " AND n.type = 'Message'",
            default   => null,
        };

        $sql = "SELECT
                    n.notification_id,
                    n.type,
                    n.message,
                    n.is_seen,
                    n.created_at,
                    TRIM(CONCAT(
                        COALESCE(u.first_name, ''), ' ',
                        COALESCE(u.last_name,  '')
                    )) AS full_name,
                    u.role
                FROM notifications n
                JOIN users u ON u.user_id = n.user_id
                WHERE {$where}
                  AND n.type IN ('Message','Delay','Timetable','Alert','Urgent','Breakdown')
                ORDER BY n.created_at DESC
                LIMIT " . (int)$limit;

        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Mark a single notification as read (called silently from the view).
     */
    public function markRead(int $notificationId, int $userId): void
    {
        $st = $this->pdo->prepare(
            "UPDATE notifications
             SET is_seen = 1
             WHERE notification_id = ? AND user_id = ? AND is_seen = 0"
        );
        $st->execute([$notificationId, $userId]);
    }
}
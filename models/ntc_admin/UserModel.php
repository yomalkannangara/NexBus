<?php
namespace App\models\ntc_admin;

use PDO;

class UserModel extends BaseModel {

    public function counts(): array {
        // unchanged (staff-only counts). If you later want a passenger card:
        // $passengers = (int)$this->pdo->query("SELECT COUNT(*) c FROM passengers")->fetch()['c'];
        $dm    = (int)$this->pdo->query("SELECT COUNT(*) c FROM users WHERE role='DepotManager'")->fetch()['c'];
        $admin = (int)$this->pdo->query("SELECT COUNT(*) c FROM users WHERE role='NTCAdmin'")->fetch()['c'];
        $owner = (int)$this->pdo->query("SELECT COUNT(*) c FROM users WHERE role='PrivateBusOwner'")->fetch()['c'];
        $tk    = (int)$this->pdo->query("SELECT COUNT(*) c FROM users WHERE role IN ('SLTBTimekeeper','PrivateTimekeeper')")->fetch()['c'];
        return compact('dm','admin','owner','tk');
    }

    public function list(array $filters = []): array {
        $sql = "SELECT user_id, first_name, last_name, email, phone, role, status, last_login, private_operator_id, sltb_depot_id, timekeeper_location
                FROM users";
        $where = [];
        $params = [];

        // Role filter
        if (!empty($filters['role'])) {
            $where[] = "role = :role";
            $params[':role'] = $filters['role'];
        }

        // Status filter
        if (!empty($filters['status'])) {
            $where[] = "status = :status";
            $params[':status'] = $filters['status'];
        }

        // Linked org filter: '', 'none', 'owner:<id>', 'depot:<id>'
        if (!empty($filters['link'])) {
            $link = (string)$filters['link'];
            if ($link === 'none') {
                $where[] = "private_operator_id IS NULL AND sltb_depot_id IS NULL";
            } elseif (str_starts_with($link, 'owner:')) {
                $id = substr($link, 6);
                if ($id !== '') {
                    $where[] = "private_operator_id = :po";
                    $params[':po'] = $id;
                }
            } elseif (str_starts_with($link, 'depot:')) {
                $id = substr($link, 6);
                if ($id !== '') {
                    $where[] = "sltb_depot_id = :dp";
                    $params[':dp'] = $id;
                }
            }
        }

        if ($where) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " ORDER BY first_name, last_name";

        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    public function owners(): array {
        return $this->pdo->query("SELECT private_operator_id, name FROM private_bus_owners ORDER BY name")->fetchAll();
    }

    public function depots(): array {
        return $this->pdo->query("SELECT sltb_depot_id, name FROM sltb_depots ORDER BY name")->fetchAll();
    }

    private function normalizeStop(string $text): string {
        $norm = strtolower(trim($text));
        if ($norm === '') return '';
        $norm = preg_replace('/[^a-z0-9 ]+/i', ' ', $norm) ?? $norm;
        $norm = preg_replace('/\s+/', ' ', $norm) ?? $norm;
        return trim($norm);
    }

    private function availableRouteStopColumns(): array {
        try {
            $st = $this->pdo->query("SHOW COLUMNS FROM routes");
            $rows = $st->fetchAll(PDO::FETCH_ASSOC);
            $cols = [];
            foreach ($rows as $r) {
                $name = strtolower((string)($r['Field'] ?? ''));
                if ($name !== '') $cols[] = $name;
            }

            $out = [];
            foreach (['stops_json', 'stops'] as $want) {
                if (in_array($want, $cols, true)) {
                    $out[] = $want;
                }
            }
            return $out;
        } catch (\Throwable $e) {
            return ['stops_json'];
        }
    }

    private function collectStopNamesFromNode(mixed $node, array &$out): void {
        if (is_string($node)) {
            $v = trim($node);
            if ($v !== '') $out[] = $v;
            return;
        }

        if (!is_array($node)) {
            return;
        }

        if (isset($node['stops'])) {
            $this->collectStopNamesFromNode($node['stops'], $out);
            return;
        }

        foreach (['stop', 'name', 'location'] as $k) {
            if (isset($node[$k]) && is_string($node[$k])) {
                $v = trim((string)$node[$k]);
                if ($v !== '') $out[] = $v;
                return;
            }
        }

        foreach ($node as $child) {
            $this->collectStopNamesFromNode($child, $out);
        }
    }

    private function extractStopsFromRaw(mixed $raw): array {
        $text = trim((string)$raw);
        if ($text === '' || strtolower($text) === 'null') {
            return [];
        }

        $attempts = [$text];

        // Common escaped payloads from SQL dumps/app writes.
        $attempts[] = stripslashes($text);
        $attempts[] = str_replace('\\"', '"', $text);

        if (
            (str_starts_with($text, '"') && str_ends_with($text, '"'))
            || (str_starts_with($text, "'") && str_ends_with($text, "'"))
        ) {
            $attempts[] = substr($text, 1, -1);
        }

        foreach ($attempts as $candidate) {
            $decoded = json_decode($candidate, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                continue;
            }

            if (is_string($decoded)) {
                $decoded2 = json_decode($decoded, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $decoded = $decoded2;
                }
            }

            $out = [];
            $this->collectStopNamesFromNode($decoded, $out);
            if (!empty($out)) {
                return $out;
            }
        }

        // Fallback for legacy delimited formats.
        if (preg_match('/,|\||->|;|>/', $text)) {
            $parts = preg_split('/\s*(?:,|\||->|;|>)\s*/', $text) ?: [];
            $parts = array_values(array_filter(array_map('trim', $parts), fn($v) => $v !== ''));
            if (!empty($parts)) {
                return $parts;
            }
        }

        return [];
    }

    public function timekeeperLocations(): array {
        $locations = ['Common'];
        $seen = ['common' => true];

        try {
            foreach ($this->availableRouteStopColumns() as $col) {
                $sql = "SELECT {$col} AS raw_stops FROM routes WHERE {$col} IS NOT NULL AND TRIM({$col}) <> ''";
                $st = $this->pdo->query($sql);
                foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $raw) {
                    foreach ($this->extractStopsFromRaw($raw) as $name) {
                        $key = $this->normalizeStop((string)$name);
                        if ($key === '' || isset($seen[$key])) continue;
                        $seen[$key] = true;
                        $locations[] = (string)$name;
                    }
                }
            }
        } catch (\Throwable $e) {
            // Keep a safe fallback list with Common if routes are unavailable.
        }

        $others = array_values(array_filter($locations, fn($v) => $this->normalizeStop((string)$v) !== 'common'));
        usort($others, fn($a, $b) => strnatcasecmp((string)$a, (string)$b));
        array_unshift($others, 'Common');
        return $others;
    }

    private function validatedTimekeeperLocation(array $d, string $role): ?string {
        if (!in_array($role, ['SLTBTimekeeper', 'PrivateTimekeeper'], true)) {
            return null;
        }

        $input = trim((string)($d['timekeeper_location'] ?? ''));
        if ($input === '') {
            return 'Common';
        }

        $want = $this->normalizeStop($input);
        if ($want === 'common') {
            return 'Common';
        }

        foreach ($this->timekeeperLocations() as $candidate) {
            if ($this->normalizeStop((string)$candidate) === $want) {
                return (string)$candidate;
            }
        }

        return 'Common';
    }

    public function create(array $d): void {
        $this->pdo->beginTransaction();
        try {
            // normalize by role (same rule used in update)
            $role = $d['role'] ?? '';

            $timekeeperLocation = $this->validatedTimekeeperLocation($d, $role);

            $depotId    = !empty($d['sltb_depot_id']) ? $d['sltb_depot_id'] : null;
            $operatorId = !empty($d['private_operator_id']) ? $d['private_operator_id'] : null;

            if ($role === 'PrivateBusOwner') {
                $depotId = null;
                $timekeeperLocation = null;
            } elseif (in_array($role, ['DepotManager','DepotOfficer'], true)) {
                $operatorId = null;
                $timekeeperLocation = null;
            } elseif (in_array($role, ['SLTBTimekeeper','PrivateTimekeeper'], true)) {
                $operatorId = null;
                $depotId = null;
            } else {
                $operatorId = null;
                $depotId = null;
                $timekeeperLocation = null;
            }

            $employeeId = (int)($d['employee_id'] ?? 0);
            if ($employeeId <= 0) {
                throw new \InvalidArgumentException('Employee ID is required');
            }

            // Compute once so both tables share the SAME hash (bcrypt is salted)
            $plainPwd = $d['password'] ?? '123456';
            $pwdHash  = password_hash($plainPwd, PASSWORD_BCRYPT);

            // Insert into users (employee id goes to user_id)
            $st = $this->pdo->prepare("
                INSERT INTO users (user_id, role, first_name, last_name, email, phone, password_hash, status, private_operator_id, sltb_depot_id, timekeeper_location)
                VALUES (?,?,?,?,?,?,?, 'Active', ?, ?, ?)
            ");
            $st->execute([
                $employeeId,
                $role,
                $d['first_name'],
                $d['last_name'],
                $d['email'] ?: null,
                $d['phone'] ?: null,
                $pwdHash,
                $operatorId,
                $depotId,
                $timekeeperLocation
            ]);

            $userId = $employeeId;

            // If passenger role → also create/attach a passengers row
            if ($role === 'Passenger') {
                $st2 = $this->pdo->prepare("
                    INSERT INTO passengers (user_id, first_name, last_name, email, phone, password_hash)
                    VALUES (?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                      first_name=VALUES(first_name),
                      last_name=VALUES(last_name),
                      email=VALUES(email),
                      phone=VALUES(phone),
                      password_hash=VALUES(password_hash)
                ");
                $st2->execute([
                    $userId,
                    $d['first_name'],
                    $d['last_name'],
                    $d['email'] ?: null,
                    $d['phone'] ?: null,
                    $pwdHash,
                ]);
            }

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function update(array $d): void {
        $this->pdo->beginTransaction();
        try {
            $userId = (int)($d['user_id'] ?? 0);
            if ($userId <= 0) {
                throw new \InvalidArgumentException('Invalid user id');
            }

            // Read current role BEFORE update (to detect flips)
            $st0 = $this->pdo->prepare("SELECT role FROM users WHERE user_id=?");
            $st0->execute([$userId]);
            $oldRole = (string)($st0->fetchColumn() ?: '');

            // Normalize linkage fields by role (same rules as create)
            $role       = $d['role'] ?? '';
            $firstName  = $d['first_name'] ?? '';
            $lastName   = $d['last_name'] ?? '';
            $email      = trim($d['email'] ?? '');
            $phone      = trim($d['phone'] ?? '');
            $timekeeperLocation = $this->validatedTimekeeperLocation($d, $role);
            $depotId    = !empty($d['sltb_depot_id']) ? $d['sltb_depot_id'] : null;
            $operatorId = !empty($d['private_operator_id']) ? $d['private_operator_id'] : null;

            if ($role === 'PrivateBusOwner') {
                $depotId = null;
                $timekeeperLocation = null;
            } elseif (in_array($role, ['DepotManager','DepotOfficer'], true)) {
                $operatorId = null;
                $timekeeperLocation = null;
            } elseif (in_array($role, ['SLTBTimekeeper','PrivateTimekeeper'], true)) {
                $operatorId = null;
                $depotId = null;
            } else {
                $operatorId = null;
                $depotId = null;
                $timekeeperLocation = null;
            }

            $sets = "role=:role, first_name=:first_name, last_name=:last_name, email=:email, phone=:phone, private_operator_id=:po, sltb_depot_id=:dp, timekeeper_location=:tk_loc";
            $args = [
                ':role'       => $role,
                ':first_name' => $firstName,
                ':last_name'  => $lastName,
                ':email'      => ($email !== '') ? $email : null,
                ':phone'      => ($phone !== '') ? $phone : null,
                ':po'         => $operatorId,
                ':dp'         => $depotId,
                ':tk_loc'     => $timekeeperLocation,
                ':user_id'    => $userId,
            ];

            // If password provided, hash once and push to both tables later
            $pwdProvided = false;
            $pwdHash = null;
            if (($d['password'] ?? '') !== '') {
                $pwdProvided = true;
                $pwdHash = password_hash($d['password'], PASSWORD_BCRYPT);
                $sets .= ", password_hash=:ph";
                $args[':ph'] = $pwdHash;
            }

            // Update users
            $sql = "UPDATE users SET $sets WHERE user_id=:user_id";
            $st  = $this->pdo->prepare($sql);
            $st->execute($args);

            // Keep passengers table in sync if role is Passenger
            if ($role === 'Passenger') {
                $sqlUpsert = "
                    INSERT INTO passengers (user_id, first_name, last_name, email, phone, password_hash)
                    SELECT u.user_id, u.first_name, u.last_name, u.email, u.phone, u.password_hash
                    FROM users u WHERE u.user_id = ?
                    ON DUPLICATE KEY UPDATE
                        first_name=VALUES(first_name),
                        last_name=VALUES(last_name),
                        email=VALUES(email),
                        phone=VALUES(phone),
                        password_hash=VALUES(password_hash)
                ";
                $st2 = $this->pdo->prepare($sqlUpsert);
                $st2->execute([$userId]);

            } elseif ($oldRole === 'Passenger' && $role !== 'Passenger') {
                // SAFER DEFAULT: do nothing (keep passengers row) because other tables
                // (complaints, favourites, etc.) likely reference passengers.passenger_id.
                // If you truly want to remove it, ensure cascades are set and then:
                // $this->pdo->prepare("DELETE FROM passengers WHERE user_id=?")->execute([$userId]);
            }

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function setStatus(int $userId, string $status): void {
        if (!in_array($status, ['Active','Suspended'], true)) {
            throw new \InvalidArgumentException('Invalid status');
        }
        $st = $this->pdo->prepare('UPDATE users SET status = :status WHERE user_id = :user_id');
        $st->execute([':status' => $status, ':user_id' => $userId]);
    }

    public function delete(int $userId): void {
        $this->pdo->beginTransaction();
        try {
            // Try hard-delete first (works only if no FK references)
            $st = $this->pdo->prepare('DELETE FROM users WHERE user_id = :user_id');
            try {
                $st->execute([':user_id' => $userId]);
                $this->pdo->commit();
                return;
            } catch (\PDOException $e) {
                // FK constraint (23000/1451) -> fallback to soft-delete
                if (($e->getCode() ?? '') !== '23000') {
                    throw $e;
                }
            }

            // Soft-delete: keep row to satisfy FKs, but disable account + anonymize PII
            $suffix = gmdate('YmdHis');
            $email  = "deleted+{$userId}@{$suffix}.invalid";

            $st2 = $this->pdo->prepare("
                UPDATE users
                SET status='Suspended',
                    first_name='Deleted',
                    last_name='User',
                    email=:email,
                    phone=NULL,
                    password_hash=NULL
                WHERE user_id=:user_id
            ");
            $st2->execute([':email' => $email, ':user_id' => $userId]);

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}

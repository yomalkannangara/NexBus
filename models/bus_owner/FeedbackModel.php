<?php
namespace App\models\bus_owner;

use PDO;
use App\models\common\BaseModel; // same base used in other modules

class FeedbackModel extends BaseModel
{
    /** Resolve current private operator id from session */
    private function operatorId(): ?int {
        $u = $_SESSION['user'] ?? null;
        return isset($u['private_operator_id']) ? (int)$u['private_operator_id'] : null;
    }
    private function hasOperator(): bool { return (bool)$this->operatorId(); }

    /** Normalize a ref like "C000123" or raw "123" to integer id */
    private function idFromRef(string $refOrId): int {
        $refOrId = trim($refOrId);
        if ($refOrId === '') return 0;
        if (ctype_digit($refOrId)) return (int)$refOrId;
        return (int) preg_replace('/\D+/', '', $refOrId); // keep digits only
    }

    /**
     * List rows for the UI table (owner-scoped).
     * Joins:
     *  - complaints c
     *  - passengers p  (passenger name)
     *  - routes r      (route label if no bus reg no)
     *  - private_buses pb (ownership check)
     */
    public function getAll(): array
    {
        $sql = "SELECT 
                    c.complaint_id,
                    c.passenger_id,
                    c.created_at AS date,
                    NULLIF(NULLIF(TRIM(c.bus_reg_no),''),'undefined') AS bus_reg_no,
                    c.category, 
                    c.description, 
                    c.status, 
                    c.reply_text,
                    /* If your passengers table uses a different column, change p.full_name below: */
                    p.full_name AS passenger,
                    r.route_no, 
                    r.name AS route_name
                FROM complaints c
                LEFT JOIN passengers     p  ON p.passenger_id = c.passenger_id
                LEFT JOIN routes         r  ON r.route_id     = c.route_id
                LEFT JOIN private_buses  pb ON pb.reg_no      = c.bus_reg_no
                WHERE c.operator_type = 'Private'";
        $params = [];

        // Owner scoping:
        // (a) complaints for their buses, OR
        // (b) route-only complaints on routes served by their buses (via timetables)
        if ($this->hasOperator()) {
            $sql .= " AND (
                        pb.private_operator_id = :op
                        OR ((c.bus_reg_no IS NULL OR c.bus_reg_no='' OR c.bus_reg_no='undefined')
                            AND c.route_id IN (
                                SELECT DISTINCT tt.route_id
                                FROM timetables tt
                                JOIN private_buses pb2 ON pb2.reg_no = tt.bus_reg_no
                                WHERE pb2.private_operator_id = :op
                            )
                        )
                    )";
            $params[':op'] = $this->operatorId();
        }

        $sql .= " ORDER BY c.created_at DESC, c.complaint_id DESC";
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function ($r) {
            $bus = $r['bus_reg_no'] ?? '';
            $routeLabel = ($r['route_no'] ?? '') !== '' 
                ? trim(($r['route_no'] ?? '').' - '.($r['route_name'] ?? ''))
                : '';
            // passenger fallback if name missing:
            $passengerLabel = trim((string)($r['passenger'] ?? ''));
            if ($passengerLabel === '' && isset($r['passenger_id'])) {
                $passengerLabel = 'Passenger #' . (int)$r['passenger_id'];
            }

            return [
                'id'           => (int)$r['complaint_id'],
                'ref_code'     => 'C'.str_pad((string)$r['complaint_id'], 6, '0', STR_PAD_LEFT),
                'date'         => $r['date'],
                'bus_or_route' => $bus !== '' ? $bus : $routeLabel,
                'passenger'    => $passengerLabel,
                'type'         => 'Complaint',
                'category'     => $r['category'] ?? '',
                'status'       => $r['status'] ?? 'Open',
                'rating'       => 0, // not in schema, kept for compatibility; UI ignores it now
                'message'      => $r['description'] ?? '',
                'response'     => $r['reply_text'] ?? '',
            ];
        }, $rows);
    }

    /** For the “Quick Response → Select Feedback ID” dropdown (owner-scoped) */
    public function getAllIds(): array
    {
        $sql = "SELECT c.complaint_id
                FROM complaints c
                LEFT JOIN private_buses pb ON pb.reg_no = c.bus_reg_no
                WHERE c.operator_type='Private'";
        $params = [];

        if ($this->hasOperator()) {
            $sql .= " AND (
                        pb.private_operator_id = :op
                        OR ((c.bus_reg_no IS NULL OR c.bus_reg_no='' OR c.bus_reg_no='undefined')
                            AND c.route_id IN (
                                SELECT DISTINCT tt.route_id
                                FROM timetables tt
                                JOIN private_buses pb2 ON pb2.reg_no = tt.bus_reg_no
                                WHERE pb2.private_operator_id = :op
                            )
                        )
                    )";
            $params[':op'] = $this->operatorId();
        }

        $sql .= " ORDER BY c.complaint_id DESC";
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        $ids = $st->fetchAll(PDO::FETCH_COLUMN);

        return array_map(fn($id) => ['ref_code' => 'C'.str_pad((string)$id, 6, '0', STR_PAD_LEFT)], $ids);
    }

    /** Owner-scoped status change */
    public function updateStatus(string $refOrId, string $status): bool
    {
        $id = $this->idFromRef($refOrId);
        if ($id <= 0) return false;

        $sql = "UPDATE complaints c
                LEFT JOIN private_buses pb ON pb.reg_no = c.bus_reg_no
                LEFT JOIN timetables tt     ON tt.route_id = c.route_id
                LEFT JOIN private_buses pb2 ON pb2.reg_no = tt.bus_reg_no
                   SET c.status = :s
                 WHERE c.complaint_id = :id
                   AND c.operator_type = 'Private'";
        $params = [':s' => $status, ':id' => $id];

        if ($this->hasOperator()) {
            $sql .= " AND (
                        pb.private_operator_id = :op
                        OR ((c.bus_reg_no IS NULL OR c.bus_reg_no='' OR c.bus_reg_no='undefined')
                            AND pb2.private_operator_id = :op)
                    )";
            $params[':op'] = $this->operatorId();
        }

        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return $st->rowCount() > 0;
    }

    /** Owner-scoped reply (also stamps resolved_at when response is non-empty) */
    public function sendResponse(string $refOrId, string $response): bool
    {
        $id = $this->idFromRef($refOrId);
        if ($id <= 0) return false;

        $sql = "UPDATE complaints c
                LEFT JOIN private_buses pb ON pb.reg_no = c.bus_reg_no
                LEFT JOIN timetables tt     ON tt.route_id = c.route_id
                LEFT JOIN private_buses pb2 ON pb2.reg_no = tt.bus_reg_no
                   SET c.reply_text = :r,
                       c.resolved_at = CASE WHEN :r2 <> '' THEN NOW() ELSE c.resolved_at END
                 WHERE c.complaint_id = :id
                   AND c.operator_type = 'Private'";
        $params = [':r' => $response, ':r2' => $response, ':id' => $id];

        if ($this->hasOperator()) {
            $sql .= " AND (
                        pb.private_operator_id = :op
                        OR ((c.bus_reg_no IS NULL OR c.bus_reg_no='' OR c.bus_reg_no='undefined')
                            AND pb2.private_operator_id = :op)
                    )";
            $params[':op'] = $this->operatorId();
        }

        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return $st->rowCount() > 0;
    }
}

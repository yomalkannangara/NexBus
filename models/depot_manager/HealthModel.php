<?php
namespace App\models\depot_manager;

use PDO;
use PDOException;
abstract class BaseModel {
    protected PDO $pdo;
    public function __construct() {
        $this->pdo = $GLOBALS['db'];   
    }
}
class HealthModel extends BaseModel
{
    /* ===== Metrics for cards (no icons) ===== */
    public function metrics(): array
    {
        $scheduled  = $this->countSafe("SELECT COUNT(*) c FROM maintenance_jobs WHERE status='Scheduled'");
        $ongoing    = $this->countSafe("SELECT COUNT(*) c FROM maintenance_jobs WHERE status='Ongoing'");
        $completed  = $this->countSafe("SELECT COUNT(*) c FROM maintenance_jobs WHERE status='Completed' AND DATE(updated_at)=CURDATE()");
        $breakdowns = $this->countSafe("SELECT COUNT(*) c FROM maintenance_jobs WHERE status='Breakdown' AND DATE(created_at)=CURDATE()");

        // Return exactly what the view expects: label, value, accent class
        return [
            ['label' => 'Scheduled',  'value' => (string)$scheduled,  'accent' => 'accent-yellow'],
            ['label' => 'Ongoing',    'value' => (string)$ongoing,    'accent' => 'accent-blue'],
            ['label' => 'Completed (Today)', 'value' => (string)$completed, 'accent' => 'accent-green'],
            ['label' => 'Breakdowns (Today)', 'value' => (string)$breakdowns, 'accent' => 'accent-red'],
        ];
    }

    /* ===== Ongoing list ===== */
    public function ongoing(): array
    {
        try {
            $sql = "SELECT j.id, j.bus_id, j.job_type, j.priority, j.status,
                           j.created_at, j.updated_at, j.eta_date, j.progress_pct,
                           j.workshop, j.vendor,
                           b.reg_no, b.next_service
                    FROM maintenance_jobs j
                    LEFT JOIN buses b ON b.id = j.bus_id
                    WHERE j.status IN ('Scheduled','Ongoing','Breakdown')
                    ORDER BY j.created_at DESC
                    LIMIT 100";
            $rows = $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $out = [];
            foreach ($rows as $r) {
                $bus      = $r['reg_no'] ?: ('BUS#'.$r['bus_id']);
                $task     = $r['job_type'] ?: 'General Service';
                $start    = $this->fmtDate($r['created_at']);
                $workshop = $r['workshop'] ?: ($r['vendor'] ?: 'Depot Workshop');

                // ETA: prefer j.eta_date, else created_at + 2 days (simple heuristic)
                $etaTs = $r['eta_date'] ?: (isset($r['created_at']) ? date('Y-m-d', strtotime($r['created_at'].' +2 days')) : null);
                $eta   = $this->fmtDate($etaTs);

                // Progress: prefer progress_pct, else infer from status
                $progress = is_numeric($r['progress_pct']) ? (int)$r['progress_pct'] : $this->inferProgress($r['status'] ?? '');

                $out[] = [
                    'bus'      => $bus,
                    'task'     => $task,
                    'start'    => $start,
                    'workshop' => $workshop,
                    'eta'      => $eta,
                    'progress' => $progress,
                ];
            }
            return $out;
        } catch (PDOException $e) {
            return [];
        }
    }

    /* ===== Completed list ===== */
    public function completed(): array
    {
        try {
            $sql = "SELECT j.id, j.bus_id, j.job_type, j.completed_at, j.cost, j.vendor, j.next_service_date,
                           b.reg_no, b.next_service
                    FROM maintenance_jobs j
                    LEFT JOIN buses b ON b.id = j.bus_id
                    WHERE j.status='Completed'
                    ORDER BY j.completed_at DESC
                    LIMIT 100";
            $rows = $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $out = [];
            foreach ($rows as $r) {
                $bus   = $r['reg_no'] ?: ('BUS#'.$r['bus_id']);
                $task  = $r['job_type'] ?: 'General Service';
                $date  = $this->fmtDate($r['completed_at']);
                $vendor= $r['vendor'] ?: 'Depot Workshop';
                $cost  = isset($r['cost']) && $r['cost'] !== null ? ('Cost: Rs. '.number_format((float)$r['cost'])) : '';

                // Next service: prefer explicit next_service_date or bus.next_service; else +30 days
                $nextDt = $r['next_service_date'] ?: ($r['next_service'] ?: (isset($r['completed_at']) ? date('Y-m-d', strtotime($r['completed_at'].' +30 days')) : null));
                $next   = $this->fmtDate($nextDt);

                $out[] = [
                    'bus'    => $bus,
                    'task'   => $task,
                    'date'   => $date,
                    'vendor' => $vendor,
                    'cost'   => $cost,
                    'next'   => $next,
                ];
            }
            return $out;
        } catch (PDOException $e) {
            return [];
        }
    }

    /* ===== Helpers ===== */
    private function countSafe(string $sql, array $params = []): int
    {
        try {
            $st = $this->pdo->prepare($sql);
            $st->execute($params);
            return (int)($st->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);
        } catch (PDOException $e) {
            return 0;
        }
    }

    private function fmtDate(?string $s): string
    {
        if (!$s) return '—';
        $ts = strtotime($s);
        return $ts ? date('Y-m-d', $ts) : '—';
        // If you want a long form: return $ts ? date('D, j M Y', $ts) : '—';
    }

    private function inferProgress(string $status): int
    {
        switch (strtolower($status)) {
            case 'scheduled': return 10;
            case 'breakdown': return 30;
            case 'ongoing':   return 60;
            case 'completed': return 100;
            default:          return 40;
        }
    }
}

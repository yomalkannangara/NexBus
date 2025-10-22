<?php
namespace App\models\depot_officer;

use App\models\common\BaseModel;

class DashboardModel extends BaseModel
{
    /** Dummy-data: disable DB */
    public function __construct() {}

    /** Cards: delayed / breakdowns today + open/in-progress complaints */
    public function counts(int $depotId): array
    {
        $today = date('Y-m-d');

        $tracking = array_filter($this->seedTracking(), function ($r) use ($depotId, $today) {
            return (int)$r['sltb_depot_id'] === (int)$depotId
                && substr($r['snapshot_at'], 0, 10) === $today;
        });

        $delayed = 0;
        $breaks  = 0;
        foreach ($tracking as $r) {
            if (($r['operational_status'] ?? '') === 'Delayed')   $delayed++;
            if (($r['operational_status'] ?? '') === 'Breakdown') $breaks++;
        }

        $complaints = array_filter($this->seedComplaints(), function ($c) use ($depotId) {
            return (int)$c['sltb_depot_id'] === (int)$depotId
                && in_array($c['status'], ['Open','In Progress'], true);
        });

        $compl = count($complaints);

        return compact('delayed','breaks','compl');
    }

    /** Latest delayed records today (limit 20), includes route_no like your JOIN did */
    public function delayedToday(int $depotId): array
    {
        $today = date('Y-m-d');

        $rows = array_values(array_filter($this->seedTracking(), function ($r) use ($depotId, $today) {
            return (int)$r['sltb_depot_id'] === (int)$depotId
                && substr($r['snapshot_at'], 0, 10) === $today
                && ($r['operational_status'] ?? '') === 'Delayed';
        }));

        usort($rows, function ($a, $b) {
            return strcmp($b['snapshot_at'], $a['snapshot_at']); // desc
        });

        return array_slice($rows, 0, 20);
    }

    /** Open complaints (default top 5 by created_at desc) */
    public function openComplaints(int $depotId, int $limit = 5): array
    {
        $rows = array_values(array_filter($this->seedComplaints(), function ($c) use ($depotId) {
            return (int)$c['sltb_depot_id'] === (int)$depotId
                && $c['status'] === 'Open';
        }));

        // Always ensure 'category' exists
        $rows = array_map([$this,'withCategory'], $rows);

        usort($rows, function ($a, $b) {
            return strcmp($b['created_at'], $a['created_at']); // desc
        });

        return array_slice($rows, 0, max(1, (int)$limit));
    }

    /* ==================== Dummy datasets ==================== */

    /** Fake live tracking feed (what tracking_monitoring JOIN routes would have returned) */
    private function seedTracking(): array
    {
        $today = date('Y-m-d');
        $yday  = date('Y-m-d', strtotime('-1 day'));

        return [
            // Depot 1 - today
            ['snapshot_at'=>"$today 08:10:05",'bus_reg_no'=>'NA-1234','sltb_depot_id'=>1,'route_no'=>'138','route_id'=>1,'operational_status'=>'Delayed',   'avg_delay_min'=>5,'speed'=>32,'heading'=>110],
            ['snapshot_at'=>"$today 08:08:12",'bus_reg_no'=>'NC-4567','sltb_depot_id'=>1,'route_no'=>'100','route_id'=>2,'operational_status'=>'OnTime',    'avg_delay_min'=>0,'speed'=>41,'heading'=>90],
            ['snapshot_at'=>"$today 07:55:30",'bus_reg_no'=>'NB-2222','sltb_depot_id'=>1,'route_no'=>'199','route_id'=>3,'operational_status'=>'Breakdown','avg_delay_min'=>0,'speed'=>0, 'heading'=>0],
            ['snapshot_at'=>"$today 09:02:44",'bus_reg_no'=>'ND-8901','sltb_depot_id'=>1,'route_no'=>'100','route_id'=>2,'operational_status'=>'Delayed',   'avg_delay_min'=>3,'speed'=>27,'heading'=>140],
            // Depot 1 - yesterday
            ['snapshot_at'=>"$yday  10:12:00",'bus_reg_no'=>'NA-1234','sltb_depot_id'=>1,'route_no'=>'138','route_id'=>1,'operational_status'=>'OnTime',   'avg_delay_min'=>0,'speed'=>36,'heading'=>70],
            // Depot 2 - today
            ['snapshot_at'=>"$today 07:40:00",'bus_reg_no'=>'QE-1111','sltb_depot_id'=>2,'route_no'=>'17', 'route_id'=>4,'operational_status'=>'Delayed',   'avg_delay_min'=>2,'speed'=>30,'heading'=>200],
            ['snapshot_at'=>"$today 08:25:19",'bus_reg_no'=>'QF-2222','sltb_depot_id'=>2,'route_no'=>'17', 'route_id'=>4,'operational_status'=>'OnTime',    'avg_delay_min'=>0,'speed'=>38,'heading'=>215],
        ];
    }

    /** Fake complaints table (now WITH category) */
    private function seedComplaints(): array
    {
        $now   = date('Y-m-d H:i:s');
        $earli = date('Y-m-d H:i:s', strtotime('-2 hours'));
        $yday  = date('Y-m-d H:i:s', strtotime('-1 day'));

        return [
            ['complaint_id'=>1,'sltb_depot_id'=>1,'bus_reg_no'=>'NA-1234','title'=>'Late departure','description'=>'Bus left 7 minutes late','status'=>'Open','category'=>'Delay','created_at'=>$earli],
            ['complaint_id'=>2,'sltb_depot_id'=>1,'bus_reg_no'=>'NB-2222','title'=>'Breakdown issue','description'=>'Bus stalled near stop','status'=>'In Progress','category'=>'Breakdown','created_at'=>$yday],
            ['complaint_id'=>3,'sltb_depot_id'=>1,'bus_reg_no'=>'NC-4567','title'=>'Rude staff','description'=>'Conductor behavior','status'=>'Closed','category'=>'Staff','created_at'=>$now],
            ['complaint_id'=>4,'sltb_depot_id'=>2,'bus_reg_no'=>'QE-1111','title'=>'Overcrowded','description'=>'Peak hour overload','status'=>'Open','category'=>'Capacity','created_at'=>$now],
        ];
    }

    /* ==================== Utility ==================== */

    private function withCategory(array $c): array
    {
        if (!isset($c['category']) || $c['category'] === '' ) {
            $c['category'] = $this->inferCategory($c['title'] ?? '', $c['status'] ?? '');
        }
        return $c;
    }

    private function inferCategory(string $title, string $status): string
    {
        $t = strtolower($title);
        if (str_contains($t, 'late') || str_contains($t, 'delay')) return 'Delay';
        if (str_contains($t, 'breakdown') || $status === 'In Progress') return 'Breakdown';
        if (str_contains($t, 'crowd') || str_contains($t, 'over')) return 'Capacity';
        if (str_contains($t, 'rude') || str_contains($t, 'staff')) return 'Staff';
        return 'General';
    }
}

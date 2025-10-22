<?php
namespace App\models\depot_officer;

use App\models\common\BaseModel;

class ReportModel extends BaseModel
{
    /** Disable DB constructor */
    public function __construct() {}

    /** Public: KPI summary (keeps your signature) */
    public function kpis(int $depotId, string $from, string $to): array
    {
        $rows = $this->filterTrips($this->seedTrips(), $depotId, $from, $to);

        $delayed = 0;
        $breakdowns = 0;
        $delaySum = 0;
        $delayCount = 0;

        foreach ($rows as $r) {
            $delay = $this->delayMinutes($r);
            if ($delay > 0) { $delayed++; $delaySum += $delay; $delayCount++; }
            if (($r['status'] ?? '') === 'Cancelled') $breakdowns++;
        }

        return [
            'delayed'         => $delayed,
            'breakdowns'      => $breakdowns,
            'avgDelayMin'     => $delayCount ? ($delaySum / $delayCount) : 0.0,
            'speedViolations' => 0, // dummy: set if you want
            'trips'           => count($rows),
        ];
    }

    /** Public: CSV export (keeps your signature) */
    public function csv(int $depotId, string $from, string $to): string
    {
        $rows = $this->filterTrips($this->seedTrips(), $depotId, $from, $to);

        $out = fopen('php://temp','r+');
        fputcsv($out, ['trip_date','bus_reg_no','route_no','status','delay_min','departure_time','arrival_time']);
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['trip_date'],
                $r['bus_reg_no'],
                $r['route_no'],
                $r['status'],
                $this->delayMinutes($r),
                $r['departure_time'] ?? '',
                $r['arrival_time'] ?? ''
            ]);
        }
        rewind($out);
        return stream_get_contents($out);
    }

    /* ----------------- Helpers (dummy data + calc) ----------------- */

    /** Hard-coded demo dataset (add/edit freely) */
    private function seedTrips(): array
    {
        $today = date('Y-m-d');
        $yday  = date('Y-m-d', strtotime('-1 day'));

        // Fields used: sltb_depot_id, trip_date, route_no, bus_reg_no,
        //              turn_no, scheduled_departure_time, departure_time, arrival_time, status
        return [
            // Depot 1 – today
            ['sltb_depot_id'=>1,'trip_date'=>$today,'route_no'=>'138','bus_reg_no'=>'NA-1234','turn_no'=>1,'scheduled_departure_time'=>'07:30:00','departure_time'=>'07:35:00','arrival_time'=>'08:45:00','status'=>'Completed'],
            ['sltb_depot_id'=>1,'trip_date'=>$today,'route_no'=>'138','bus_reg_no'=>'NA-1234','turn_no'=>2,'scheduled_departure_time'=>'09:00:00','departure_time'=>'09:02:00','arrival_time'=>null,'status'=>'InProgress'],
            ['sltb_depot_id'=>1,'trip_date'=>$today,'route_no'=>'100','bus_reg_no'=>'NC-4567','turn_no'=>1,'scheduled_departure_time'=>'08:00:00','departure_time'=>'07:57:00','arrival_time'=>null,'status'=>'InProgress'],
            ['sltb_depot_id'=>1,'trip_date'=>$today,'route_no'=>'100','bus_reg_no'=>'ND-8901','turn_no'=>1,'scheduled_departure_time'=>'10:00:00','departure_time'=>null,'arrival_time'=>null,'status'=>'Planned'],
            ['sltb_depot_id'=>1,'trip_date'=>$today,'route_no'=>'199','bus_reg_no'=>'NB-2222','turn_no'=>1,'scheduled_departure_time'=>'06:30:00','departure_time'=>'06:55:00','arrival_time'=>null,'status'=>'Cancelled'],

            // Depot 1 – yesterday
            ['sltb_depot_id'=>1,'trip_date'=>$yday ,'route_no'=>'138','bus_reg_no'=>'NA-1234','turn_no'=>1,'scheduled_departure_time'=>'07:30:00','departure_time'=>'07:28:00','arrival_time'=>'08:35:00','status'=>'Completed'],
            ['sltb_depot_id'=>1,'trip_date'=>$yday ,'route_no'=>'100','bus_reg_no'=>'NC-4567','turn_no'=>1,'scheduled_departure_time'=>'08:00:00','departure_time'=>'08:10:00','arrival_time'=>'09:05:00','status'=>'Completed'],

            // Depot 2 – today
            ['sltb_depot_id'=>2,'trip_date'=>$today,'route_no'=>'17','bus_reg_no'=>'QE-1111','turn_no'=>1,'scheduled_departure_time'=>'07:00:00','departure_time'=>'07:03:00','arrival_time'=>null,'status'=>'InProgress'],
            ['sltb_depot_id'=>2,'trip_date'=>$today,'route_no'=>'17','bus_reg_no'=>'QE-1111','turn_no'=>2,'scheduled_departure_time'=>'09:00:00','departure_time'=>null,'arrival_time'=>null,'status'=>'Planned'],
        ];
    }

    /** Filter by depot + date range (inclusive) */
    private function filterTrips(array $rows, int $depotId, string $from, string $to): array
    {
        $fromTs = strtotime($from.' 00:00:00');
        $toTs   = strtotime($to.' 23:59:59');

        return array_values(array_filter($rows, function($r) use ($depotId,$fromTs,$toTs){
            if ((int)$r['sltb_depot_id'] !== (int)$depotId) return false;
            $t = strtotime(($r['trip_date'] ?? date('Y-m-d')).' 12:00:00');
            return ($t >= $fromTs && $t <= $toTs);
        }));
    }

    /** Positive delay minutes; 0 for early/on-time/missing */
    private function delayMinutes(array $r): int
    {
        $sd = $r['scheduled_departure_time'] ?? null;
        $ad = $r['departure_time'] ?? null;
        if (!$sd || !$ad) return 0;
        $t0 = strtotime(($r['trip_date'] ?? date('Y-m-d')).' '.$sd);
        $t1 = strtotime(($r['trip_date'] ?? date('Y-m-d')).' '.$ad);
        $diff = (int)round(($t1 - $t0) / 60);
        return max($diff, 0);
    }
}

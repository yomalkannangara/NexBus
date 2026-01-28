<?php
namespace App\models\depot_officer;

use App\models\common\BaseModel;

class BusProfileModel extends BaseModel
{
    /** Disable DB constructor */
    public function __construct() {}

    /**
     * Get bus information by registration number
     */
    public function getBusByReg(string $busReg): array
    {
        $buses = $this->seedBuses();
        foreach ($buses as $b) {
            if (($b['bus_reg_no'] ?? '') === $busReg) {
                return $b;
            }
        }
        return [];
    }

    /**
     * Get current tracking status for bus
     */
    public function getTracking(string $busReg): array
    {
        $tracking = $this->seedTracking();
        // Get most recent record
        $matches = array_filter($tracking, fn($t) => ($t['bus_reg_no'] ?? '') === $busReg);
        if (empty($matches)) return [];
        
        usort($matches, fn($a, $b) => strcmp($b['snapshot_at'] ?? '', $a['snapshot_at'] ?? ''));
        return $matches[0] ?? [];
    }

    /**
     * Get assignment history for bus
     */
    public function getAssignments(string $busReg, int $limit = 10): array
    {
        $assignments = $this->seedAssignments();
        $matches = array_filter($assignments, fn($a) => ($a['bus_reg_no'] ?? '') === $busReg);
        
        usort($matches, fn($a, $b) => strcmp($b['assigned_date'] ?? '', $a['assigned_date'] ?? ''));
        return array_slice($matches, 0, $limit);
    }

    /**
     * Get trip history for bus
     */
    public function getTrips(string $busReg, int $limit = 20): array
    {
        $trips = $this->seedTrips();
        $matches = array_filter($trips, fn($t) => ($t['bus_reg_no'] ?? '') === $busReg);
        
        usort($matches, fn($a, $b) => strcmp($b['trip_date'] ?? '', $a['trip_date'] ?? ''));
        return array_slice($matches, 0, $limit);
    }

    /* ===== Seed Data ===== */

    private function seedBuses(): array
    {
        return [
            ['bus_reg_no'=>'NA-1234','make_model'=>'Ashok Leyland Viking','capacity'=>52,'license_expiry'=>'2025-12-31','status'=>'Active'],
            ['bus_reg_no'=>'NC-4567','make_model'=>'Volvo B7R','capacity'=>49,'license_expiry'=>'2025-11-15','status'=>'Active'],
            ['bus_reg_no'=>'NB-2222','make_model'=>'Tata Sunbird','capacity'=>45,'license_expiry'=>'2025-10-20','status'=>'Maintenance'],
            ['bus_reg_no'=>'ND-8901','make_model'=>'Ashok Leyland Lynx','capacity'=>42,'license_expiry'=>'2025-09-10','status'=>'Active'],
            ['bus_reg_no'=>'QE-1111','make_model'=>'Volvo B9R','capacity'=>55,'license_expiry'=>'2026-01-05','status'=>'Active'],
            ['bus_reg_no'=>'QF-2222','make_model'=>'Ashok Leyland Trident','capacity'=>47,'license_expiry'=>'2025-08-30','status'=>'Active'],
        ];
    }

    private function seedTracking(): array
    {
        $today = date('Y-m-d');
        $yday  = date('Y-m-d', strtotime('-1 day'));

        return [
            ['snapshot_at'=>"$today 08:10:05",'bus_reg_no'=>'NA-1234','route_no'=>'138','operational_status'=>'Delayed','avg_delay_min'=>5,'speed'=>32],
            ['snapshot_at'=>"$today 08:08:12",'bus_reg_no'=>'NC-4567','route_no'=>'100','operational_status'=>'OnTime','avg_delay_min'=>0,'speed'=>41],
            ['snapshot_at'=>"$today 07:55:30",'bus_reg_no'=>'NB-2222','route_no'=>'199','operational_status'=>'Breakdown','avg_delay_min'=>0,'speed'=>0],
            ['snapshot_at'=>"$today 09:02:44",'bus_reg_no'=>'ND-8901','route_no'=>'100','operational_status'=>'Delayed','avg_delay_min'=>3,'speed'=>27],
            ['snapshot_at'=>"$today 07:40:00",'bus_reg_no'=>'QE-1111','route_no'=>'17','operational_status'=>'Delayed','avg_delay_min'=>2,'speed'=>30],
            ['snapshot_at'=>"$today 08:25:19",'bus_reg_no'=>'QF-2222','route_no'=>'17','operational_status'=>'OnTime','avg_delay_min'=>0,'speed'=>38],
        ];
    }

    private function seedAssignments(): array
    {
        return [
            ['bus_reg_no'=>'NA-1234','route_no'=>'138','driver_name'=>'Jayantha Silva','assigned_date'=>'2025-12-15','status'=>'Active'],
            ['bus_reg_no'=>'NA-1234','route_no'=>'100','driver_name'=>'Kasun Perera','assigned_date'=>'2025-12-10','status'=>'Inactive'],
            ['bus_reg_no'=>'NA-1234','route_no'=>'138','driver_name'=>'Rasil Kularatne','assigned_date'=>'2025-12-01','status'=>'Inactive'],
            ['bus_reg_no'=>'NC-4567','route_no'=>'100','driver_name'=>'Nimal Fernando','assigned_date'=>'2025-12-12','status'=>'Active'],
            ['bus_reg_no'=>'NC-4567','route_no'=>'199','driver_name'=>'Saman Dissanayake','assigned_date'=>'2025-11-20','status'=>'Inactive'],
            ['bus_reg_no'=>'NB-2222','route_no'=>'199','driver_name'=>'Dilshan Wickramasinghe','assigned_date'=>'2025-12-14','status'=>'Active'],
            ['bus_reg_no'=>'ND-8901','route_no'=>'100','driver_name'=>'Priyantha Gunawardena','assigned_date'=>'2025-12-11','status'=>'Active'],
            ['bus_reg_no'=>'QE-1111','route_no'=>'17','driver_name'=>'Roshan Kumara','assigned_date'=>'2025-12-09','status'=>'Active'],
            ['bus_reg_no'=>'QF-2222','route_no'=>'17','driver_name'=>'Anuruddha Rathnayake','assigned_date'=>'2025-12-13','status'=>'Active'],
        ];
    }

    private function seedTrips(): array
    {
        $today = date('Y-m-d');
        $yday  = date('Y-m-d', strtotime('-1 day'));
        $2day  = date('Y-m-d', strtotime('-2 days'));

        return [
            ['bus_reg_no'=>'NA-1234','trip_date'=>$today,'route_no'=>'138','turn_no'=>1,'departure_time'=>'07:35','arrival_time'=>'08:45','status'=>'Completed'],
            ['bus_reg_no'=>'NA-1234','trip_date'=>$today,'route_no'=>'138','turn_no'=>2,'departure_time'=>'09:02','arrival_time'=>null,'status'=>'InProgress'],
            ['bus_reg_no'=>'NA-1234','trip_date'=>$yday,'route_no'=>'138','turn_no'=>1,'departure_time'=>'07:28','arrival_time'=>'08:35','status'=>'Completed'],
            ['bus_reg_no'=>'NA-1234','trip_date'=>$yday,'route_no'=>'138','turn_no'=>2,'departure_time'=>'09:00','arrival_time'=>'10:15','status'=>'Completed'],
            ['bus_reg_no'=>'NC-4567','trip_date'=>$today,'route_no'=>'100','turn_no'=>1,'departure_time'=>'07:57','arrival_time'=>null,'status'=>'InProgress'],
            ['bus_reg_no'=>'NC-4567','trip_date'=>$yday,'route_no'=>'100','turn_no'=>1,'departure_time'=>'08:10','arrival_time'=>'09:05','status'=>'Completed'],
            ['bus_reg_no'=>'NC-4567','trip_date'=>$yday,'route_no'=>'100','turn_no'=>2,'departure_time'=>'09:30','arrival_time'=>'10:20','status'=>'Completed'],
            ['bus_reg_no'=>'NB-2222','trip_date'=>$today,'route_no'=>'199','turn_no'=>1,'departure_time'=>'06:55','arrival_time'=>null,'status'=>'Cancelled'],
            ['bus_reg_no'=>'ND-8901','trip_date'=>$today,'route_no'=>'100','turn_no'=>1,'departure_time'=>null,'arrival_time'=>null,'status'=>'Planned'],
            ['bus_reg_no'=>'QE-1111','trip_date'=>$today,'route_no'=>'17','turn_no'=>1,'departure_time'=>'07:03','arrival_time'=>null,'status'=>'InProgress'],
            ['bus_reg_no'=>'QE-1111','trip_date'=>$yday,'route_no'=>'17','turn_no'=>1,'departure_time'=>'07:00','arrival_time'=>'08:30','status'=>'Completed'],
            ['bus_reg_no'=>'QF-2222','trip_date'=>$today,'route_no'=>'17','turn_no'=>2,'departure_time'=>null,'arrival_time'=>null,'status'=>'Planned'],
        ];
    }
}

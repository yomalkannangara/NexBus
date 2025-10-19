<?php
namespace App\Models;

final class FleetModel {
    public function summary(): array {
        return [
            ['label'=>'Total Buses','value'=>'1,247','class'=>'text-primary'],
            ['label'=>'Active Buses','value'=>'1,184','class'=>'text-green'],
            ['label'=>'In Maintenance','value'=>'45','class'=>'text-yellow'],
            ['label'=>'Out of Service','value'=>'18','class'=>'text-red'],
        ];
    }
    public function fleetRows(): array {
        return [
            ['id'=>'BUS001','number'=>'NC-1247','route'=>'Colombo - Kandy','routeNumber'=>'R001','status'=>'Active','location'=>'Kadugannawa','capacity'=>52,'lastMaintenance'=>'2024-12-15','nextService'=>'2025-01-15'],
            ['id'=>'BUS002','number'=>'WP-3456','route'=>'Galle - Matara','routeNumber'=>'R045','status'=>'Maintenance','location'=>'Galle Depot','capacity'=>48,'lastMaintenance'=>'2024-12-10','nextService'=>'2025-01-10'],
            ['id'=>'BUS003','number'=>'CP-7890','route'=>'Negombo - Airport','routeNumber'=>'R187','status'=>'Active','location'=>'Katunayake','capacity'=>35,'lastMaintenance'=>'2024-12-20','nextService'=>'2025-01-20'],
            ['id'=>'BUS004','number'=>'SP-2134','route'=>'Kurunegala - Anuradhapura','routeNumber'=>'R092','status'=>'Out of Service','location'=>'Kurunegala Depot','capacity'=>55,'lastMaintenance'=>'2024-11-30','nextService'=>'2024-12-30'],
            ['id'=>'BUS005','number'=>'EP-5678','route'=>'Trincomalee - Batticaloa','routeNumber'=>'R156','status'=>'Active','location'=>'Trincomalee','capacity'=>45,'lastMaintenance'=>'2024-12-18','nextService'=>'2025-01-18'],
        ];
    }
}

<?php
namespace App\Models;

final class HealthModel {
    public function metrics(): array {
        return [
            ['value'=>'1,184','label'=>'Healthy Buses','icon'=>'check','accent'=>'green'],
            ['value'=>'45','label'=>'Needs Maintenance','icon'=>'wrench','accent'=>'yellow'],
            ['value'=>'18','label'=>'Critical Issues','icon'=>'alert','accent'=>'red'],
        ];
    }
    public function ongoing(): array {
        return [
            ['bus'=>'SP-2134','task'=>'Engine Overhaul','start'=>'2025-01-08','workshop'=>'Ravi Mechanic','eta'=>'2025-01-12','progress'=>65],
            ['bus'=>'WP-3456','task'=>'Brake System Repair','start'=>'2025-01-09','workshop'=>'Saman Workshop','eta'=>'2025-01-11','progress'=>30],
            ['bus'=>'NC-9871','task'=>'Transmission Service','start'=>'2025-01-10','workshop'=>'Central Garage','eta'=>'2025-01-11','progress'=>80],
        ];
    }
    public function completed(): array {
        return [
            ['bus'=>'NC-1247','task'=>'Regular Service','date'=>'2025-01-07','vendor'=>'Auto Lanka','next'=>'2025-04-07'],
        ];
    }
}

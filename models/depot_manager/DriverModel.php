<?php
namespace App\Models;

final class DriverModel {
    public function metrics(): array {
        return [
            ['value'=>'1,792','label'=>'Active Drivers','accent'=>'green'],
            ['value'=>'1,856','label'=>'Active Conductors','accent'=>'green'],
            ['value'=>'18','label'=>'Suspended Drivers','accent'=>'red'],
            ['value'=>'12','label'=>'Suspended Conductors','accent'=>'red'],
        ];
    }
    public function driverActivities(): array {
        return [
            ['name'=>'Sunil Perera','id'=>'DL-2024-001','text'=>'License Renewed','time'=>'2 hours ago','status'=>'Active'],
            ['name'=>'Kumara Silva','id'=>'DL-2024-045','text'=>'Training Completed','time'=>'4 hours ago','status'=>'Active'],
            ['name'=>'Ravi Fernando','id'=>'DL-2024-089','text'=>'Medical Check Done','time'=>'6 hours ago','status'=>'Active'],
            ['name'=>'Nimal Rodrigo','id'=>'DL-2024-123','text'=>'Violation Reported','time'=>'8 hours ago','status'=>'Under Review'],
        ];
    }
    public function conductorActivities(): array {
        return [
            ['name'=>'Pradeep Kumar','id'=>'CON-2024-156','text'=>'Shift Completed','time'=>'1 hour ago','status'=>'Active'],
        ];
    }
}

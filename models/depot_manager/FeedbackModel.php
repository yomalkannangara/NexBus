<?php
namespace App\Models;

final class FeedbackModel {
    public function cards(): array {
        return [
            ['value'=>'47','label'=>'Total This Month','trend'=>'down','trendText'=>'-12% from last month','class'=>'text-primary','icon'=>'message-square','accent'=>'primary'],
            ['value'=>'15','label'=>'Open Complaints','trend'=>'up','trendText'=>'+5% from last week','class'=>'text-red','icon'=>'message-circle','accent'=>'red'],
            ['value'=>'32','label'=>'Resolved This Month','trend'=>'up','trendText'=>'+8% resolution rate','class'=>'text-green','icon'=>'message-circle','accent'=>'green'],
            ['value'=>'4.2','label'=>'Average Rating','trend'=>'up','trendText'=>'+0.3 from last month','class'=>'text-secondary','icon'=>'star','accent'=>'secondary'],
        ];
    }
    public function rows(): array {
        return [
            ['id'=>'FB001','date'=>'2025-01-10','busNumber'=>'NC-1247','route'=>'Colombo - Kandy','passengerName'=>'Saman Kumara','type'=>'Complaint','category'=>'Delay','description'=>'Bus was 45 minutes late from scheduled departure','status'=>'In Progress','rating'=>2],
            ['id'=>'FB002','date'=>'2025-01-10','busNumber'=>'WP-3456','route'=>'Galle - Matara','passengerName'=>'Amara Silva','type'=>'Feedback','category'=>'Service Quality','description'=>'Very clean bus and friendly driver. Excellent service!','status'=>'Resolved','rating'=>5],
            ['id'=>'FB003','date'=>'2025-01-09','busNumber'=>'CP-7890','route'=>'Negombo - Airport','passengerName'=>'Nimal Fernando','type'=>'Complaint','category'=>'Driver Behavior','description'=>'Driver was rude and spoke harshly to elderly passengers','status'=>'Open','rating'=>1],
            ['id'=>'FB004','date'=>'2025-01-09','busNumber'=>'SP-2134','route'=>'Kurunegala - Anuradhapura','passengerName'=>'Priya Perera','type'=>'Suggestion','category'=>'Route Improvement','description'=>'Please add more stops between Kurunegala and Dambulla','status'=>'Under Review','rating'=>3],
            ['id'=>'FB005','date'=>'2025-01-08','busNumber'=>'EP-5678','route'=>'Trincomalee - Batticaloa','passengerName'=>'Rajesh Kumar','type'=>'Complaint','category'=>'Vehicle Condition','description'=>'Air conditioning not working, very uncomfortable journey','status'=>'Resolved','rating'=>2],
        ];
    }
}

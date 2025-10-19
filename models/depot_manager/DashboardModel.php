<?php
namespace App\Models;

final class DashboardModel {
    public function topStats(): array {
        return [
            ['title'=>'Total Buses','value'=>'1,247','change'=>'+15 from yesterday','trend'=>'up','icon'=>'bus','color'=>'var(--chart-1)'],
            ['title'=>'Registered Bus Owners','value'=>'342','change'=>'+8 from yesterday','trend'=>'up','icon'=>'users','color'=>'var(--chart-2)'],
            ['title'=>'Active Routes','value'=>'156','change'=>'+4 from yesterday','trend'=>'up','icon'=>'check-circle','color'=>'var(--chart-2)'],
        ];
    }
    public function dailyStats(): array {
        return [
            ['title'=>"Today's Complaints",'value'=>'7','change'=>'-2% from yesterday','trend'=>'down','icon'=>'alert','color'=>'#EA580C'],
            ['title'=>'Delayed Buses Today','value'=>'23','change'=>'+4% from yesterday','trend'=>'up','icon'=>'clock','color'=>'#DC2626'],
            ['title'=>'Broken Buses Today','value'=>'5','change'=>'+1% from yesterday','trend'=>'up','icon'=>'alert','color'=>'#DC2626'],
        ];
    }
    public function todayLabel(): string {
        // Example label to mirror your header
        return 'Monday 11 August 2025';
    }
}

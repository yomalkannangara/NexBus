<?php
namespace App\Models;

final class EarningsModel {
    public function top(): array {
        return [
            ['value'=>'Rs. 845,500','label'=>'Daily Income','trend'=>'+5.2% from yesterday','color'=>'maroon'],
            ['value'=>'Rs. 1,250,000','label'=>'Highest Income','sub'=>'December 31, 2024','color'=>'green'],
            ['value'=>'Rs. 425,000','label'=>'Lowest Income','sub'=>'January 1, 2025','color'=>'red'],
        ];
    }
    public function busIncome(): array {
        return [
            ['number'=>'NC-1247','route'=>'Colombo - Kandy','daily'=>'Rs. 12,500','weekly'=>'Rs. 87,500','eff'=>'95%'],
            ['number'=>'WP-3456','route'=>'Galle - Matara','daily'=>'Rs. 8,750','weekly'=>'Rs. 61,250','eff'=>'88%'],
            ['number'=>'CP-7890','route'=>'Negombo - Airport','daily'=>'Rs. 15,200','weekly'=>'Rs. 106,400','eff'=>'98%'],
            ['number'=>'SP-2134','route'=>'Kurunegala - Anuradhapura','daily'=>'Rs. 0','weekly'=>'Rs. 45,600','eff'=>'0%'],
            ['number'=>'EP-5678','route'=>'Trincomalee - Batticaloa','daily'=>'Rs. 9,800','weekly'=>'Rs. 68,600','eff'=>'92%'],
        ];
    }
    public function monthlySummary(): array {
        return [
            'current'  => 'Rs. 24.5M',
            'previous' => 'Rs. 23.2M',
            'growth'   => '+5.6%',
        ];
    }
}

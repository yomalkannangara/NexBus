<?php
namespace App\Models;

final class PerformanceModel {
    public function cards(): array {
        return [
            ['title'=>'Delayed Buses Today','value'=>'47','sub'=>'Filtered results','color'=>'red','icon'=>'clock'],
            ['title'=>'Average Driver Rating','value'=>'8.0','sub'=>'Filtered average','color'=>'green','icon'=>'star'],
            ['title'=>'Speed Violations','value'=>'75','sub'=>'Filtered data','color'=>'yellow','icon'=>'zap'],
            ['title'=>'Long Wait Times','value'=>'15%','sub'=>'Over 10 minutes','color'=>'maroon','icon'=>'trending-up'],
        ];
    }
    public function topDrivers(): array {
        return [
            ['rank'=>1,'name'=>'Sunil Perera','route'=>'Colombo - Kandy','delay'=>'2%','rating'=>'4.8','speed'=>'1','wait'=>'3%'],
            ['rank'=>2,'name'=>'Pradeep Kumar','route'=>'Trincomalee - Batticaloa','delay'=>'4%','rating'=>'4.7','speed'=>'2','wait'=>'5%'],
            ['rank'=>3,'name'=>'Ravi Fernando','route'=>'Negombo - Airport','delay'=>'5%','rating'=>'4.6','speed'=>'0','wait'=>'4%'],
            ['rank'=>4,'name'=>'Anil Jayawardana','route'=>'Galle - Matara','delay'=>'6%','rating'=>'4.5','speed'=>'3','wait'=>'7%'],
            ['rank'=>5,'name'=>'Mahesh Silva','route'=>'Kurunegala - Anuradhapura','delay'=>'7%','rating'=>'4.4','speed'=>'1','wait'=>'8%'],
        ];
    }
}

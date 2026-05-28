<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\contact_location;
use App\Models\contact_department;
use App\Models\contact_employees;

class ContactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 創建位置
        $taipei = contact_location::create([
            'location_name' => '台北總部',
            'address' => '台北市信義區信義路五段7號',
        ]);

        $taichung = contact_location::create([
            'location_name' => '台中分部',
            'address' => '台中市西屯區台灣大道三段99號',
        ]);

        $kaohsiung = contact_location::create([
            'location_name' => '高雄分部',
            'address' => '高雄市前鎮區中山二路2號',
        ]);

        // 創建部門
        $rd = contact_department::create([
            'department_name' => '研發部',
            'location_id' => $taipei->id,
        ]);

        $marketing = contact_department::create([
            'department_name' => '行銷部',
            'location_id' => $taipei->id,
        ]);

        $sales = contact_department::create([
            'department_name' => '銷售部',
            'location_id' => $taichung->id,
        ]);

        $hr = contact_department::create([
            'department_name' => '人力資源部',
            'location_id' => $kaohsiung->id,
        ]);

        // 創建員工
        contact_employees::create([
            'name' => '張大明',
            'email' => 'daming@example.com',
            'phone' => '0912345678',
            'position' => '資深工程師',
            'department_id' => $rd->id,
        ]);

        contact_employees::create([
            'name' => '李小華',
            'email' => 'xiaohua@example.com',
            'phone' => '0923456789',
            'position' => '行銷經理',
            'department_id' => $marketing->id,
        ]);

        contact_employees::create([
            'name' => '王美麗',
            'email' => 'meili@example.com',
            'phone' => '0934567890',
            'position' => '銷售主管',
            'department_id' => $sales->id,
        ]);

        contact_employees::create([
            'name' => '陳志明',
            'email' => 'zhiming@example.com',
            'phone' => '0945678901',
            'position' => 'HR 專員',
            'department_id' => $hr->id,
        ]);
    }
}

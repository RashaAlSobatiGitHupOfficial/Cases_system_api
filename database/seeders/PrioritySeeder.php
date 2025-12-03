<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PrioritySeeder extends Seeder
{
   public function run()
    {
        DB::table('priorities')->insert([
            [
                'priority_name' => 'High',
                'delay_time'    => 1,  // أسرع وقت معالجة
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'priority_name' => 'Middle',
                'delay_time'    => 3,
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'priority_name' => 'Normal',
                'delay_time'    => 5,
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'priority_name' => 'Low',
                'delay_time'    => 7, // أقصى تأخير
                'created_at'    => now(),
                               'updated_at'    => now(),
            ],
        ]);
    }
}

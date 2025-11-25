<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('permissions')->insert([
            ['permission_name' => 'Add Case', 'created_at' => now(), 'updated_at' => now()],
            ['permission_name' => 'Edit Case', 'created_at' => now(), 'updated_at' => now()],
            ['permission_name' => 'Delete Case', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}

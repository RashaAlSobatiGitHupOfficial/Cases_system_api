<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\User;
use App\Models\Employee;
use App\Models\Permission;
use Illuminate\Support\Facades\DB;

class AdminRoleSeeder extends Seeder
{
    public function run()
    {
        DB::transaction(function () {

            $adminRole = Role::updateOrCreate(
                ['role_name' => 'Super Admin'],
                ['role_name' => 'Super Admin']
            );

            $permissions = Permission::all();

            $adminRole->permissions()->sync($permissions->pluck('id')->toArray());

            $user = User::where('username', 'admin')->first();

            if (!$user) {
                throw new \Exception("Admin user not found. Run UserSeeder first.");
            }

        
            $user->role_id = $adminRole->id;
            $user->save();

        });
    }
}

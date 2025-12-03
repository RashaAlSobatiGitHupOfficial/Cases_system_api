<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\PermissionCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
     public function run()
    {
        $categories = [
            [
                'category_name' => 'Users',
                'permissions' => [
                    'add user',
                    'edit user',
                    'delete user',
                    'view user'
                ]
            ],
            [
                'category_name' => 'Clients',
                'permissions' => [
                    'add client',
                    'edit client',
                    'delete client',
                    'view client'
                ]
            ],
            [
                'category_name' => 'Roles',
                'permissions' => [
                    'add role',
                    'edit role',
                    'delete role',
                    'assign permissions'
                ]
            ],
             [
                'category_name' => 'Employees',
                'permissions' => [
                    'add employee',
                    'edit employee',
                    'delete employee',
                    'view employee'
                ]
            ],
             [
                'category_name' => 'Reports',
                'permissions' => [
                    'add report',
                    'edit report',
                    'delete report',
                    'view report'
                ]
            ],
            [
                'category_name' => 'statistics',
                'permissions' => [
                    'add statistic',
                    'edit statistic',
                    'delete statistic',
                    'view statistic'
                ]
            ],
            [
                'category_name' => 'cases',
                'permissions' => [
                    'cases.view_all',
                    'cases.view_assigned',
                    'cases.view_unassigned',
                    'cases.assign',
                    'cases.accept',
                    'cases.reassign',
                    'cases.remove_employee',
                    'cases.edit',
                    'cases.delete'
                ]
            ],

            [
                'category_name' => 'dashboard',
                'permissions' => [
                    'view dashboard'
                ]
            ],
        ];

        foreach ($categories as $cat) {
            $category = PermissionCategory::create([
                'category_name' => $cat['category_name']
            ]);

            foreach ($cat['permissions'] as $perm) {
                Permission::create([
                    'permission_name' => strtolower($perm),
                    'category_id'     => $category->id
                ]);
            }
        }
    }
}

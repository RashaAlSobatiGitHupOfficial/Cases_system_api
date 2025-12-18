<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Employee;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        $user = User::updateOrCreate(
            [
                'username' => 'admin',
                'password' => Hash::make('12345678'),
            ]
        );

        Employee::updateOrCreate(
            ['user_id' => $user->id],
            [
                'first_name' => 'admin',
                'last_name' => 'admin',
                'middle_name' => 'admin',
                'gender' => 'female',
                'phone' => '777777777',

                'email' => 'admin@example.com',
            ]
        );
    }
}

<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'سوبر ادمن',
            'email' => 'admin@emaarplus.com',
            'password' => 'password',
            'phone' => '0599000000',
            'role' => 'super_admin',
            'is_active' => true,
            'preferred_currency' => 'ILS',
        ]);

        User::create([
            'name' => 'سارة أحمد',
            'email' => 'employee@emaarplus.com',
            'password' => 'password',
            'phone' => '0599111111',
            'role' => 'employee',
            'is_active' => true,
            'preferred_currency' => 'ILS',
        ]);

        User::create([
            'name' => 'محمود حسن',
            'email' => 'guard@emaarplus.com',
            'password' => 'password',
            'phone' => '0599222222',
            'role' => 'guard',
            'is_active' => true,
            'preferred_currency' => 'ILS',
        ]);
    }
}

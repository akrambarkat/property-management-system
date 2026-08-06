<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            CurrencySeeder::class,
            SettingSeeder::class,
            SmsProviderSeeder::class,
            SmsTemplateSeeder::class,
            UserSeeder::class,
            DummyDataSeeder::class,
        ]);
    }
}

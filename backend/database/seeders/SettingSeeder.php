<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        Setting::create(['key' => 'app_name', 'value' => 'EMAARPlus']);
        Setting::create(['key' => 'default_currency', 'value' => 'ILS']);
        Setting::create(['key' => 'electricity_unit_price', 'value' => '0.50']);
        Setting::create(['key' => 'water_unit_price', 'value' => '3.00']);
        Setting::create(['key' => 'invoice_prefix', 'value' => 'INV-']);
        Setting::create(['key' => 'contract_prefix', 'value' => 'CTR-']);
        Setting::create(['key' => 'receipt_prefix', 'value' => 'REC-']);
    }
}

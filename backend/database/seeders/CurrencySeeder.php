<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        Currency::create(['code' => 'ILS', 'name' => 'شيكل', 'symbol' => '₪', 'exchange_rate' => 1.0000, 'is_default' => true]);
        Currency::create(['code' => 'JOD', 'name' => 'دينار أردني', 'symbol' => 'د.أ', 'exchange_rate' => 0.2000, 'is_default' => false]);
        Currency::create(['code' => 'USD', 'name' => 'دولار أمريكي', 'symbol' => '$', 'exchange_rate' => 0.2800, 'is_default' => false]);
    }
}

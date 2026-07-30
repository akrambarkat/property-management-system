<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->decimal('electricity_amount', 10, 2)->default(0)->after('rent_amount');
            $table->decimal('water_amount', 10, 2)->default(0)->after('electricity_amount');
            $table->decimal('internet_amount', 10, 2)->default(0)->after('water_amount');
            $table->decimal('services_amount', 10, 2)->default(0)->after('internet_amount');
        });
    }

    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->dropColumn(['electricity_amount', 'water_amount', 'internet_amount', 'services_amount']);
        });
    }
};

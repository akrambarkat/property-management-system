<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_statistics', function (Blueprint $table) {
            $table->id();
            $table->date('stat_date');
            $table->string('provider_key')->nullable();
            $table->unsignedBigInteger('sent')->default(0);
            $table->unsignedBigInteger('failed')->default(0);
            $table->unsignedBigInteger('pending')->default(0);
            $table->decimal('total_cost', 12, 4)->default(0);
            $table->decimal('avg_duration_ms', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(['stat_date', 'provider_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_statistics');
    }
};

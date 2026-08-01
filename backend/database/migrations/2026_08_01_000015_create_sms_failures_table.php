<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_failures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sms_log_id')->nullable()->constrained('sms_logs')->cascadeOnDelete();
            $table->foreignId('provider_id')->nullable()->constrained('sms_providers')->nullOnDelete();
            $table->unsignedTinyInteger('attempt');
            $table->string('error_code')->nullable();
            $table->text('error_message');
            $table->text('trace')->nullable();
            $table->integer('http_status')->nullable();
            $table->timestamps();

            $table->index(['sms_log_id', 'attempt']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_failures');
    }
};

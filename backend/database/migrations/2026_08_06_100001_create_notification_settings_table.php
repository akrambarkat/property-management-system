<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // contract_expiration, overdue_invoice, new_payment, maintenance_request, sms_failure, system_alert, login_alert
            $table->boolean('is_enabled')->default(true);
            $table->boolean('in_app_enabled')->default(true);
            $table->boolean('sms_enabled')->default(false);
            $table->boolean('email_enabled')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_settings');
    }
};

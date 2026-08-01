<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('event_type');                     // rent_due, contract_expiry, maintenance, payment_confirmation, payment_failed
            $table->foreignId('template_id')->nullable()->constrained('sms_templates')->nullOnDelete();
            $table->json('conditions')->nullable();           // days_before, status filters
            $table->unsignedTinyInteger('days_before')->nullable();
            $table->string('recipient_scope')->default('tenant'); // tenant|building|property
            $table->foreignId('building_id')->nullable()->constrained('buildings')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_jobs');
    }
};

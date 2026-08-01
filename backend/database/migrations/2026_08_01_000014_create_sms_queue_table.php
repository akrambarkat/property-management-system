<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_queue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sms_log_id')->nullable()->constrained('sms_logs')->cascadeOnDelete();
            $table->string('recipient');
            $table->text('message');
            $table->foreignId('provider_id')->nullable()->constrained('sms_providers')->nullOnDelete();
            $table->string('status')->default('pending');      // pending|processing|sent|failed|cancelled
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->unsignedTinyInteger('max_attempts')->default(3);
            $table->timestamp('available_at')->useCurrent();
            $table->timestamp('locked_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->string('dedupe_key')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_queue');
    }
};

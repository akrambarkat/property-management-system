<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('provider_id')->nullable()->constrained('sms_providers')->nullOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('sms_templates')->nullOnDelete();
            $table->string('recipient');
            $table->text('message');
            $table->string('status');                          // pending|queued|sent|failed|delivered|retrying
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->decimal('cost', 10, 4)->default(0);
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('message_id')->nullable();          // provider message id
            $table->string('provider_status')->nullable();
            $table->integer('http_status')->nullable();
            $table->text('failure_reason')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->json('provider_response')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'created_at']);
            $table->index(['recipient', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
    }
};

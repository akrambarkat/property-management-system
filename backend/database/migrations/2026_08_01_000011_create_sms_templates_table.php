<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();                 // rent_reminder, invoice_created, ...
            $table->string('title');                          // AR display title
            $table->string('subject')->nullable();
            $table->text('message');
            $table->json('variables')->nullable();            // supported variables for this template
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);     // system templates cannot be deleted
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_templates');
    }
};

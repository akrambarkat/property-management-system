<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_providers', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();                    // custom, twilio, vonage, messagebird, jawwal, ooredoo
            $table->string('name');                              // display name (AR)
            $table->string('class');                             // provider implementation class
            $table->string('api_url')->nullable();
            $table->text('api_key')->nullable();
            $table->string('username')->nullable();
            $table->text('password')->nullable();
            $table->string('sender_id')->nullable();
            $table->unsignedSmallInteger('timeout')->default(15);
            $table->unsignedTinyInteger('retries')->default(3);
            $table->string('http_method')->default('POST');       // GET|POST|PUT
            $table->string('content_type')->default('application/json');
            $table->string('authorization_type')->default('bearer'); // bearer|basic|api_key_header|none
            $table->json('custom_headers')->nullable();
            $table->boolean('is_active')->default(false);
            $table->boolean('is_default')->default(false);
            $table->timestamp('last_connected_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_providers');
    }
};

<?php

use App\Models\SmsProvider;
use App\Services\EncryptionService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the display sender name column and encrypts any gateway credentials
 * that were stored in plaintext before the model mutators were introduced.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms_providers', function (Blueprint $table) {
            $table->string('sender_name')->nullable()->after('password');
        });

        $encryptor = app(EncryptionService::class);

        SmsProvider::query()->withoutGlobalScopes()->select(['id', 'api_key', 'password'])->get()
            ->each(function (SmsProvider $provider) use ($encryptor) {
                $updates = [];
                if (is_string($provider->api_key) && $provider->api_key !== '') {
                    $updates['api_key'] = $encryptor->encrypt($provider->api_key);
                }
                if (is_string($provider->password) && $provider->password !== '') {
                    $updates['password'] = $encryptor->encrypt($provider->password);
                }
                if ($updates) {
                    $provider->timestamps = false;
                    // Bypass the model mutators so we never double-encrypt.
                    \Illuminate\Support\Facades\DB::table('sms_providers')
                        ->where('id', $provider->id)
                        ->update($updates);
                }
            });
    }

    public function down(): void
    {
        $encryptor = app(EncryptionService::class);

        SmsProvider::query()->withoutGlobalScopes()->select(['id', 'api_key', 'password'])->get()
            ->each(function (SmsProvider $provider) use ($encryptor) {
                $updates = [];
                if (is_string($provider->api_key) && $provider->api_key !== '') {
                    $updates['api_key'] = $encryptor->decrypt($provider->api_key);
                }
                if (is_string($provider->password) && $provider->password !== '') {
                    $updates['password'] = $encryptor->decrypt($provider->password);
                }
                if ($updates) {
                    \Illuminate\Support\Facades\DB::table('sms_providers')
                        ->where('id', $provider->id)
                        ->update($updates);
                }
            });

        Schema::table('sms_providers', function (Blueprint $table) {
            $table->dropColumn('sender_name');
        });
    }
};

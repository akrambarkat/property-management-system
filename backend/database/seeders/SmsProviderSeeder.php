<?php

namespace Database\Seeders;

use App\Models\SmsProvider;
use Illuminate\Database\Seeder;

class SmsProviderSeeder extends Seeder
{
    public function run(): void
    {
        $providers = [
            [
                'key' => 'custom',
                'name' => 'مزود مخصص (HTTP)',
                'class' => \App\Services\Sms\Providers\CustomProvider::class,
                'is_active' => true,
                'is_default' => true,
            ],
            [
                'key' => 'twilio',
                'name' => 'Twilio',
                'class' => \App\Services\Sms\Providers\TwilioProvider::class,
            ],
            [
                'key' => 'vonage',
                'name' => 'Vonage (Nexmo)',
                'class' => \App\Services\Sms\Providers\CustomProvider::class,
            ],
            [
                'key' => 'messagebird',
                'name' => 'MessageBird',
                'class' => \App\Services\Sms\Providers\CustomProvider::class,
            ],
            [
                'key' => 'jawwal',
                'name' => 'Jawwal SMS',
                'class' => \App\Services\Sms\Providers\JawwalProvider::class,
            ],
            [
                'key' => 'ooredoo',
                'name' => 'Ooredoo SMS',
                'class' => \App\Services\Sms\Providers\CustomProvider::class,
            ],
        ];

        foreach ($providers as $provider) {
            SmsProvider::updateOrCreate(
                ['key' => $provider['key']],
                $provider,
            );
        }
    }
}

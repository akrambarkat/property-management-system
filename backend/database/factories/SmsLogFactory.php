<?php

namespace Database\Factories;

use App\Models\SmsLog;
use App\Models\SmsProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

class SmsLogFactory extends Factory
{
    protected $model = SmsLog::class;

    public function definition(): array
    {
        return [
            'uuid' => fake()->uuid(),
            'provider_id' => SmsProvider::inRandomOrder()->first()?->id,
            'recipient' => fake()->e164PhoneNumber(),
            'message' => fake()->sentence(12),
            'status' => fake()->randomElement(['sent', 'failed', 'queued', 'pending']),
            'attempts' => fake()->numberBetween(0, 3),
            'cost' => fake()->randomFloat(4, 0, 2),
            'duration_ms' => fake()->numberBetween(50, 3000),
            'message_id' => fake()->optional()->uuid(),
            'created_at' => fake()->dateTimeBetween('-30 days'),
        ];
    }

    public function sent(): static
    {
        return $this->state(fn () => ['status' => 'sent', 'sent_at' => now()]);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => 'failed',
            'failure_reason' => fake()->sentence(6),
            'http_status' => fake()->randomElement([400, 401, 403, 500]),
        ]);
    }
}

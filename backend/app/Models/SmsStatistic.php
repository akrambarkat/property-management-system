<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsStatistic extends Model
{
    protected $fillable = [
        'stat_date', 'provider_key', 'sent', 'failed',
        'pending', 'total_cost', 'avg_duration_ms',
    ];

    protected function casts(): array
    {
        return [
            'stat_date' => 'date',
            'total_cost' => 'float',
            'avg_duration_ms' => 'float',
        ];
    }
}

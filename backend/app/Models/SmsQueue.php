<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsQueue extends Model
{
    protected $table = 'sms_queue';

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'sms_log_id', 'recipient', 'message', 'provider_id',
        'status', 'attempts', 'max_attempts', 'available_at',
        'locked_at', 'processed_at', 'dedupe_key',
    ];

    protected function casts(): array
    {
        return [
            'available_at' => 'datetime',
            'locked_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public function log()
    {
        return $this->belongsTo(SmsLog::class, 'sms_log_id');
    }

    public function provider()
    {
        return $this->belongsTo(SmsProvider::class);
    }
}

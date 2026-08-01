<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SmsLog extends Model
{
    use SoftDeletes;

    public const STATUS_PENDING = 'pending';
    public const STATUS_QUEUED = 'queued';
    public const STATUS_SENT = 'sent';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_FAILED = 'failed';
    public const STATUS_RETRYING = 'retrying';

    protected $fillable = [
        'uuid', 'provider_id', 'template_id', 'recipient', 'message',
        'status', 'attempts', 'cost', 'duration_ms', 'message_id',
        'provider_status', 'http_status', 'failure_reason',
        'request_payload', 'response_payload', 'provider_response',
        'created_by', 'sent_at',
    ];

    protected $hidden = ['request_payload'];

    protected function casts(): array
    {
        return [
            'request_payload' => 'array',
            'response_payload' => 'array',
            'provider_response' => 'array',
            'sent_at' => 'datetime',
        ];
    }

    public function provider()
    {
        return $this->belongsTo(SmsProvider::class);
    }

    public function template()
    {
        return $this->belongsTo(SmsTemplate::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function failures()
    {
        return $this->hasMany(SmsFailure::class);
    }
}

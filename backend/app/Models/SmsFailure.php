<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsFailure extends Model
{
    protected $fillable = [
        'sms_log_id', 'provider_id', 'attempt', 'error_code',
        'error_message', 'trace', 'http_status',
    ];

    public function log()
    {
        return $this->belongsTo(SmsLog::class, 'sms_log_id');
    }

    public function provider()
    {
        return $this->belongsTo(SmsProvider::class);
    }
}

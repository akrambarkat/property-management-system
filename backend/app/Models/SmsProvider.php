<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SmsProvider extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'key', 'name', 'class', 'api_url', 'api_key', 'username', 'password',
        'sender_id', 'timeout', 'retries', 'http_method', 'content_type',
        'authorization_type', 'custom_headers', 'is_active', 'is_default',
        'last_connected_at',
    ];

    protected $hidden = ['api_key', 'password'];

    protected function casts(): array
    {
        return [
            'custom_headers' => 'array',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'last_connected_at' => 'datetime',
        ];
    }

    public function logs()
    {
        return $this->hasMany(SmsLog::class);
    }
}

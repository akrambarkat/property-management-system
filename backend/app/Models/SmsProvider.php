<?php

namespace App\Models;

use App\Services\EncryptionService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SmsProvider extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'key', 'name', 'class', 'api_url', 'api_key', 'username', 'password',
        'sender_name', 'sender_id', 'timeout', 'retries', 'http_method',
        'content_type', 'authorization_type', 'custom_headers', 'is_active',
        'is_default', 'last_connected_at',
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

    /**
     * Sensitive gateway credentials are encrypted at rest and decrypted
     * transparently on read so callers keep working with plaintext.
     */
    protected function apiKey(): Attribute
    {
        return $this->encryptedCredential();
    }

    protected function password(): Attribute
    {
        return $this->encryptedCredential();
    }

    private function encryptedCredential(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value === null
                ? null
                : app(EncryptionService::class)->decrypt($value),
            set: fn (?string $value) => $value === null
                ? null
                : app(EncryptionService::class)->encrypt($value),
        );
    }

    public function logs()
    {
        return $this->hasMany(SmsLog::class);
    }
}

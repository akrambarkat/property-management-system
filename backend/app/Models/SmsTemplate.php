<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SmsTemplate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'key', 'title', 'subject', 'message', 'variables', 'is_active', 'is_system',
    ];

    protected function casts(): array
    {
        return [
            'variables' => 'array',
            'is_active' => 'boolean',
            'is_system' => 'boolean',
        ];
    }

    public function logs()
    {
        return $this->hasMany(SmsLog::class);
    }
}

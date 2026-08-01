<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SmsJob extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'event_type', 'template_id', 'conditions', 'days_before',
        'recipient_scope', 'building_id', 'is_active', 'last_run_at',
    ];

    protected function casts(): array
    {
        return [
            'conditions' => 'array',
            'is_active' => 'boolean',
            'last_run_at' => 'datetime',
        ];
    }

    public function template()
    {
        return $this->belongsTo(SmsTemplate::class);
    }

    public function building()
    {
        return $this->belongsTo(Building::class);
    }
}

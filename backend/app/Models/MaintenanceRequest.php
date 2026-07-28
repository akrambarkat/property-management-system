<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaintenanceRequest extends Model
{
    protected $fillable = [
        'unit_id', 'requested_by', 'description', 'priority',
        'status', 'assigned_to', 'cost', 'completed_at', 'notes'
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
            'cost' => 'decimal:2',
        ];
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}

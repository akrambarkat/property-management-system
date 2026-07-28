<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UtilityReading extends Model
{
    protected $fillable = [
        'unit_id', 'reading_date', 'utility_type',
        'previous_reading', 'current_reading', 'consumption',
        'unit_price', 'total', 'recorded_by', 'notes'
    ];

    protected function casts(): array
    {
        return [
            'reading_date' => 'date',
            'previous_reading' => 'decimal:2',
            'current_reading' => 'decimal:2',
            'consumption' => 'decimal:2',
            'unit_price' => 'decimal:4',
            'total' => 'decimal:2',
        ];
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}

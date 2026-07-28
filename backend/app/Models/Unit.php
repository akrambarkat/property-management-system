<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    protected $fillable = [
        'building_id', 'unit_number', 'unit_type', 'floor',
        'area', 'rent_amount', 'status', 'notes', 'is_active'
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'floor' => 'integer',
            'area' => 'decimal:2',
            'rent_amount' => 'decimal:2',
        ];
    }

    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    public function utilityReadings()
    {
        return $this->hasMany(UtilityReading::class);
    }

    public function maintenanceRequests()
    {
        return $this->hasMany(MaintenanceRequest::class);
    }

    public function currentContract()
    {
        return $this->hasOne(Contract::class)->where('status', 'active');
    }
}

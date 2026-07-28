<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    protected $fillable = [
        'first_name', 'last_name', 'id_number', 'phone',
        'email', 'address', 'notes', 'is_active'
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    public function currentUnit()
    {
        return $this->hasOneThrough(
            Unit::class,
            Contract::class,
            'tenant_id',
            'id',
            'id',
            'unit_id'
        )->where('contracts.status', 'active');
    }
}

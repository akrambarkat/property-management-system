<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Building extends Model
{
    protected $fillable = ['location_id', 'name', 'address', 'floors', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'floors' => 'integer'];
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function units()
    {
        return $this->hasMany(Unit::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }
}

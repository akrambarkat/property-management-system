<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Tenant extends Model
{
    protected $fillable = [
        'first_name', 'last_name', 'id_number', 'id_photo_path', 'phone',
        'email', 'address', 'notes', 'is_active'
    ];

    protected $appends = ['id_photo_url'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function getIdPhotoUrlAttribute()
    {
        return $this->id_photo_path ? Storage::url($this->id_photo_path) : null;
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

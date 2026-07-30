<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $appends = ['balance'];

    protected $fillable = [
        'contract_id', 'invoice_number', 'issue_date', 'due_date',
        'rent_amount', 'electricity_amount', 'water_amount', 'internet_amount',
        'services_amount', 'total_amount', 'status', 'paid_amount', 'notes'
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'due_date' => 'date',
            'rent_amount' => 'decimal:2',
            'electricity_amount' => 'decimal:2',
            'water_amount' => 'decimal:2',
            'internet_amount' => 'decimal:2',
            'services_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
        ];
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function getBalanceAttribute()
    {
        return $this->total_amount - $this->paid_amount;
    }
}

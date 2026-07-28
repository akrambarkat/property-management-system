<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\Setting;

class ContractService
{
    public function generateContractNumber(): string
    {
        $prefix = Setting::where('key', 'contract_prefix')->value('value') ?? 'CTR-';
        $last = Contract::max('id') ?? 0;
        return $prefix . str_pad($last + 1, 4, '0', STR_PAD_LEFT);
    }
}

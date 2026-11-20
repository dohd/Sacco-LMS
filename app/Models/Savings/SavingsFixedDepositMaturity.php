<?php

namespace App\Models\Savings;

use Illuminate\Database\Eloquent\Model;

class SavingsFixedDepositMaturity extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'maturity_date' => 'date',
        'next_maturity_date' => 'date',
        'principal_amount' => 'decimal:2',
        'interest_amount' => 'decimal:2',
        'rolled_over_amount' => 'decimal:2',
        'available_balance' => 'decimal:2',
    ];

    public function savingsAccount()
    {
        return $this->belongsTo(SavingsAccount::class);
    }
}

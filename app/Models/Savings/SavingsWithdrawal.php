<?php

namespace App\Models\Savings;

use App\Models\ModelTrait;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SavingsWithdrawal extends Model
{
    use HasFactory, ModelTrait;

    protected $guarded = ['id'];

    /**
     * Getters
     * */
    public function getActionButtonsAttribute()
    {
        return $this->getButtonWrapperAttribute(
            $this->getViewButtonAttribute('savings_withdrawals.show', null),
            $this->getEditButtonAttribute('savings_withdrawals.edit', null),
            null,
        );
    }

    /**
     * Relationships
     * */
    public function savingsAccount()
    {
        return $this->belongsTo(SavingsAccount::class);
    }

    public function savingsTransaction()
    {
        return $this->belongsTo(SavingsTransaction::class);
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function paidBy()
    {
        return $this->belongsTo(User::class, 'paid_by');
    }    
}

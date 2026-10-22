<?php

namespace App\Models\Savings;

use App\Models\ModelTrait;
use App\Models\User;
use Auth;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SavingsWithdrawal extends Model
{
    use HasFactory, ModelTrait;

    protected $guarded = ['id'];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        // fetch only member records
        static::addGlobalScope('member_id', function($query) {
            if (Auth::user() && Auth::user()->hasRoleCode('member')) {
                $member = optional(Auth()->user()->member);
                $query->whereHas('savingsAccount', fn($subq) => $subq->where('member_id', $member->id));
            }
        });
    }

    /**
     * Getters
     * */
    public function getActionButtonsAttribute()
    {
        return $this->getButtonWrapperAttribute(
            $this->getViewButtonAttribute('savings_withdrawals.show', 'manage-savings-withdrawal'),
            $this->status === 'pending' ? $this->getEditButtonAttribute('savings_withdrawals.edit', 'request-savings-withdrawal') : null,
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

<?php

namespace App\Models\Savings;

use App\Models\ModelTrait;
use App\Models\User;
use Auth;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SavingsTransaction extends Model
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
            $this->getViewButtonAttribute('savings_transactions.show', 'manage-savings-transaction'),
            $this->status === 'pending' && $this->transaction_type !== 'withdrawal' ? $this->getEditButtonAttribute('savings_transactions.edit', 'create-savings-transaction') : null,
            null,
        );
    }

    /**
     * Relationships
     * */
    public function savingsAccount()
    {
        return $this->belongsTo(SavingsAccount::class, 'savings_account_id');
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function reversalOf()
    {
        return $this->belongsTo(SavingsTransaction::class, 'reversal_of_id');
    }
}

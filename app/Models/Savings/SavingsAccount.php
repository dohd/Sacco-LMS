<?php

namespace App\Models\Savings;

use App\Models\Memberships\Member;
use App\Models\ModelTrait;
use App\Models\User;
use Auth;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SavingsAccount extends Model
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
                $query->where('member_id', $member->id);
            }
        });
    }

    /**
     * Getters
     * */
    public function getActionButtonsAttribute()
    {
        return $this->getButtonWrapperAttribute(
            $this->getViewButtonAttribute('savings_accounts.show', 'view-own-savings'),
            $this->getEditButtonAttribute('savings_accounts.edit', 'manage-savings-accounts'),
            null,
        );
    }

    /**
     * Relationships
     * */
    public function transactions()
    {
        return $this->hasMany(SavingsTransaction::class, 'savings_account_id');
    }

    public function member()
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function savingsProduct()
    {
        return $this->belongsTo(SavingsProduct::class, 'savings_product_id');
    }

    public function openedBy()
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }
}

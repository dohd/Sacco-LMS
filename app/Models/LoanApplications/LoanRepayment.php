<?php

namespace App\Models\LoanApplications;

use App\Models\Memberships\Member;
use App\Models\ModelTrait;
use App\Models\User;
use Auth;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanRepayment extends Model
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
            $this->getViewButtonAttribute('loan_repayments.show', null),
            $this->status === 'pending' ? $this->getEditButtonAttribute('loan_repayments.edit', 'confirm-repayment') : null,
            null,
        );
    }

    /**
     * Relationships
     * */
    public function allocations()
    {
        return $this->hasMany(LoanRepaymentAllocation::class);
    }

    public function loan()
    {
        return $this->belongsTo(Loan::class, 'loan_id');
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function reversedBy()
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }
}

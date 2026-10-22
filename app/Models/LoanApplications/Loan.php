<?php

namespace App\Models\LoanApplications;

use App\Models\Memberships\Member;
use App\Models\ModelTrait;
use Auth;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Loan extends Model
{
    use HasFactory, ModelTrait;

    protected $guarded = ['id'];

    protected $casts = [
        'approved_amount' => 'decimal:2',
        'amount_disbursed' => 'decimal:2',
        'fees_due' => 'decimal:2',
    ];

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
            $this->getViewButtonAttribute('loans.show', 'view-own-loan'),
            null,
            null,
        );
    }

    /**
     * Relationships
     * */
    public function loanApplication()
    {
        return $this->belongsTo(LoanApplication::class);
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function loanApproval()
    {
        return $this->belongsTo(LoanApproval::class);
    }

    public function disbursements()
    {
        return $this->hasMany(LoanDisbursement::class);
    }

    public function repaymentSchedules()
    {
        return $this->hasMany(LoanRepaymentSchedule::class);
    }    
}

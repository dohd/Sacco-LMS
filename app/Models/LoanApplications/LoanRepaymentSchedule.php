<?php

namespace App\Models\LoanApplications;

use App\Models\Memberships\Member;
use App\Models\ModelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanRepaymentSchedule extends Model
{
    use HasFactory, ModelTrait;

    protected $guarded = ['id'];

    /**
     * Getters
     * */
    public function getActionButtonsAttribute()
    {
        return $this->getButtonWrapperAttribute(
            $this->getViewButtonAttribute('loan_repayment_schedules.show', null),
            $this->getEditButtonAttribute('loan_repayment_schedules.edit', null),
            null,
        );
    }

    /**
     * Relationships
     * */
    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    public function repaymentAllocations()
    {
        return $this->hasMany(LoanRepaymentAllocation::class);
    }
}

<?php

namespace App\Models\LoanApplications;

use App\Models\Memberships\Member;
use App\Models\ModelTrait;
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
     * Getters
     * */
    public function getActionButtonsAttribute()
    {
        return $this->getButtonWrapperAttribute(
            $this->getViewButtonAttribute('loans.show', null),
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

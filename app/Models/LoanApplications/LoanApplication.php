<?php

namespace App\Models\LoanApplications;

use App\Models\Memberships\Member;
use App\Models\ModelTrait;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanApplication extends Model
{
    use HasFactory, ModelTrait;

    protected $guarded = ['id'];

    protected $casts = [
        'required_date' => 'date',
        'declaration_date' => 'date',
        'amount_requested' => 'decimal:2',
        'monthly_installment' => 'decimal:2',
        'purpose_amount' => 'decimal:2',
        'total_share_contribution' => 'decimal:2',
        'outstanding_loan_balance' => 'decimal:2',
        'monthly_share_contribution' => 'decimal:2',
        'security_shares' => 'decimal:2',
        'guarantor_security' => 'decimal:2',
    ];

    /**
     * Getters
     * */
    public function getActionButtonsAttribute()
    {
        return $this->getButtonWrapperAttribute(
            $this->getViewButtonAttribute('loan_applications.show', null),
            $this->getEditButtonAttribute('loan_applications.edit', null),
            null,
        );
    }

    /**
     * Relationship
     * */
    public function loanProduct()
    {
        return $this->belongsTo(LoanProduct::class);
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function guarantors()
    {
        return $this->hasMany(LoanGuarantor::class);
    }    

    public function securities()
    {
        return $this->hasMany(LoanSecurity::class);
    }

    public function witnesses()
    {
        return $this->hasMany(LoanWitness::class);
    }

    public function draftedBy()
    {
        return $this->belongsTo(User::class, 'drafted_by');
    }

    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function deferredBy()
    {
        return $this->belongsTo(User::class, 'deferred_by');
    }

    public function rejectedBy()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }
}

<?php

namespace App\Models\LoanApplications;

use App\Models\ModelTrait;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanDisbursement extends Model
{
    use HasFactory, ModelTrait;

    protected $guarded = ['id'];

    /**
     * Getters
     * */
    public function getActionButtonsAttribute()
    {
        return $this->getButtonWrapperAttribute(
            $this->getViewButtonAttribute('loan_disbursements.show', null),
            $this->getEditButtonAttribute('loan_disbursements.edit', null),
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

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function reversedBy()
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }
}

<?php

namespace App\Models\LoanApplications;

use App\Models\Memberships\Member;
use App\Models\ModelTrait;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanRepayment extends Model
{
    use HasFactory, ModelTrait;

    protected $guarded = ['id'];

    /**
     * Getters
     * */
    public function getActionButtonsAttribute()
    {
        return $this->getButtonWrapperAttribute(
            $this->getViewButtonAttribute('loan_repayments.show', null),
            $this->getEditButtonAttribute('loan_repayments.edit', null),
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

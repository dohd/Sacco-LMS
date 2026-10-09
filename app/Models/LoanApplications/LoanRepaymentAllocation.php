<?php

namespace App\Models\LoanApplications;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanRepaymentAllocation extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function repayment()
    {
        return $this->belongsTo(LoanRepayment::class, 'loan_repayment_id');
    }

    public function schedule()
    {
        return $this->belongsTo(
            LoanRepaymentSchedule::class,
            'loan_repayment_schedule_id'
        );
    }

    public function allocatedBy()
    {
        return $this->belongsTo(User::class, 'allocated_by');
    }

    public function reversedBy()
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }
}

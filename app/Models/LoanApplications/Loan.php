<?php

namespace App\Models\LoanApplications;

use App\Models\Memberships\Member;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Loan extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'approved_amount' => 'decimal:2',
        'amount_disbursed' => 'decimal:2',
        'fees_due' => 'decimal:2',
    ];

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
}

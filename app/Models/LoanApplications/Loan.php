<?php

namespace App\Models\LoanApplications;

use App\Models\Memberships\Member;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Loan extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

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

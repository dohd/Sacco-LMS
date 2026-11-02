<?php

namespace App\Models\LoanApplications;

use App\Models\Memberships\Member;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanGuarantor extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    protected $casts = [
        'shares_offered' => 'decimal:2',
        'held_amount' => 'decimal:2',
        'consented_at' => 'datetime',
        'released_at' => 'datetime',
    ];

    public function application()
    {
        return $this->belongsTo(LoanApplication::class, 'loan_application_id');
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    public function holds()
    {
        return $this->hasMany(LoanGuarantorHold::class);
    }
}

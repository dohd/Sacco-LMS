<?php

namespace App\Models\LoanApplications;

use Illuminate\Database\Eloquent\Model;

class LoanGuarantorHold extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'amount' => 'decimal:2',
        'released_at' => 'datetime',
    ];

    public function guarantor()
    {
        return $this->belongsTo(LoanGuarantor::class, 'loan_guarantor_id');
    }
}

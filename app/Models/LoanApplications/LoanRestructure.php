<?php

namespace App\Models\LoanApplications;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class LoanRestructure extends Model
{
    protected $guarded = ['id'];

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    public function performedBy()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}

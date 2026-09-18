<?php

namespace App\Models\Savings;

use App\Models\ModelTrait;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SavingsTransaction extends Model
{
    use HasFactory, ModelTrait;

    protected $guarded = ['id'];


    /**
     * Relationships
     * */
    public function savingsAccount()
    {
        return $this->belongsTo(SavingsAccount::class, 'savings_account_id');
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function reversalOf()
    {
        return $this->belongsTo(SavingsTransaction::class, 'reversal_of_id');
    }
}

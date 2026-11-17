<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountDetail extends Model
{
    use HasFactory;

    /**
     * Relationships
     * */
    public function chartOfAccount()
    {
        return $this->hasMany(chartOfAccount::class);
    }
}

<?php

namespace App\Models\Dividends;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DividendAllocation extends Model
{
    use HasFactory;

    // Controllers only pass validated fields and derive the financial totals.
    protected $guarded = ['id'];

    public function run()
    {
        return $this->belongsTo(DividendRun::class, 'dividend_run_id');
    }

    public function member()
    {
        return $this->belongsTo(\App\Models\Memberships\Member::class);
    }

    public function shareAccount()
    {
        return $this->belongsTo(\App\Models\Shares\ShareAccount::class);
    }

    public function deductions()
    {
        return $this->hasMany(DividendDeduction::class);
    }
}

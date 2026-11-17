<?php

namespace App\Models\Dividends;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DividendRun extends Model
{
    use HasFactory;

    protected $guarded = [
        'id',
        'total_qualifying_shares',
        'gross_dividend_amount',
        'total_withholding_tax',
        'net_dividend_amount',
        'status',
        'approved_by',
        'posted_by',
        'approved_at',
        'posted_at',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'declaration_date' => 'date',
        'payment_date' => 'date',
        'approved_at' => 'datetime',
        'posted_at' => 'datetime',
    ];

    public function allocations()
    {
        return $this->hasMany(DividendAllocation::class);
    }
}

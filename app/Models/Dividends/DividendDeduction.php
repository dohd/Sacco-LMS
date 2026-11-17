<?php

namespace App\Models\Dividends;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DividendDeduction extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function allocation()
    {
        return $this->belongsTo(DividendAllocation::class, 'dividend_allocation_id');
    }
}

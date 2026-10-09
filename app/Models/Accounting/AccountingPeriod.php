<?php

namespace App\Models\Accounting;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountingPeriod extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    /**
     * Getters
     * */
    public function getActionButtonsAttribute()
    {
        return $this->getButtonWrapperAttribute(
            $this->getViewButtonAttribute('accounting_periods.show', null),
            $this->getEditButtonAttribute('accounting_periods.edit', null),
            null,
        );
    }

    /**
     * Relationships
     * */
    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }
}

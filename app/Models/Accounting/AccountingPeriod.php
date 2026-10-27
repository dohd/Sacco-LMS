<?php

namespace App\Models\Accounting;

use App\Models\ModelTrait;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountingPeriod extends Model
{
    use HasFactory, ModelTrait;

    protected $guarded = ['id'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'closed_at' => 'datetime',
    ];

    /**
     * Getters
     * */
    public function getActionButtonsAttribute()
    {
        return $this->getButtonWrapperAttribute(
            $this->getViewButtonAttribute('accounting_periods.show', 'view-accounting-periods'),
            $this->getEditButtonAttribute('accounting_periods.edit', 'edit-accounting-periods'),
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

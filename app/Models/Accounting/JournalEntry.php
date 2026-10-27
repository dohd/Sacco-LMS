<?php

namespace App\Models\Accounting;

use App\Models\ModelTrait;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JournalEntry extends Model
{
    use HasFactory, ModelTrait;

    protected $guarded = ['id'];

    protected $casts = [
        'transaction_date' => 'date',
        'posting_date' => 'date',
        'posted_at' => 'datetime',
    ];

    /**
     * Getters
     * */
    public function getActionButtonsAttribute()
    {
        return $this->getButtonWrapperAttribute(
            $this->getViewButtonAttribute('journal_entries.show', 'view-own-loan'),
            $this->getEditButtonAttribute('journal_entries.edit', 'view-own-loan'),
            null,
        );
    }

    /**
     * Relationships
     * */
    public function lines()
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function accountingPeriod()
    {
        return $this->belongsTo(AccountingPeriod::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function poster()
    {
        return $this->belongsTo(User::class, 'posted_by');
    }   

    public function reversalOf()
    {
        return $this->belongsTo(self::class, 'reversal_of_id');
    }

    public function reversals()
    {
        return $this->hasMany(self::class, 'reversal_of_id');
    }

    public function source()
    {
        return $this->morphTo();
    }
}

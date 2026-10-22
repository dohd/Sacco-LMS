<?php

namespace App\Models\Shares;

use App\Models\ModelTrait;
use App\Models\User;
use Auth;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShareTransaction extends Model
{
    use HasFactory, ModelTrait;

    protected $guarded = ['id'];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        // fetch only member records
        static::addGlobalScope('member_id', function($query) {
            if (Auth::user() && Auth::user()->hasRoleCode('member')) {
                $member = optional(Auth()->user()->member);
                $query->whereHas('shareAccount', fn($subq) => $subq->where('member_id', $member->id));
            }
        });
    }

    /**
     * Getters
     * */
    public function getActionButtonsAttribute()
    {
        return $this->getButtonWrapperAttribute(
            $this->getViewButtonAttribute('share_transactions.show', 'manage-share-transaction'),
            $this->status === 'pending' ? $this->getEditButtonAttribute('share_transactions.edit', 'confirm-share-transaction') : null,
            null,
        );
    }

    /**
     * Relationships
     * */
    public function shareAccount()
    {
        return $this->belongsTo(ShareAccount::class, 'share_account_id');
    }

    public function reversalOf()
    {
        return $this->belongsTo(self::class, 'reversal_of_id');
    }

    public function reversal()
    {
        return $this->hasOne(self::class, 'reversal_of_id');
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}

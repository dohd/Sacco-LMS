<?php

namespace App\Models\Shares;

use App\Models\Memberships\Member;
use App\Models\ModelTrait;
use Auth;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShareAccount extends Model
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
                $query->where('member_id', $member->id);
            }
        });
    }

    /**
     * Getters
     * */
    public function getActionButtonsAttribute()
    {
        return $this->getButtonWrapperAttribute(
            $this->getViewButtonAttribute('share_accounts.show', 'view-own-shares'),
            $this->getEditButtonAttribute('share_accounts.edit', 'manage-share-accounts'),
            null,
        );
    }

    /**
     * Relationship
     * */
    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function shareProduct()
    {
        return $this->belongsTo(ShareProduct::class);
    }
}

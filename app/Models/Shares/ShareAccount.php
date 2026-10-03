<?php

namespace App\Models\Shares;

use App\Models\Memberships\Member;
use App\Models\ModelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShareAccount extends Model
{
    use HasFactory, ModelTrait;

    protected $guarded = ['id'];


    /**
     * Getters
     * */
    public function getActionButtonsAttribute()
    {
        return $this->getButtonWrapperAttribute(
            $this->getViewButtonAttribute('share_accounts.show', null),
            $this->getEditButtonAttribute('share_accounts.edit', null),
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

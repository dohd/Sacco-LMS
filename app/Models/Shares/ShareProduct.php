<?php

namespace App\Models\Shares;

use App\Models\Accounting\ChartOfAccount;
use App\Models\ModelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShareProduct extends Model
{
    use HasFactory, ModelTrait;

    protected $guarded = ['id'];

    /**
     * Getters
     * */
    public function getActionButtonsAttribute()
    {
        return $this->getButtonWrapperAttribute(
            $this->getViewButtonAttribute('share_products.show', ['view-share-products', 'manage-share-products']),
            $this->getEditButtonAttribute('share_products.edit', 'edit-share-products'),
            null,
        );
    }

    /**
     * Relationship
     * */
    public function shareCapitalAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'share_capital_account_id');
    }

    public function sharePremiumAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'share_premium_account_id');
    }
}

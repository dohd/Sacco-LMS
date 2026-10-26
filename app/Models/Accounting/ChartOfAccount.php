<?php

namespace App\Models\Accounting;

use App\Models\ModelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChartOfAccount extends Model
{
    use HasFactory, ModelTrait;

    protected $guarded = ['id'];

    protected $casts = [
        'is_control_account' => 'boolean',
        'is_postable'        => 'boolean',
        'is_active'          => 'boolean',
    ];


    /**
     * Getters
     * */
    public function getActionButtonsAttribute()
    {
        return $this->getButtonWrapperAttribute(
            $this->getViewButtonAttribute('chart_of_accounts.show', 'view-chart-of-accounts'),
            $this->getEditButtonAttribute('chart_of_accounts.edit', 'edit-chart-of-accounts'),
            null,
        );
    }

    /**
     * Relationships
     * */
    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}

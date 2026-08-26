<?php

namespace App\Models\LoanApplications;

use App\Models\ModelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanProduct extends Model
{
    use HasFactory, ModelTrait;

    protected $guarded = [];

    protected $casts = [
        'requires_guarantors' => 'boolean',
        'allows_top_up' => 'boolean',
        'allows_refinancing' => 'boolean',
        'is_active' => 'boolean',
        'eligibility_rules' => 'array',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    /**
     * Getters
     * */
    public function getActionButtonsAttribute()
    {
        return $this->getButtonWrapperAttribute(
            $this->getViewButtonAttribute('loan_products.show', null),
            $this->getEditButtonAttribute('loan_products.edit', null),
            null,
        );
    }
}

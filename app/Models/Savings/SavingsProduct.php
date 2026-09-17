<?php

namespace App\Models\Savings;

use App\Models\Accounting\ChartOfAccount;
use App\Models\ModelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SavingsProduct extends Model
{
    use HasFactory, ModelTrait;

    protected $guarded = ['id'];

    protected $casts = [
        'interest_rate' => 'decimal:4',
        'premature_withdrawal_penalty_percentage' => 'decimal:4',
        'maximum_balance' => 'decimal:2',
        'minimum_balance' => 'decimal:2',
        'minimum_monthly_contribution' => 'decimal:2',
        'allows_premature_withdrawal' => 'boolean',
        'auto_rollover' => 'boolean',
        'allows_partial_withdrawals' => 'boolean',
        'allows_withdrawals' => 'boolean',
        'can_secure_loan' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Getters
     * */
    public function getActionButtonsAttribute()
    {
        return $this->getButtonWrapperAttribute(
            $this->getViewButtonAttribute('savings_products.show', null),
            $this->getEditButtonAttribute('savings_products.edit', null),
            null,
        );
    }

    /**
     * Relationship
     * */
    public function savingsControlAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'savings_control_account_id');
    }

    public function interestExpenseAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'interest_expense_account_id');
    }

    public function feeIncomeAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'fee_income_account_id');
    }    
}

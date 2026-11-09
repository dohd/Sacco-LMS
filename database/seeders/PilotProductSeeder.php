<?php

namespace Database\Seeders;

use App\Models\LoanApplications\LoanProduct;
use App\Models\Savings\SavingsProduct;
use App\Models\Shares\ShareProduct;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Products for the pilot, derived from the member, savings and loan files.
 *
 *   php artisan db:seed --class=PilotProductSeeder
 *
 * Safe to run again: each product is matched on its code and updated in place.
 * Ledger accounts are looked up by code in the chart of accounts, and the seeder
 * stops before writing anything if one of them is missing.
 */
class PilotProductSeeder extends Seeder
{
    /** Chart of accounts codes the products are mapped to. */
    private const ACCOUNTS = [
        'loan_principal' => '1301',
        'interest_receivable' => '1302',
        'fee_receivable' => '1303',
        'penalty_receivable' => '1304',
        'savings_register' => '2101',
        'unearned_interest' => '2206',
        'insurance_payable' => '2207',
        'share_capital' => '3001',
        'share_premium' => '3004',
        'interest_income' => '4001',
        'penalty_income' => '4004',
        'processing_fee_income' => '4005',
        'withdrawal_fee_income' => '4006',
        'savings_interest_expense' => '5002',
        'write_off_expense' => '5004',
    ];

    public function run(): void
    {
        $accounts = $this->resolveAccounts();

        DB::transaction(function () use ($accounts) {
            $this->savingsProduct($accounts);
            $this->shareProduct($accounts);
            $this->loanProducts($accounts);
        });
    }

    /** @return array<string,int> */
    private function resolveAccounts(): array
    {
        $found = DB::table('chart_of_accounts')->whereIn('code', array_values(self::ACCOUNTS))
            ->where('is_active', true)->where('is_postable', true)->pluck('id', 'code');

        $missing = [];
        $accounts = [];
        foreach (self::ACCOUNTS as $key => $code) {
            if (!isset($found[$code])) {
                $missing[] = $code;
                continue;
            }
            $accounts[$key] = (int) $found[$code];
        }
        if ($missing) {
            throw new RuntimeException('Create these active, postable accounts in the chart of accounts first: ' . implode(', ', $missing) . '.');
        }

        return $accounts;
    }

    /** Member deposits: monthly contributions, not withdrawable, 5.07% a year credited annually. */
    private function savingsProduct(array $a): void
    {
        SavingsProduct::updateOrCreate(['code' => 'BOSA-DEP-01'], [
            'name' => 'Non-Withdrawable Member Deposits',
            'product_type' => 'compulsory',
            'savings_control_account_id' => $a['savings_register'],
            'interest_expense_account_id' => $a['savings_interest_expense'],
            'fee_income_account_id' => $a['withdrawal_fee_income'],
            'interest_rate' => 5.07,
            'interest_frequency' => 'annual',
            'interest_calculation_method' => 'simple',
            'allows_withdrawals' => false,
            'allows_partial_withdrawals' => false,
            'allows_premature_withdrawal' => false,
            'minimum_monthly_contribution' => 1000,
            'can_secure_loan' => true,
            'is_active' => true,
        ]);
    }

    /** Shares in units of 100, so holdings of 500, 600, 1,000, 1,500 and 2,000 are whole units. */
    private function shareProduct(array $a): void
    {
        ShareProduct::updateOrCreate(['code' => 'SC'], [
            'name' => 'Share Capital',
            'description' => 'Member share capital, in units of KES 100.',
            'share_capital_account_id' => $a['share_capital'],
            'share_premium_account_id' => $a['share_premium'],
            'unit_value' => 100,
            'minimum_units' => 0,
            'maximum_units' => null,
            'dividend_eligible' => true,
            'allows_transfer' => false,
            'allows_redemption' => true,
            'can_secure_loan' => true,
            'is_active' => true,
        ]);
    }

    /**
     * Two loan products that differ only in how interest is recognised: LN-STD holds
     * it as unearned interest and releases it as installments fall due; LN-IMP
     * recognises it as the legacy files record it.
     */
    private function loanProducts(array $a): void
    {
        $common = [
            'loan_principal_account_id' => $a['loan_principal'],
            'interest_receivable_account_id' => $a['interest_receivable'],
            'fee_receivable_account_id' => $a['fee_receivable'],
            'interest_income_account_id' => $a['interest_income'],
            'penalty_income_account_id' => $a['penalty_income'],
            'penalty_receivable_account_id' => $a['penalty_receivable'],
            'processing_fee_income_account_id' => $a['processing_fee_income'],
            'insurance_payable_account_id' => $a['insurance_payable'],
            'write_off_expense_account_id' => $a['write_off_expense'],
            'minimum_amount' => 0,
            'maximum_amount' => null,
            'minimum_repayment_months' => 1,
            'maximum_repayment_months' => 72,
            'interest_rate' => 12,
            'interest_method' => 'reducing_balance',
            'interest_frequency' => 'annual',
            'processing_fee_percentage' => 0,
            'insurance_fee_percentage' => 1,
            'penalty_rate' => 0,
            'is_active' => true,
        ];

        LoanProduct::updateOrCreate(['code' => 'LN-STD'], $common + [
            'name' => 'Member Loan',
            'description' => 'Standard member loan: 12% a year reducing balance, 1% insurance. Interest is held as unearned and released as installments fall due.',
            'unearned_interest_account_id' => $a['unearned_interest'],
            'requires_guarantors' => true,
        ]);

        LoanProduct::updateOrCreate(['code' => 'LN-IMP'], $common + [
            'name' => 'Member Loan (Imported)',
            'description' => 'Loans brought in from the legacy files. Interest is recognised as the files record it.',
            'unearned_interest_account_id' => null,
            'requires_guarantors' => false,
        ]);
    }
}

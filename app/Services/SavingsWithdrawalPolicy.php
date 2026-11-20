<?php

namespace App\Services;

use App\Models\Savings\SavingsProduct;
use App\Models\Savings\SavingsAccount;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class SavingsWithdrawalPolicy
{
    public function validate(SavingsProduct $product, $submittedAt, ?SavingsAccount $account = null, float $amount = 0, bool $checkNotice = true): float
    {
        if ($product->product_type === 'fixed_deposit') {
            if (!$account) {
                throw ValidationException::withMessages(['amount' => 'The fixed-deposit account is required to validate this withdrawal.']);
            }

            $today = Carbon::today();
            $matured = $account->fixed_deposit_matured_at
                || ($account->fixed_deposit_maturity_date && Carbon::parse($account->fixed_deposit_maturity_date)->lte($today));
            $noticeDays = (int) $product->withdrawal_notice_days;
            if ($checkNotice && $noticeDays > 0 && (!$submittedAt || now()->lt(Carbon::parse($submittedAt)->addDays($noticeDays)))) {
                throw ValidationException::withMessages(['amount' => 'The withdrawal notice period has not elapsed.']);
            }
            $balance = max(0, round((float) $account->ledger_balance - (float) $account->held_balance, 2));
            $available = $matured ? $balance : round((float) $account->available_balance, 2);
            $amount = round($amount, 2);

            if ($matured) {
                if (!$product->allows_partial_withdrawals && $amount + 0.01 < $available) {
                    throw ValidationException::withMessages(['amount' => 'This fixed deposit requires a full withdrawal at maturity.']);
                }
                return 0.0;
            }

            // Interest left outside a principal-only rollover remains accessible.
            if ($amount <= $available) {
                return 0.0;
            }
            if (!$product->allows_premature_withdrawal) {
                throw ValidationException::withMessages(['amount' => 'The fixed deposit principal is locked until maturity.']);
            }
            if ($amount > $balance) {
                throw ValidationException::withMessages(['amount' => 'The withdrawal exceeds the fixed-deposit balance.']);
            }
            if (!$product->allows_partial_withdrawals && $amount + 0.01 < $balance) {
                throw ValidationException::withMessages(['amount' => 'Premature withdrawal requires closing the full fixed deposit.']);
            }

            $principalWithdrawal = max(0, $amount - $available);

            return round($principalWithdrawal * (float) $product->premature_withdrawal_penalty_percentage / 100, 2);
        }

        // Product retirement closes new enrollment; account status controls existing access.
        if (!$product->allows_withdrawals) {
            throw ValidationException::withMessages(['amount' => 'Withdrawals are not permitted for this product.']);
        }
        $noticeDays = (int) $product->withdrawal_notice_days;
        if ($noticeDays > 0 && (!$submittedAt || now()->lt(Carbon::parse($submittedAt)->addDays($noticeDays)))) {
            throw ValidationException::withMessages(['amount' => 'The withdrawal notice period has not elapsed.']);
        }

        return 0.0;
    }
}

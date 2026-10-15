<?php

namespace App\Services;

use App\Models\Savings\SavingsProduct;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class SavingsWithdrawalPolicy
{
    public function validate(SavingsProduct $product, $submittedAt): void
    {
        // Product retirement closes new enrollment; account status controls existing access.
        if (!$product->allows_withdrawals) {
            throw ValidationException::withMessages(['amount' => 'Withdrawals are not permitted for this product.']);
        }
        if ($product->product_type === 'fixed_deposit') {
            throw ValidationException::withMessages(['amount' => 'Fixed-deposit withdrawals require a maturity workflow.']);
        }
        $noticeDays = (int) $product->withdrawal_notice_days;
        if ($noticeDays > 0 && (!$submittedAt || now()->lt(Carbon::parse($submittedAt)->addDays($noticeDays)))) {
            throw ValidationException::withMessages(['amount' => 'The withdrawal notice period has not elapsed.']);
        }
    }
}

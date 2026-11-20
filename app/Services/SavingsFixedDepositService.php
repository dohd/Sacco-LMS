<?php

namespace App\Services;

use App\Models\Savings\SavingsAccount;
use App\Models\Savings\SavingsFixedDepositMaturity;
use App\Models\Savings\SavingsTransaction;
use App\Models\Savings\SavingsWithdrawal;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SavingsFixedDepositService
{
    private $postingService;

    public function __construct(JournalPostingService $postingService)
    {
        $this->postingService = $postingService;
    }

    /** Process all fixed deposits whose contract matured on or before $asOf. */
    public function processMaturities(Carbon $asOf, int $actorId): array
    {
        $result = ['matured' => 0, 'rolled_over' => 0, 'interest' => 0.0, 'failed' => 0];
        $ids = SavingsAccount::whereNotNull('fixed_deposit_maturity_date')
            ->whereNull('fixed_deposit_terminated_at')
            ->whereNull('fixed_deposit_matured_at')
            ->whereIn('status', ['active', 'frozen'])
            ->whereHas('savingsProduct', fn($query) => $query
                ->where('product_type', 'fixed_deposit')
                ->where('interest_frequency', 'at_maturity'))
            ->whereDate('fixed_deposit_maturity_date', '<=', $asOf->toDateString())
            ->orderBy('id')->pluck('id');

        foreach ($ids as $id) {
            try {
                $outcome = DB::transaction(function () use ($id, $asOf, $actorId) {
                    $account = SavingsAccount::whereKey($id)->lockForUpdate()->first();
                    if (!$account || !$account->fixed_deposit_maturity_date || $account->fixed_deposit_terminated_at) {
                        return null;
                    }

                    $maturityDate = Carbon::parse($account->fixed_deposit_maturity_date);
                    if ($maturityDate->gt($asOf)) {
                        return null;
                    }
                    if (SavingsFixedDepositMaturity::where('savings_account_id', $account->id)
                        ->whereDate('maturity_date', $maturityDate->toDateString())->exists()) {
                        return null;
                    }

                    $product = $account->savingsProduct;
                    if (!$product || $product->product_type !== 'fixed_deposit') {
                        return null;
                    }

                    $principal = round((float) $account->fixed_deposit_locked_principal, 2);
                    $interest = $this->interestForTerm($account, $maturityDate);
                    $newBalance = round((float) $account->ledger_balance + $interest, 2);
                    $hasOpenWithdrawal = SavingsWithdrawal::where('savings_account_id', $account->id)
                        ->whereIn('status', ['pending', 'approved'])->exists();

                    if ($interest > 0) {
                        $transaction = SavingsTransaction::create([
                            'savings_account_id' => $account->id,
                            'transaction_number' => $this->transactionNumber(),
                            'transaction_type' => 'interest',
                            'direction' => 'credit',
                            'amount' => $interest,
                            'running_balance' => $newBalance,
                            'transaction_date' => $this->postingDate($maturityDate)->toDateString(),
                            'value_date' => $maturityDate->toDateString(),
                            'payment_method' => 'system',
                            'external_reference' => 'fixed-deposit-interest:' . $account->id . ':' . $maturityDate->toDateString(),
                            'status' => 'confirmed',
                            'recorded_by' => $actorId,
                            'confirmed_by' => $actorId,
                            'confirmed_at' => now(),
                            'description' => 'Fixed-deposit interest at maturity on ' . $maturityDate->toDateString(),
                        ]);
                        $account->ledger_balance = $newBalance;
                        $account->last_transaction_date = $transaction->transaction_date;
                        $this->postingService->postSavingsMovement($transaction);
                    }

                    $nextMaturity = null;
                    $rolledOver = 0.0;
                    $availableAtMaturity = 0.0;
                    $willRollover = $account->fixed_deposit_auto_rollover && !$hasOpenWithdrawal;
                    if ($willRollover) {
                        $nextStart = $maturityDate->copy();
                        $nextMaturity = $nextStart->copy()->addMonthsNoOverflow((int) $account->fixed_deposit_term_months);
                        $account->fixed_deposit_term_start_date = $nextStart->toDateString();
                        $account->fixed_deposit_maturity_date = $nextMaturity->toDateString();
                        $account->fixed_deposit_interest_rate = $product->interest_rate;
                        $account->fixed_deposit_calculation_method = $product->interest_calculation_method;

                        if ($account->fixed_deposit_rollover_option === 'principal_and_interest') {
                            $rolledOver = $newBalance;
                            $account->fixed_deposit_locked_principal = $newBalance;
                        } else {
                            $rolledOver = min($principal, $newBalance);
                            $account->fixed_deposit_locked_principal = $rolledOver;
                            $availableAtMaturity = max(0, round($newBalance - $rolledOver - (float) $account->held_balance, 2));
                        }
                    } else {
                        $account->fixed_deposit_matured_at = $maturityDate->toDateString();
                        $account->fixed_deposit_locked_principal = 0;
                        $availableAtMaturity = max(0, round($newBalance - (float) $account->held_balance, 2));
                    }

                    $account->available_balance = max(0, round(
                        $newBalance - (float) $account->held_balance - (float) $account->fixed_deposit_locked_principal,
                        2
                    ));
                    $account->save();

                    SavingsFixedDepositMaturity::create([
                        'savings_account_id' => $account->id,
                        'maturity_date' => $maturityDate->toDateString(),
                        'principal_amount' => $principal,
                        'interest_amount' => $interest,
                        'rollover_option' => $willRollover ? $account->fixed_deposit_rollover_option : null,
                        'rolled_over_amount' => $rolledOver,
                        'available_balance' => $availableAtMaturity,
                        'next_maturity_date' => $nextMaturity ? $nextMaturity->toDateString() : null,
                        'processed_by' => $actorId,
                    ]);

                    return ['interest' => $interest, 'rolled_over' => (bool) $nextMaturity];
                });

                if ($outcome) {
                    $result['matured']++;
                    $result['rolled_over'] += $outcome['rolled_over'] ? 1 : 0;
                    $result['interest'] = round($result['interest'] + $outcome['interest'], 2);
                }
            } catch (\Throwable $e) {
                $result['failed']++;
                \Log::error('Fixed-deposit maturity processing failed for account ' . $id . ': ' . $e->getMessage());
            }
        }

        return $result;
    }

    private function interestForTerm(SavingsAccount $account, Carbon $maturityDate): float
    {
        $principal = max(0, (float) $account->fixed_deposit_locked_principal);
        $rate = max(0, (float) $account->fixed_deposit_interest_rate) / 100;
        $start = Carbon::parse($account->fixed_deposit_term_start_date);
        $days = max(0, $start->diffInDays($maturityDate));
        if ($principal <= 0 || $rate <= 0 || $days <= 0) {
            return 0.0;
        }

        $interest = $account->fixed_deposit_calculation_method === 'compound'
            ? $principal * (pow(1 + $rate, $days / 365) - 1)
            : $principal * $rate * $days / 365;

        return round($interest, 2);
    }

    private function postingDate(Carbon $maturityDate): Carbon
    {
        $isOpen = DB::table('accounting_periods')->where('status', 'open')
            ->whereDate('start_date', '<=', $maturityDate->toDateString())
            ->whereDate('end_date', '>=', $maturityDate->toDateString())->exists();

        return $isOpen ? $maturityDate->copy() : Carbon::today();
    }

    private function transactionNumber(): string
    {
        do {
            $number = 'SAV-TRX-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(6));
        } while (SavingsTransaction::where('transaction_number', $number)->exists());

        return $number;
    }
}

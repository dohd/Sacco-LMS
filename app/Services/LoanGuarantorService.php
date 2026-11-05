<?php

namespace App\Services;

use App\Models\LoanApplications\Loan;
use App\Models\LoanApplications\LoanApplication;
use App\Models\LoanApplications\LoanGuarantor;
use App\Models\LoanApplications\LoanGuarantorHold;
use App\Models\LoanApplications\LoanRepayment;
use App\Models\LoanApplications\LoanRepaymentSchedule;
use App\Models\Shares\ShareTransaction;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Turns a recorded guarantee into an enforceable one by holding the
 * guarantor's shares for the life of the loan.
 *
 * Every method that moves share balances must run inside a transaction.
 */
class LoanGuarantorService
{
    /**
     * Guarantors must have consented and signed before a loan is approved.
     */
    public function assertConsented(LoanApplication $application): void
    {
        $blocked = $application->guarantors
            ->filter(fn($guarantor) => $guarantor->status !== 'accepted' || !$guarantor->signature);

        if ($blocked->isNotEmpty()) {
            throw ValidationException::withMessages([
                'guarantors' => 'Every guarantor must accept and sign before approval. Pending: '
                    . $blocked->pluck('guarantor_name')->implode(', ') . '.',
            ]);
        }
    }

    /**
     * Hold the shares of every accepted guarantor against the new loan.
     */
    public function placeHolds(LoanApplication $application, Loan $loan): void
    {
        foreach ($application->guarantors as $guarantor) {
            $this->holdGuarantor($guarantor, $loan);
        }
    }

    public function holdGuarantor(LoanGuarantor $guarantor, Loan $loan): void
    {
        $amount = round((float) $guarantor->shares_offered, 2);
        $remaining = $amount;

        $accounts = DB::table('share_accounts')
            ->where('member_id', $guarantor->member_id)
            ->where('status', 'active')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($accounts as $account) {
            if ($remaining <= 0) {
                break;
            }
            $free = round((float) $account->share_balance - (float) $account->held_amount, 2);
            $take = round(min($remaining, $free), 2);
            if ($take <= 0) {
                continue;
            }
            $newHeld = round((float) $account->held_amount + $take, 2);
            DB::table('share_accounts')->where('id', $account->id)->update([
                'held_amount' => $newHeld,
                'available_amount' => max(0, round((float) $account->share_balance - $newHeld, 2)),
                'updated_at' => now(),
            ]);
            LoanGuarantorHold::create([
                'loan_guarantor_id' => $guarantor->id,
                'loan_id' => $loan->id,
                'share_account_id' => $account->id,
                'amount' => $take,
                'status' => 'active',
            ]);
            $remaining = round($remaining - $take, 2);
        }

        if ($remaining > 0) {
            throw ValidationException::withMessages([
                'guarantors' => $guarantor->guarantor_name . ' no longer has enough available shares to guarantee this loan.',
            ]);
        }

        $guarantor->update([
            'status' => 'held',
            'loan_id' => $loan->id,
            'held_amount' => $amount,
            'released_at' => null,
            'released_by' => null,
        ]);
    }

    /**
     * Release one guarantor's holds. The final status is 'released' when the
     * loan is complete and 'replaced' when another guarantor takes over.
     */
    public function releaseGuarantor(LoanGuarantor $guarantor, string $finalStatus = 'released'): void
    {
        $holds = LoanGuarantorHold::where('loan_guarantor_id', $guarantor->id)
            ->where('status', 'active')
            ->lockForUpdate()
            ->get();

        foreach ($holds as $hold) {
            $account = DB::table('share_accounts')->where('id', $hold->share_account_id)->lockForUpdate()->first();
            if ($account) {
                $newHeld = max(0, round((float) $account->held_amount - (float) $hold->amount, 2));
                DB::table('share_accounts')->where('id', $account->id)->update([
                    'held_amount' => $newHeld,
                    'available_amount' => max(0, round((float) $account->share_balance - $newHeld, 2)),
                    'updated_at' => now(),
                ]);
            }
            $hold->update(['status' => 'released', 'released_at' => now()]);
        }

        $guarantor->update([
            'status' => $finalStatus,
            'held_amount' => 0,
            'released_at' => now(),
            'released_by' => Auth::id(),
        ]);
    }

    /**
     * Release every held guarantee on a loan that has been fully repaid.
     */
    public function releaseForLoan(Loan $loan): void
    {
        LoanGuarantor::where('loan_id', $loan->id)->where('status', 'held')->lockForUpdate()->get()
            ->each(fn($guarantor) => $this->releaseGuarantor($guarantor));
    }

    /**
     * Re-hold guarantees released by full repayment when a repayment is
     * reversed. Best effort: a guarantor who has since spent the shares is
     * logged for staff follow-up rather than blocking the reversal.
     */
    public function reinstateForLoan(Loan $loan): void
    {
        LoanGuarantor::where('loan_id', $loan->id)->where('status', 'released')->lockForUpdate()->get()
            ->each(function ($guarantor) use ($loan) {
                try {
                    DB::transaction(fn() => $this->holdGuarantor($guarantor, $loan));
                } catch (ValidationException $e) {
                    Log::warning("Guarantee {$guarantor->id} on loan {$loan->id} could not be re-held after repayment reversal.");
                }
            });
    }

    /**
     * Call a guarantee on a defaulting loan.
     *
     * The guarantor's held shares are redeemed, whole units only, and applied
     * to the loan as a repayment (penalty, interest, fees, then principal,
     * oldest installment first). Only what is held can be taken, and never more
     * than the loan's open installments. With no amount given, the loan's
     * arrears are called, or everything owed when the loan has none.
     *
     * @return array{repayment: LoanRepayment, amount: float}
     */
    public function invoke(LoanGuarantor $guarantor, ?float $requested, string $reason, string $date): array
    {
        return DB::transaction(function () use ($guarantor, $requested, $reason, $date) {
            $guarantor = LoanGuarantor::whereKey($guarantor->id)->lockForUpdate()->firstOrFail();
            if ($guarantor->status !== 'held' || !$guarantor->loan_id) {
                throw ValidationException::withMessages(['guarantee' => 'Only a guarantee that is currently held can be invoked.']);
            }

            $loan = Loan::whereKey($guarantor->loan_id)->lockForUpdate()->firstOrFail();
            if (!in_array($loan->status, ['active', 'in_arrears'], true)) {
                throw ValidationException::withMessages(['guarantee' => 'A guarantee can only be invoked on an active loan.']);
            }
            if (LoanRepayment::where('loan_id', $loan->id)->where('status', 'pending')->exists()) {
                throw ValidationException::withMessages(['guarantee' => 'Confirm or cancel the pending repayments before invoking a guarantee.']);
            }

            $owed = round((float) LoanRepaymentSchedule::where('loan_id', $loan->id)
                ->whereIn('status', ['pending', 'partially_paid', 'overdue'])
                ->sum('outstanding_amount'), 2);
            if ($owed <= 0) {
                throw ValidationException::withMessages(['guarantee' => 'Nothing is owed on this loan.']);
            }

            $target = $requested !== null ? round($requested, 2)
                : (round((float) $loan->arrears_amount, 2) > 0 ? round((float) $loan->arrears_amount, 2) : $owed);
            if ($target <= 0) {
                throw ValidationException::withMessages(['amount' => 'Enter an amount greater than zero.']);
            }
            if ($target > $owed) {
                throw ValidationException::withMessages(['amount' => 'The amount is more than the ' . number_format($owed, 2) . ' owed on the loan.']);
            }

            $holds = LoanGuarantorHold::where('loan_guarantor_id', $guarantor->id)
                ->where('status', 'active')->orderBy('id')->lockForUpdate()->get();

            $remaining = $target;
            $redemptions = [];
            foreach ($holds as $hold) {
                if ($remaining <= 0) {
                    break;
                }
                $account = \App\Models\Shares\ShareAccount::with('shareProduct')->whereKey($hold->share_account_id)->lockForUpdate()->firstOrFail();
                $unitValue = round((float) $account->shareProduct->unit_value, 2);
                if ($unitValue <= 0) {
                    continue;
                }
                $units = (int) floor(round(min($remaining, (float) $hold->amount) / $unitValue, 6));
                $units = min($units, (int) $account->total_units);
                if ($units < 1) {
                    continue;
                }
                $amount = round($units * $unitValue, 2);

                $newUnits = (int) $account->total_units - $units;
                $newBalance = round((float) $account->share_balance - $amount, 2);
                $newHeld = max(0, round((float) $account->held_amount - $amount, 2));

                $redemptions[] = ShareTransaction::create([
                    'share_account_id' => $account->id,
                    'transaction_number' => $this->shareTransactionNumber(),
                    'transaction_type' => 'redemption',
                    'direction' => 'debit',
                    'units' => $units,
                    'unit_value' => $unitValue,
                    'amount' => $amount,
                    'running_units' => $newUnits,
                    'running_balance' => $newBalance,
                    'transaction_date' => $date,
                    'value_date' => $date,
                    'payment_method' => 'system',
                    'status' => 'confirmed',
                    'recorded_by' => Auth::id(),
                    'confirmed_by' => Auth::id(),
                    'confirmed_at' => now(),
                    'loan_guarantor_id' => $guarantor->id,
                    'description' => 'Shares applied to loan ' . $loan->loan_number . ' under guarantee',
                ]);

                $account->update([
                    'total_units' => $newUnits,
                    'share_balance' => $newBalance,
                    'held_amount' => $newHeld,
                    'available_amount' => max(0, round($newBalance - $newHeld, 2)),
                ]);

                $left = round((float) $hold->amount - $amount, 2);
                $hold->update($left > 0
                    ? ['amount' => $left]
                    : ['amount' => 0, 'status' => 'invoked', 'released_at' => now()]);

                $remaining = round($remaining - $amount, 2);
            }

            $taken = round($target - $remaining, 2);
            if ($taken <= 0) {
                throw ValidationException::withMessages(['guarantee' => 'The guarantor has no whole share units available to apply.']);
            }

            $stillHeld = round((float) LoanGuarantorHold::where('loan_guarantor_id', $guarantor->id)->where('status', 'active')->sum('amount'), 2);
            $guarantor->update([
                'status' => $stillHeld > 0 ? 'held' : 'invoked',
                'held_amount' => $stillHeld,
                'invoked_at' => now(),
                'invoked_by' => Auth::id(),
                'invoked_amount' => round((float) $guarantor->invoked_amount + $taken, 2),
                'invocation_reason' => $reason,
            ]);

            $repayment = LoanRepayment::create([
                'loan_id' => $loan->id,
                'member_id' => $loan->member_id,
                'repayment_number' => $this->repaymentNumber(),
                'payment_date' => $date,
                'value_date' => $date,
                'amount_paid' => $taken,
                'principal_amount' => 0,
                'interest_amount' => 0,
                'penalty_amount' => 0,
                'fees_amount' => 0,
                'unallocated_amount' => 0,
                'payment_method' => 'guarantee',
                'payer_name' => $guarantor->guarantor_name,
                'recorded_by' => Auth::id(),
                'status' => 'pending',
                'loan_guarantor_id' => $guarantor->id,
                'remarks' => 'Guarantee invoked: ' . $reason,
            ]);

            $repayment = app(LoanRepaymentConfirmationService::class)->confirm($repayment->id, false);
            app(JournalPostingService::class)->postGuaranteeInvocation($repayment, $redemptions);

            return ['repayment' => $repayment, 'amount' => $taken];
        });
    }

    private function shareTransactionNumber(): string
    {
        do {
            $number = 'SHA-TRX-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(6));
        } while (ShareTransaction::where('transaction_number', $number)->exists());

        return $number;
    }

    private function repaymentNumber(): string
    {
        do {
            $number = 'RP-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(6));
        } while (LoanRepayment::where('repayment_number', $number)->exists());

        return $number;
    }
}

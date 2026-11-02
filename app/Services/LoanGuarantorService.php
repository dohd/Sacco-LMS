<?php

namespace App\Services;

use App\Models\LoanApplications\Loan;
use App\Models\LoanApplications\LoanApplication;
use App\Models\LoanApplications\LoanGuarantor;
use App\Models\LoanApplications\LoanGuarantorHold;
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
}

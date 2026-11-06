<?php

namespace App\Services;

use App\Models\Accounting\AccountingPeriod;
use App\Models\Accounting\ChartOfAccount;
use App\Models\Accounting\JournalEntry;
use App\Models\Accounting\JournalEntryLine;
use App\Models\LoanApplications\Loan;
use App\Models\LoanApplications\LoanDisbursement;
use App\Models\LoanApplications\LoanRepayment;
use App\Models\LoanApplications\LoanRepaymentSchedule;
use App\Models\Savings\SavingsTransaction;
use App\Models\Shares\ShareAccount;
use App\Models\Shares\ShareTransaction;
use App\Models\Savings\SavingsWithdrawal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class JournalPostingService
{
    /**
     * Post a processed loan disbursement to the general ledger.
     *
     *   Dr  Loan principal                                  amount paid out
     *   Dr  Interest receivable                             interest charged on the loan
     *   Dr  Fee receivable                                  fees capitalised into the loan
     *   Cr  Cash / bank account chosen on the disbursement  amount paid out
     *       (member savings control for account_credit)
     *   Cr  Interest income                                 interest charged on the loan
     *   Cr  Processing fee income                           fees capitalised into the loan
     *
     * Fees are recognised in full when the loan is disbursed. Interest is too,
     * unless the product has an unearned interest account: then the credit goes
     * to that liability instead and is released to interest income as each
     * installment falls due (see postLoanInterestRelease). Either way the
     * receivables are collected as repayments arrive.
     */
    public function postLoanDisbursement(LoanDisbursement $disbursement): JournalEntry
    {
        return DB::transaction(function () use ($disbursement) {
            $disbursement = LoanDisbursement::with('loan.loanApplication.loanProduct')
                ->whereKey($disbursement->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($disbursement->status !== 'processed') {
                throw ValidationException::withMessages([
                    'disbursement' => 'Only a processed disbursement can be posted to the ledger.',
                ]);
            }

            $loan = $disbursement->loan;
            $product = optional(optional($loan)->loanApplication)->loanProduct;
            if (!$loan || !$product) {
                throw ValidationException::withMessages([
                    'disbursement' => 'The loan product for this disbursement could not be found.',
                ]);
            }

            if (round((float) $disbursement->deductions_amount, 2) > 0) {
                throw ValidationException::withMessages([
                    'disbursement' => 'Disbursements with deductions cannot be posted until a deductions account is configured.',
                ]);
            }

            $payout = round((float) $disbursement->net_amount, 2);
            $interest = round((float) $loan->interest_balance, 2);
            $fees = round((float) $loan->fees_due, 2);
            if ($payout <= 0) {
                throw ValidationException::withMessages(['disbursement' => 'The disbursement has no amount to post.']);
            }

            $source = $disbursement->disbursement_method === 'account_credit'
                ? $this->savingsRegisterAccount()
                : ChartOfAccount::find($disbursement->disbursement_account_id);
            if (!$source) {
                throw ValidationException::withMessages([
                    'disbursement' => $disbursement->disbursement_method === 'account_credit'
                        ? 'The individual savings register account is not configured.'
                        : 'Select the cash or bank ledger account the loan was paid from.',
                ]);
            }

            $lines = [
                ['account' => $product->loan_principal_account_id, 'debit' => $payout, 'description' => 'Loan principal disbursed'],
                ['account' => $source->id, 'credit' => $payout, 'description' => 'Loan paid out'],
            ];
            // With an unearned interest account the interest is held as a liability and released
            // to income as installments fall due; without one it is recognised in full now.
            $deferInterest = $interest > 0 && $product->unearned_interest_account_id;
            if ($interest > 0) {
                $lines[] = ['account' => $product->interest_receivable_account_id, 'debit' => $interest, 'description' => 'Interest charged'];
                $lines[] = $deferInterest
                    ? ['account' => $product->unearned_interest_account_id, 'credit' => $interest, 'description' => 'Unearned interest']
                    : ['account' => $product->interest_income_account_id, 'credit' => $interest, 'description' => 'Interest income'];
            }
            if ($fees > 0) {
                $lines[] = ['account' => $product->fee_receivable_account_id, 'debit' => $fees, 'description' => 'Fees capitalised'];
                $lines[] = ['account' => $product->processing_fee_income_account_id, 'credit' => $fees, 'description' => 'Fee income'];
            }

            $entry = $this->postEntry([
                'posting_key' => 'loan_disbursement:' . $disbursement->id . ':processed',
                'event_type' => 'loan_disbursement',
                'source' => $disbursement,
                'transaction_date' => $disbursement->disbursement_date,
                'posting_date' => $disbursement->value_date ?: $disbursement->disbursement_date,
                'reference_number' => $disbursement->transaction_reference ?: $disbursement->disbursement_number,
                'description' => 'Loan disbursement ' . $disbursement->disbursement_number . ' for loan ' . $loan->loan_number,
                'created_by' => $disbursement->processed_by ?: $disbursement->approved_by,
                'member_id' => $loan->member_id,
                'loan_id' => $loan->id,
            ], $lines);

            if ($deferInterest && !$loan->interest_deferred) {
                $loan->update(['interest_deferred' => true]);
            }

            return $entry;
        });
    }

    /**
     * Reverse the journal posted for a disbursement. Disbursements processed
     * before ledger posting existed have no entry, so there is nothing to reverse.
     */
    public function reverseLoanDisbursement(LoanDisbursement $disbursement, string $reason): ?JournalEntry
    {
        // Interest already released to income (or reclassified) goes back first.
        $schedules = LoanRepaymentSchedule::where('loan_id', $disbursement->loan_id)->whereNotNull('interest_released_on')->get();
        foreach ($schedules as $schedule) {
            $this->reverseEntry(
                'loan_interest:' . $schedule->id . ':released',
                'loan_interest:' . $schedule->id . ':reversed',
                'loan_interest_release_reversal',
                $schedule,
                $reason,
                'Interest on installment ' . $schedule->installment_number
            );
        }
        $loan = Loan::find($disbursement->loan_id);
        if ($loan) {
            $this->reverseEntry(
                'loan_interest:' . $loan->id . ':reclassified',
                'loan_interest:' . $loan->id . ':reclassification_reversed',
                'loan_interest_reclassification_reversal',
                $loan,
                $reason,
                'Interest reclassification ' . $loan->loan_number
            );
        }
        LoanRepaymentSchedule::where('loan_id', $disbursement->loan_id)->update(['interest_released_on' => null]);

        return $this->reverseEntry(
            'loan_disbursement:' . $disbursement->id . ':processed',
            'loan_disbursement:' . $disbursement->id . ':reversed',
            'loan_disbursement_reversal',
            $disbursement,
            $reason,
            'Disbursement ' . $disbursement->disbursement_number
        );
    }

    /**
     * Post a confirmed loan repayment to the general ledger.
     *
     *   Dr  Cash / bank account chosen on the repayment     amount received
     *       (member savings control for account_credit)
     *   Cr  Loan principal                                  principal portion
     *   Cr  Interest receivable                             interest portion
     *   Cr  Fee receivable                                  fees portion
     *   Cr  Penalty income                                  penalty portion
     *
     * Safe to call twice: the posting key makes the repayment post once.
     */
    public function postLoanRepaymentReceipt(LoanRepayment $repayment): JournalEntry
    {
        return DB::transaction(function () use ($repayment) {
            $repayment = LoanRepayment::with('loan.loanApplication.loanProduct')
                ->whereKey($repayment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($repayment->status !== 'confirmed') {
                throw ValidationException::withMessages([
                    'repayment' => 'Only a confirmed repayment can be posted to the ledger.',
                ]);
            }

            $loan = $repayment->loan;
            $product = optional(optional($loan)->loanApplication)->loanProduct;
            if (!$loan || !$product) {
                throw ValidationException::withMessages([
                    'repayment' => 'The loan product for this repayment could not be found.',
                ]);
            }

            if ($repayment->payment_method === 'guarantee') {
                throw ValidationException::withMessages([
                    'repayment' => 'A repayment funded by a guarantee is posted by the guarantee invocation.',
                ]);
            }

            if (round((float) $repayment->unallocated_amount, 2) > 0) {
                throw ValidationException::withMessages([
                    'repayment' => 'Unallocated repayment amounts cannot be posted until an overpayment account is configured.',
                ]);
            }

            // Debit the account that received the money: the one picked on the
            // repayment, or the savings control account for account credit.
            $receiptAccount = $repayment->payment_method === 'account_credit'
                ? $this->savingsRegisterAccount()
                : ChartOfAccount::find($repayment->deposit_account_id);
            if (!$receiptAccount) {
                throw ValidationException::withMessages([
                    'repayment' => $repayment->payment_method === 'account_credit'
                        ? 'The individual savings register account is not configured.'
                        : 'Select the cash or bank ledger account that received this repayment.',
                ]);
            }

            $amountReceived = round((float) $repayment->amount_paid, 2);
            $parts = [
                ['account' => $product->loan_principal_account_id, 'amount' => $repayment->principal_amount, 'label' => 'Principal'],
                ['account' => $product->interest_receivable_account_id, 'amount' => $repayment->interest_amount, 'label' => 'Interest'],
                ['account' => $product->fee_receivable_account_id, 'amount' => $repayment->fees_amount, 'label' => 'Fees'],
                ['account' => $product->penalty_receivable_account_id, 'amount' => $repayment->penalty_amount, 'label' => 'Penalty'],
            ];
            $lines = [['account' => $receiptAccount->id, 'debit' => $amountReceived, 'description' => 'Repayment received']];
            foreach ($parts as $part) {
                if (round((float) $part['amount'], 2) > 0) {
                    $lines[] = ['account' => $part['account'], 'credit' => round((float) $part['amount'], 2), 'description' => $part['label'] . ' repaid'];
                }
            }
            if ($amountReceived <= 0 || count($lines) < 2) {
                throw ValidationException::withMessages(['repayment' => 'The repayment has no amount to post.']);
            }

            return $this->postEntry([
                'posting_key' => 'loan_repayment:' . $repayment->id . ':confirmed',
                'event_type' => 'loan_repayment',
                'source' => $repayment,
                'transaction_date' => $repayment->payment_date,
                'posting_date' => $repayment->value_date ?: $repayment->payment_date,
                'reference_number' => $repayment->transaction_reference ?: $repayment->receipt_number ?: $repayment->repayment_number,
                'description' => 'Loan repayment ' . $repayment->repayment_number . ' for loan ' . $loan->loan_number,
                'created_by' => $repayment->recorded_by,
                'member_id' => $repayment->member_id,
                'loan_id' => $repayment->loan_id,
            ], $lines);
        });
    }

    /**
     * Reverse the journal posted for a repayment. Repayments confirmed before
     * ledger posting existed have no entry, so there is nothing to reverse.
     */
    public function reverseLoanRepaymentReceipt(LoanRepayment $repayment, string $reason): ?JournalEntry
    {
        return $this->reverseEntry(
            'loan_repayment:' . $repayment->id . ':confirmed',
            'loan_repayment:' . $repayment->id . ':reversed',
            'loan_repayment_reversal',
            $repayment,
            $reason,
            'Repayment ' . $repayment->repayment_number
        );
    }

    /**
     * Post a guarantee invoked on a defaulting loan. The guarantor's shares
     * are redeemed and the proceeds repay the loan, so no cash moves.
     *
     *   Dr  Share capital (one line per share account)   shares redeemed
     *   Cr  Loan principal                               principal portion
     *   Cr  Interest receivable                          interest portion
     *   Cr  Fee receivable                               fees portion
     *   Cr  Penalty receivable                           penalty portion
     *
     * The share lines are tagged with the guarantor's member and share
     * account; the loan lines with the borrower's loan.
     *
     * @param ShareTransaction[] $redemptions
     */
    public function postGuaranteeInvocation(LoanRepayment $repayment, array $redemptions): JournalEntry
    {
        return DB::transaction(function () use ($repayment, $redemptions) {
            $repayment = LoanRepayment::with('loan.loanApplication.loanProduct')->whereKey($repayment->id)->lockForUpdate()->firstOrFail();
            $loan = $repayment->loan;
            $product = optional(optional($loan)->loanApplication)->loanProduct;
            if (!$loan || !$product) {
                throw ValidationException::withMessages(['repayment' => 'The loan product for this repayment could not be found.']);
            }

            if (round((float) $repayment->unallocated_amount, 2) > 0) {
                throw ValidationException::withMessages(['repayment' => 'The guarantee is larger than the amount owed on the loan.']);
            }

            $lines = [];
            $debits = 0.0;
            foreach ($redemptions as $redemption) {
                $account = ShareAccount::with('shareProduct')->findOrFail($redemption->share_account_id);
                $amount = round((float) $redemption->amount, 2);
                $lines[] = [
                    'account' => $account->shareProduct->share_capital_account_id,
                    'debit' => $amount,
                    'description' => 'Shares applied to loan ' . $loan->loan_number,
                    'member_id' => $account->member_id,
                    'loan_id' => null,
                    'share_account_id' => $account->id,
                ];
                $debits = round($debits + $amount, 2);
            }

            $parts = [
                ['account' => $product->loan_principal_account_id, 'amount' => $repayment->principal_amount, 'label' => 'Principal'],
                ['account' => $product->interest_receivable_account_id, 'amount' => $repayment->interest_amount, 'label' => 'Interest'],
                ['account' => $product->fee_receivable_account_id, 'amount' => $repayment->fees_amount, 'label' => 'Fees'],
                ['account' => $product->penalty_receivable_account_id, 'amount' => $repayment->penalty_amount, 'label' => 'Penalty'],
            ];
            foreach ($parts as $part) {
                if (round((float) $part['amount'], 2) > 0) {
                    $lines[] = [
                        'account' => $part['account'],
                        'credit' => round((float) $part['amount'], 2),
                        'description' => $part['label'] . ' repaid from guarantee',
                        'member_id' => $loan->member_id,
                        'loan_id' => $loan->id,
                        'share_account_id' => null,
                    ];
                }
            }
            if ($debits <= 0) {
                throw ValidationException::withMessages(['repayment' => 'The guarantee has no amount to post.']);
            }

            return $this->postEntry([
                'posting_key' => 'guarantee_invocation:' . $repayment->id . ':confirmed',
                'event_type' => 'guarantee_invocation',
                'source' => $repayment,
                'transaction_date' => $repayment->payment_date,
                'posting_date' => $repayment->value_date ?: $repayment->payment_date,
                'reference_number' => $repayment->repayment_number,
                'description' => 'Guarantee invoked on loan ' . $loan->loan_number . ' (' . $repayment->repayment_number . ')',
                'created_by' => $repayment->recorded_by,
                'member_id' => $loan->member_id,
                'loan_id' => $loan->id,
            ], $lines);
        });
    }

    /**
     * Release an installment's interest from unearned interest to income on
     * its due date.
     *
     *   Dr  Unearned interest        interest due on the installment
     *   Cr  Interest income          interest due on the installment
     *
     * Dated on the due date, or on the run date when the period of the due
     * date is no longer open. Releases do not depend on whether the installment
     * has been paid: interest paid early stays unearned until it falls due.
     */
    public function postLoanInterestRelease(LoanRepaymentSchedule $schedule, string $asOf): ?JournalEntry
    {
        return DB::transaction(function () use ($schedule, $asOf) {
            $schedule = LoanRepaymentSchedule::whereKey($schedule->id)->lockForUpdate()->firstOrFail();
            if ($schedule->interest_released_on) {
                return null;
            }

            $loan = Loan::with('loanApplication.loanProduct')->findOrFail($schedule->loan_id);
            $product = optional($loan->loanApplication)->loanProduct;
            if (!$product || !$product->unearned_interest_account_id) {
                throw ValidationException::withMessages(['interest' => 'The loan product has no unearned interest account.']);
            }

            $due = \Carbon\Carbon::parse($schedule->due_date)->toDateString();
            $date = AccountingPeriod::where('status', 'open')->whereDate('start_date', '<=', $due)->whereDate('end_date', '>=', $due)->exists()
                ? $due : $asOf;
            $amount = round((float) $schedule->interest_due, 2);

            $entry = null;
            if ($amount > 0) {
                $entry = $this->postEntry([
                    'posting_key' => 'loan_interest:' . $schedule->id . ':released',
                    'event_type' => 'loan_interest_release',
                    'source' => $schedule,
                    'transaction_date' => $date,
                    'posting_date' => $date,
                    'reference_number' => $loan->loan_number . '/' . $schedule->installment_number,
                    'description' => 'Interest earned on installment ' . $schedule->installment_number . ' of loan ' . $loan->loan_number,
                    'created_by' => $loan->disbursed_by,
                    'member_id' => $loan->member_id,
                    'loan_id' => $loan->id,
                ], [
                    ['account' => $product->unearned_interest_account_id, 'debit' => $amount, 'description' => 'Unearned interest released'],
                    ['account' => $product->interest_income_account_id, 'credit' => $amount, 'description' => 'Interest income'],
                ]);
            }
            $schedule->update(['interest_released_on' => $due]);

            return $entry;
        });
    }

    /**
     * Move the unreleased interest of a loan that was booked to income in full
     * into unearned interest, so it is released as installments fall due.
     *
     *   Dr  Interest income          interest on installments not yet due
     *   Cr  Unearned interest        interest on installments not yet due
     *
     * Installments already due keep their income and are marked released.
     * Returns the amount moved, or null when the loan is skipped.
     */
    public function reclassifyLoanInterest(Loan $loan, string $asOf): ?float
    {
        return DB::transaction(function () use ($loan, $asOf) {
            $loan = Loan::with('loanApplication.loanProduct')->whereKey($loan->id)->lockForUpdate()->firstOrFail();
            $product = optional($loan->loanApplication)->loanProduct;
            if ($loan->interest_deferred || !$product || !$product->unearned_interest_account_id) {
                return null;
            }
            // Only loans whose disbursement is in the ledger can be reclassified.
            $disbursement = LoanDisbursement::where('loan_id', $loan->id)->where('status', 'processed')->first();
            if (!$disbursement || !JournalEntry::where('posting_key', 'loan_disbursement:' . $disbursement->id . ':processed')->exists()) {
                return null;
            }

            $schedules = LoanRepaymentSchedule::where('loan_id', $loan->id)->whereNull('interest_released_on')
                ->whereIn('status', ['pending', 'partially_paid', 'overdue', 'paid'])->lockForUpdate()->get();
            $future = $schedules->filter(fn($s) => \Carbon\Carbon::parse($s->due_date)->toDateString() > $asOf);
            $amount = round((float) $future->sum('interest_due'), 2);

            if ($amount > 0) {
                $this->postEntry([
                    'posting_key' => 'loan_interest:' . $loan->id . ':reclassified',
                    'event_type' => 'loan_interest_reclassification',
                    'source' => $loan,
                    'transaction_date' => $asOf,
                    'posting_date' => $asOf,
                    'reference_number' => $loan->loan_number,
                    'description' => 'Unearned interest on loan ' . $loan->loan_number,
                    'created_by' => $loan->disbursed_by,
                    'member_id' => $loan->member_id,
                    'loan_id' => $loan->id,
                ], [
                    ['account' => $product->interest_income_account_id, 'debit' => $amount, 'description' => 'Interest not yet earned'],
                    ['account' => $product->unearned_interest_account_id, 'credit' => $amount, 'description' => 'Unearned interest'],
                ]);
            }
            foreach ($schedules->diff($future) as $schedule) {
                $schedule->update(['interest_released_on' => \Carbon\Carbon::parse($schedule->due_date)->toDateString()]);
            }
            $loan->update(['interest_deferred' => true]);

            return $amount;
        });
    }

    /**
     * Post a penalty assessed on an overdue installment.
     *
     *   Dr  Penalty receivable        penalty
     *   Cr  Penalty income            penalty
     *
     * The receivable is cleared when the penalty is paid (see
     * postLoanRepaymentReceipt).
     */
    public function postLoanPenaltyAccrual(LoanRepaymentSchedule $schedule, float $amount, string $assessedOn): JournalEntry
    {
        return DB::transaction(function () use ($schedule, $amount, $assessedOn) {
            $loan = Loan::with('loanApplication.loanProduct')->findOrFail($schedule->loan_id);
            $product = optional($loan->loanApplication)->loanProduct;
            if (!$product) {
                throw ValidationException::withMessages(['penalty' => 'The loan product for this penalty could not be found.']);
            }

            $amount = round($amount, 2);
            if ($amount <= 0) {
                throw ValidationException::withMessages(['penalty' => 'The penalty has no amount to post.']);
            }

            if (!$product->penalty_receivable_account_id) {
                throw ValidationException::withMessages([
                    'ledger' => 'The loan product has no penalty receivable account. Set it on the loan product.',
                ]);
            }

            return $this->postEntry([
                'posting_key' => 'loan_penalty:' . $schedule->id . ':assessed',
                'event_type' => 'loan_penalty',
                'source' => $schedule,
                'transaction_date' => $assessedOn,
                'posting_date' => $assessedOn,
                'reference_number' => $loan->loan_number . '/' . $schedule->installment_number,
                'description' => 'Penalty on installment ' . $schedule->installment_number . ' of loan ' . $loan->loan_number,
                'created_by' => $loan->disbursed_by,
                'member_id' => $loan->member_id,
                'loan_id' => $loan->id,
            ], [
                ['account' => $product->penalty_receivable_account_id, 'debit' => $amount, 'description' => 'Penalty assessed'],
                ['account' => $product->penalty_income_account_id, 'credit' => $amount, 'description' => 'Penalty income'],
            ]);
        });
    }

    /**
     * Write off what is still owed on a loan.
     *
     *   Dr  Write-off expense          total written off
     *   Cr  Loan principal             remaining principal
     *   Cr  Interest receivable        remaining interest
     *   Cr  Fee receivable             remaining fees
     *   Cr  Penalty receivable         remaining penalties
     *
     * The amounts are the loan's own balances in the ledger (its tagged
     * journal lines), so the loan's sub-ledger returns to exactly zero.
     *
     * @return array{entry: JournalEntry, principal: float, interest: float, fees: float, penalty: float}
     */
    public function postLoanWriteOff(Loan $loan, string $writtenOffOn, string $reason): array
    {
        return DB::transaction(function () use ($loan, $writtenOffOn, $reason) {
            $loan = Loan::with('loanApplication.loanProduct')->whereKey($loan->id)->lockForUpdate()->firstOrFail();
            $product = optional($loan->loanApplication)->loanProduct;
            if (!$product) {
                throw ValidationException::withMessages(['write_off' => 'The loan product for this loan could not be found.']);
            }
            if (!$product->write_off_expense_account_id) {
                throw ValidationException::withMessages([
                    'ledger' => 'The loan product has no write-off expense account. Set it on the loan product.',
                ]);
            }

            $accounts = [
                'principal' => $product->loan_principal_account_id,
                'interest' => $product->interest_receivable_account_id,
                'fees' => $product->fee_receivable_account_id,
                'penalty' => $product->penalty_receivable_account_id,
            ];
            $labels = ['principal' => 'Principal', 'interest' => 'Interest', 'fees' => 'Fees', 'penalty' => 'Penalty'];

            $lines = [];
            $amounts = ['principal' => 0.0, 'interest' => 0.0, 'fees' => 0.0, 'penalty' => 0.0];
            foreach ($accounts as $key => $accountId) {
                if (!$accountId) {
                    continue;
                }
                $owed = round((float) JournalEntryLine::where('loan_id', $loan->id)
                    ->where('chart_of_account_id', $accountId)
                    ->whereHas('journalEntry', fn($q) => $q->whereIn('status', ['posted', 'reversed']))
                    ->sum(DB::raw('debit - credit')), 2);
                if ($owed > 0) {
                    $amounts[$key] = $owed;
                    $lines[] = ['account' => $accountId, 'credit' => $owed, 'description' => $labels[$key] . ' written off'];
                }
            }

            // Interest not yet released is still held as unearned interest. It is cleared against
            // the receivable, not charged to expense; any part already collected is earned now.
            $unearnedLines = [];
            if ($product->unearned_interest_account_id) {
                $unearned = round((float) JournalEntryLine::where('loan_id', $loan->id)
                    ->where('chart_of_account_id', $product->unearned_interest_account_id)
                    ->whereHas('journalEntry', fn($q) => $q->whereIn('status', ['posted', 'reversed']))
                    ->sum(DB::raw('credit - debit')), 2);
                $cleared = min($unearned, $amounts['interest']);
                if ($cleared > 0) {
                    $unearnedLines[] = ['account' => $product->unearned_interest_account_id, 'debit' => $cleared, 'description' => 'Unearned interest cleared'];
                    $amounts['interest'] = round($amounts['interest'] - $cleared, 2);
                    // The interest line above credits the full receivable; the expense covers only what was earned.
                    $amounts['unearned_cleared'] = $cleared;
                }
                $residual = round($unearned - $cleared, 2);
                if ($residual > 0) {
                    $unearnedLines[] = ['account' => $product->unearned_interest_account_id, 'debit' => $residual, 'description' => 'Collected interest earned'];
                    $unearnedLines[] = ['account' => $product->interest_income_account_id, 'credit' => $residual, 'description' => 'Interest income'];
                }
            }

            $total = round(array_sum([$amounts['principal'], $amounts['interest'], $amounts['fees'], $amounts['penalty']]), 2);
            if ($total <= 0 && empty($amounts['unearned_cleared'])) {
                throw ValidationException::withMessages([
                    'write_off' => 'There is nothing outstanding in the ledger to write off for this loan.',
                ]);
            }
            if ($total > 0) {
                array_unshift($lines, ['account' => $product->write_off_expense_account_id, 'debit' => $total, 'description' => 'Loan written off']);
            }
            $lines = array_merge($lines, $unearnedLines);

            $entry = $this->postEntry([
                'posting_key' => 'loan_write_off:' . $loan->id,
                'event_type' => 'loan_write_off',
                'source' => $loan,
                'transaction_date' => $writtenOffOn,
                'posting_date' => $writtenOffOn,
                'reference_number' => $loan->loan_number,
                'description' => 'Write-off of loan ' . $loan->loan_number . ': ' . $reason,
                'created_by' => $loan->disbursed_by,
                'member_id' => $loan->member_id,
                'loan_id' => $loan->id,
            ], $lines);

            return ['entry' => $entry, 'principal' => $amounts['principal'], 'interest' => $amounts['interest'], 'fees' => $amounts['fees'], 'penalty' => $amounts['penalty']];
        });
    }

    /**
     * Post a confirmed savings deposit to the general ledger.
     *
     *   Dr  Cash / bank account chosen on confirmation     deposit amount
     *   Cr  Savings control account of the product         deposit amount
     */
    public function postSavingsDeposit(SavingsTransaction $transaction): JournalEntry
    {
        return DB::transaction(function () use ($transaction) {
            $transaction = SavingsTransaction::with('savingsAccount.savingsProduct')
                ->whereKey($transaction->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($transaction->transaction_type !== 'deposit' || $transaction->status !== 'confirmed') {
                throw ValidationException::withMessages([
                    'transaction' => 'Only a confirmed deposit can be posted to the ledger.',
                ]);
            }

            $account = $transaction->savingsAccount;
            $product = optional($account)->savingsProduct;
            if (!$account || !$product) {
                throw ValidationException::withMessages([
                    'transaction' => 'The savings product for this deposit could not be found.',
                ]);
            }

            $received = ChartOfAccount::find($transaction->deposit_account_id);
            if (!$received) {
                throw ValidationException::withMessages([
                    'deposit_account_id' => 'Select the cash or bank ledger account that received this deposit.',
                ]);
            }

            $amount = round((float) $transaction->amount, 2);
            if ($amount <= 0) {
                throw ValidationException::withMessages(['transaction' => 'The deposit has no amount to post.']);
            }

            return $this->postEntry([
                'posting_key' => 'savings_transaction:' . $transaction->id . ':confirmed',
                'event_type' => 'savings_deposit',
                'source' => $transaction,
                'transaction_date' => $transaction->transaction_date,
                'posting_date' => $transaction->value_date ?: $transaction->transaction_date,
                'reference_number' => $transaction->external_reference ?: $transaction->receipt_number ?: $transaction->transaction_number,
                'description' => 'Savings deposit ' . $transaction->transaction_number . ' to account ' . $account->account_number,
                'created_by' => $transaction->confirmed_by ?: $transaction->recorded_by,
                'member_id' => $account->member_id,
                'savings_account_id' => $account->id,
            ], [
                ['account' => $received->id, 'debit' => $amount, 'description' => 'Deposit received'],
                ['account' => $this->requireSavingsRegister()->id, 'credit' => $amount, 'description' => 'Member savings'],
            ]);
        });
    }

    /**
     * Post a confirmed savings fee, interest credit or adjustment.
     *
     *   Fee         Dr  Savings register            Cr  Product fee income
     *   Interest    Dr  Product interest expense    Cr  Savings register
     *   Adjustment  credit: Dr offset account       Cr  Savings register
     *               debit:  Dr Savings register     Cr  offset account
     *
     * Transfers between savings accounts move money within the same register
     * account, so they have no ledger effect and are not posted.
     */
    public function postSavingsMovement(SavingsTransaction $transaction): JournalEntry
    {
        return DB::transaction(function () use ($transaction) {
            $transaction = SavingsTransaction::with('savingsAccount.savingsProduct')
                ->whereKey($transaction->id)
                ->lockForUpdate()
                ->firstOrFail();

            $type = $transaction->transaction_type;
            if ($transaction->status !== 'confirmed' || !in_array($type, ['fee', 'interest', 'adjustment'], true)) {
                throw ValidationException::withMessages([
                    'transaction' => 'Only a confirmed fee, interest or adjustment can be posted here.',
                ]);
            }

            $account = $transaction->savingsAccount;
            $product = optional($account)->savingsProduct;
            if (!$account || !$product) {
                throw ValidationException::withMessages([
                    'transaction' => 'The savings product for this transaction could not be found.',
                ]);
            }

            $amount = round((float) $transaction->amount, 2);
            if ($amount <= 0) {
                throw ValidationException::withMessages(['transaction' => 'The transaction has no amount to post.']);
            }

            $register = $this->requireSavingsRegister()->id;
            if ($type === 'fee') {
                $lines = [
                    ['account' => $register, 'debit' => $amount, 'description' => 'Savings fee charged'],
                    ['account' => $product->fee_income_account_id, 'credit' => $amount, 'description' => 'Savings fee income'],
                ];
            } elseif ($type === 'interest') {
                $lines = [
                    ['account' => $product->interest_expense_account_id, 'debit' => $amount, 'description' => 'Savings interest expense'],
                    ['account' => $register, 'credit' => $amount, 'description' => 'Savings interest credited'],
                ];
            } else {
                $offset = $transaction->offset_account_id;
                if (!$offset || (int) $offset === (int) $register) {
                    throw ValidationException::withMessages([
                        'offset_account_id' => 'Select the ledger account that balances this adjustment.',
                    ]);
                }
                $lines = $transaction->direction === 'credit'
                    ? [
                        ['account' => $offset, 'debit' => $amount, 'description' => 'Savings adjustment offset'],
                        ['account' => $register, 'credit' => $amount, 'description' => 'Savings adjustment credited'],
                    ]
                    : [
                        ['account' => $register, 'debit' => $amount, 'description' => 'Savings adjustment debited'],
                        ['account' => $offset, 'credit' => $amount, 'description' => 'Savings adjustment offset'],
                    ];
            }

            return $this->postEntry([
                'posting_key' => 'savings_transaction:' . $transaction->id . ':confirmed',
                'event_type' => 'savings_' . $type,
                'source' => $transaction,
                'transaction_date' => $transaction->transaction_date,
                'posting_date' => $transaction->value_date ?: $transaction->transaction_date,
                'reference_number' => $transaction->external_reference ?: $transaction->receipt_number ?: $transaction->transaction_number,
                'description' => 'Savings ' . $type . ' ' . $transaction->transaction_number . ' on account ' . $account->account_number,
                'created_by' => $transaction->confirmed_by ?: $transaction->recorded_by,
                'member_id' => $account->member_id,
                'savings_account_id' => $account->id,
            ], $lines);
        });
    }

    /**
     * Post a paid savings withdrawal to the general ledger.
     *
     *   Dr  Savings control account of the product         withdrawal amount
     *   Cr  Cash / bank account chosen at payment          withdrawal amount
     *
     * The member's balance falls at approval, but the ledger moves when the
     * money actually leaves, so approved-unpaid withdrawals are the only
     * difference between the savings control account and member balances.
     */
    public function postSavingsWithdrawal(SavingsWithdrawal $withdrawal): JournalEntry
    {
        return DB::transaction(function () use ($withdrawal) {
            $withdrawal = SavingsWithdrawal::with('savingsAccount.savingsProduct')
                ->whereKey($withdrawal->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($withdrawal->status !== 'paid') {
                throw ValidationException::withMessages([
                    'withdrawal' => 'Only a paid withdrawal can be posted to the ledger.',
                ]);
            }

            $account = $withdrawal->savingsAccount;
            $product = optional($account)->savingsProduct;
            if (!$account || !$product) {
                throw ValidationException::withMessages([
                    'withdrawal' => 'The savings product for this withdrawal could not be found.',
                ]);
            }

            $paidFrom = ChartOfAccount::find($withdrawal->payment_account_id);
            if (!$paidFrom) {
                throw ValidationException::withMessages([
                    'payment_account_id' => 'Select the cash or bank ledger account the withdrawal was paid from.',
                ]);
            }

            $amount = round((float) $withdrawal->amount, 2);
            if ($amount <= 0) {
                throw ValidationException::withMessages(['withdrawal' => 'The withdrawal has no amount to post.']);
            }

            $paidOn = optional($withdrawal->paid_at)->toDateString() ?: now()->toDateString();

            return $this->postEntry([
                'posting_key' => 'savings_withdrawal:' . $withdrawal->id . ':paid',
                'event_type' => 'savings_withdrawal',
                'source' => $withdrawal,
                'transaction_date' => $paidOn,
                'posting_date' => $paidOn,
                'reference_number' => $withdrawal->payment_reference ?: $withdrawal->request_number,
                'description' => 'Savings withdrawal ' . $withdrawal->request_number . ' from account ' . $account->account_number,
                'created_by' => $withdrawal->paid_by ?: $withdrawal->approved_by,
                'member_id' => $account->member_id,
                'savings_account_id' => $account->id,
            ], [
                ['account' => $this->requireSavingsRegister()->id, 'debit' => $amount, 'description' => 'Member savings withdrawn'],
                ['account' => $paidFrom->id, 'credit' => $amount, 'description' => 'Withdrawal paid out'],
            ]);
        });
    }

    /**
     * Reverse the ledger entries behind a savings transaction that is being
     * reversed: a deposit, or the payment of a withdrawal. Transactions with
     * no entry (including everything confirmed before ledger posting existed)
     * are skipped.
     */
    public function reverseSavingsTransaction(SavingsTransaction $transaction, string $reason): void
    {
        $this->reverseEntry(
            'savings_transaction:' . $transaction->id . ':confirmed',
            'savings_transaction:' . $transaction->id . ':reversed',
            'savings_deposit_reversal',
            $transaction,
            $reason,
            'Transaction ' . $transaction->transaction_number
        );

        $withdrawal = SavingsWithdrawal::where('savings_transaction_id', $transaction->id)->first();
        if ($withdrawal) {
            $this->reverseEntry(
                'savings_withdrawal:' . $withdrawal->id . ':paid',
                'savings_withdrawal:' . $withdrawal->id . ':reversed',
                'savings_withdrawal_reversal',
                $withdrawal,
                $reason,
                'Withdrawal ' . $withdrawal->request_number
            );
        }
    }

    /**
     * Post a confirmed share purchase, redemption, bonus issue or adjustment.
     *
     *   Credit (purchase, bonus issue, credit adjustment)
     *       Dr  Ledger account chosen on confirmation    amount
     *       Cr  Share capital account of the product     amount
     *   Debit (redemption, debit adjustment)
     *       Dr  Share capital account of the product     amount
     *       Cr  Ledger account chosen on confirmation    amount
     *
     * The chosen ledger account is cash or bank for a purchase or redemption,
     * and the reserve or other account that balances a bonus issue or
     * adjustment. Shares are carried at unit value, so the share premium
     * account is not used. Transfers between members do not change share
     * capital and are not posted.
     */
    public function postShareTransaction(ShareTransaction $transaction): JournalEntry
    {
        return DB::transaction(function () use ($transaction) {
            $transaction = ShareTransaction::with('shareAccount.shareProduct')
                ->whereKey($transaction->id)
                ->lockForUpdate()
                ->firstOrFail();

            $type = $transaction->transaction_type;
            if ($transaction->status !== 'confirmed' || !in_array($type, ['purchase', 'redemption', 'bonus_issue', 'adjustment'], true)) {
                throw ValidationException::withMessages([
                    'transaction' => 'Only a confirmed purchase, redemption, bonus issue or adjustment can be posted.',
                ]);
            }

            $account = $transaction->shareAccount;
            $product = optional($account)->shareProduct;
            if (!$account || !$product) {
                throw ValidationException::withMessages([
                    'transaction' => 'The share product for this transaction could not be found.',
                ]);
            }

            $other = ChartOfAccount::find($transaction->ledger_account_id);
            if (!$other || (int) $other->id === (int) $product->share_capital_account_id) {
                throw ValidationException::withMessages([
                    'ledger_account_id' => in_array($type, ['purchase', 'redemption'], true)
                        ? 'Select the cash or bank ledger account for this share transaction.'
                        : 'Select the ledger account that balances this share transaction.',
                ]);
            }

            $amount = round((float) $transaction->amount, 2);
            if ($amount <= 0) {
                throw ValidationException::withMessages(['transaction' => 'The transaction has no amount to post.']);
            }

            $label = ucwords(str_replace('_', ' ', $type));
            $capital = $product->share_capital_account_id;
            $lines = $transaction->direction === 'credit'
                ? [
                    ['account' => $other->id, 'debit' => $amount, 'description' => $label . ' offset'],
                    ['account' => $capital, 'credit' => $amount, 'description' => 'Share capital'],
                ]
                : [
                    ['account' => $capital, 'debit' => $amount, 'description' => 'Share capital'],
                    ['account' => $other->id, 'credit' => $amount, 'description' => $label . ' offset'],
                ];

            return $this->postEntry([
                'posting_key' => 'share_transaction:' . $transaction->id . ':confirmed',
                'event_type' => 'share_' . $type,
                'source' => $transaction,
                'transaction_date' => $transaction->transaction_date,
                'posting_date' => $transaction->value_date ?: $transaction->transaction_date,
                'reference_number' => $transaction->payment_reference ?: $transaction->receipt_number ?: $transaction->transaction_number,
                'description' => $label . ' ' . $transaction->transaction_number . ' on share account ' . $account->account_number,
                'created_by' => $transaction->confirmed_by ?: $transaction->recorded_by,
                'member_id' => $account->member_id,
                'share_account_id' => $account->id,
            ], $lines);
        });
    }

    /**
     * Reverse the journal posted for a share transaction. Transactions
     * confirmed before ledger posting existed have no entry, so are skipped.
     */
    public function reverseShareTransaction(ShareTransaction $transaction, string $reason): ?JournalEntry
    {
        return $this->reverseEntry(
            'share_transaction:' . $transaction->id . ':confirmed',
            'share_transaction:' . $transaction->id . ':reversed',
            'share_' . $transaction->transaction_type . '_reversal',
            $transaction,
            $reason,
            'Share transaction ' . $transaction->transaction_number
        );
    }

    /**
     * Accounts whose balances must agree with a subledger (loan, savings or
     * share balances). Only the transaction screens may post to them, because
     * a manual line carries no member, loan or account tag.
     */
    public function subledgerControlledAccountIds(): array
    {
        $ids = ChartOfAccount::where('config_key', 'individual_savings_register')->pluck('id')->all();
        foreach (['loan_principal_account_id', 'interest_receivable_account_id', 'fee_receivable_account_id', 'penalty_receivable_account_id', 'unearned_interest_account_id'] as $column) {
            $ids = array_merge($ids, DB::table('loan_products')->whereNotNull($column)->pluck($column)->all());
        }
        $ids = array_merge($ids, DB::table('share_products')->whereNotNull('share_capital_account_id')->pluck('share_capital_account_id')->all());

        return array_values(array_unique(array_map('intval', $ids)));
    }

    /**
     * Refuse manual journal lines on subledger-controlled accounts.
     */
    public function assertManualAccountsAllowed(array $accountIds): void
    {
        $blocked = array_intersect(array_map('intval', $accountIds), $this->subledgerControlledAccountIds());
        if ($blocked) {
            $codes = ChartOfAccount::whereIn('id', $blocked)->orderBy('code')->pluck('code')->implode(', ');
            throw ValidationException::withMessages([
                'lines' => "Account {$codes} is controlled by a loan, savings or share subledger and cannot be used in a manual journal. Record the transaction on its own screen instead.",
            ]);
        }
    }

    /**
     * Post a draft manual journal. The same rules as every other posting
     * apply: an open period containing the transaction date, a balanced entry
     * and active, postable accounts that are not subledger-controlled.
     */
    public function postManualEntry(int $id): JournalEntry
    {
        return DB::transaction(function () use ($id) {
            $entry = JournalEntry::whereKey($id)->lockForUpdate()->with('lines')->firstOrFail();

            if ($entry->event_type !== 'manual' || $entry->status !== 'draft') {
                throw ValidationException::withMessages(['journal' => 'Only draft manual journals can be posted.']);
            }

            $period = AccountingPeriod::whereKey($entry->accounting_period_id)->lockForUpdate()->firstOrFail();
            if ($period->status !== 'open') {
                throw ValidationException::withMessages(['journal' => 'The accounting period is not open.']);
            }
            $date = $entry->transaction_date->toDateString();
            if ($date < $period->start_date->toDateString() || $date > $period->end_date->toDateString()) {
                throw ValidationException::withMessages(['journal' => 'Transaction date falls outside the accounting period.']);
            }

            if ($entry->lines->count() < 2) {
                throw ValidationException::withMessages(['journal' => 'A journal must contain at least two lines.']);
            }
            foreach ($entry->lines as $line) {
                $debit = round((float) $line->debit, 2);
                $credit = round((float) $line->credit, 2);
                if ($debit < 0 || $credit < 0 || ($debit > 0) === ($credit > 0)) {
                    throw ValidationException::withMessages(['journal' => 'Each journal line must have either a debit or a credit amount.']);
                }
            }
            $difference = $entry->lines->sum(fn($line) => round((float) $line->debit, 2) - round((float) $line->credit, 2));
            if (bccomp((string) round($difference, 2), '0', 2) !== 0 || round((float) $entry->lines->sum('debit'), 2) <= 0) {
                throw ValidationException::withMessages(['journal' => 'The journal is not balanced.']);
            }

            foreach ($entry->lines as $line) {
                $account = ChartOfAccount::find($line->chart_of_account_id);
                if (!$account || !$account->is_active) {
                    throw ValidationException::withMessages(['journal' => 'One or more accounts are inactive.']);
                }
                if (!$account->is_postable) {
                    throw ValidationException::withMessages(['journal' => "Account {$account->code} is not postable."]);
                }
            }
            $this->assertManualAccountsAllowed($entry->lines->pluck('chart_of_account_id')->all());

            $actor = Auth::id() ?: $entry->created_by;
            $entry->update(['status' => 'posted', 'posted_by' => $actor, 'posted_at' => now()]);

            return $entry->fresh();
        });
    }

    /**
     * Reverse a posted manual journal. Entries created by a transaction are
     * reversed from that transaction, so its records stay in step with the ledger.
     */
    public function reverseManualEntry(int $id, string $reason): JournalEntry
    {
        return DB::transaction(function () use ($id, $reason) {
            $original = JournalEntry::whereKey($id)->lockForUpdate()->firstOrFail();

            if ($original->event_type !== 'manual') {
                throw ValidationException::withMessages([
                    'journal' => 'This entry was created by a transaction. Reverse the transaction itself instead.',
                ]);
            }
            if ($original->status !== 'posted') {
                throw ValidationException::withMessages(['journal' => 'Only posted journals can be reversed.']);
            }

            return $this->reverseEntry(
                $original->posting_key,
                'manual_journal:' . $original->id . ':reversed',
                'manual_reversal',
                $original,
                $reason,
                'Journal ' . $original->entry_number
            );
        });
    }

    /**
     * Member balances are posted to the individual savings register, the
     * postable sub-ledger of the member savings control account.
     */
    private function savingsRegisterAccount(): ?ChartOfAccount
    {
        return ChartOfAccount::where('config_key', 'individual_savings_register')->first();
    }

    private function requireSavingsRegister(): ChartOfAccount
    {
        $account = $this->savingsRegisterAccount();
        if (!$account) {
            throw ValidationException::withMessages([
                'ledger' => 'The individual savings register account is not configured.',
            ]);
        }

        return $account;
    }

    /**
     * Create a posted, balanced journal from lines of the form
     * ['account' => id, 'debit' => x, 'credit' => y, 'description' => text].
     * Returns the existing entry when the posting key was already used.
     */
    private function postEntry(array $header, array $lines): JournalEntry
    {
        $existing = JournalEntry::where('posting_key', $header['posting_key'])->first();
        if ($existing) {
            return $existing;
        }

        $period = $this->openPeriod($header['transaction_date']);
        $actor = Auth::id() ?: $header['created_by'];

        $entry = JournalEntry::create([
            'accounting_period_id' => $period->id,
            'entry_number' => $this->generateJournalEntryNumber(),
            'posting_key' => $header['posting_key'],
            'transaction_date' => $header['transaction_date'],
            'posting_date' => $header['posting_date'],
            'reference_number' => $header['reference_number'],
            'event_type' => $header['event_type'],
            'source_type' => get_class($header['source']),
            'source_id' => $header['source']->getKey(),
            'description' => $header['description'],
            'status' => 'posted',
            'created_by' => $actor,
            'posted_by' => $actor,
            'posted_at' => now(),
        ]);

        foreach ($lines as $line) {
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'chart_of_account_id' => $line['account'],
                'debit' => $line['debit'] ?? 0,
                'credit' => $line['credit'] ?? 0,
                'description' => $line['description'] ?? null,
                'member_id' => array_key_exists('member_id', $line) ? $line['member_id'] : ($header['member_id'] ?? null),
                'loan_id' => array_key_exists('loan_id', $line) ? $line['loan_id'] : ($header['loan_id'] ?? null),
                'savings_account_id' => array_key_exists('savings_account_id', $line) ? $line['savings_account_id'] : ($header['savings_account_id'] ?? null),
                'share_account_id' => array_key_exists('share_account_id', $line) ? $line['share_account_id'] : ($header['share_account_id'] ?? null),
            ]);
        }

        // Every account must exist, be active and accept postings.
        foreach ($entry->lines()->with('account')->get() as $line) {
            $account = $line->account;
            if (!$account || !$account->is_active || !$account->is_postable) {
                throw ValidationException::withMessages([
                    'ledger' => 'A ledger account used by this transaction is missing, inactive or not postable. Check the loan product account mapping.',
                ]);
            }
        }

        $difference = JournalEntryLine::where('journal_entry_id', $entry->id)->sum(DB::raw('debit - credit'));
        if (bccomp((string) $difference, '0', 2) !== 0) {
            throw ValidationException::withMessages([
                'ledger' => 'The transaction does not balance in the ledger. Check the amounts and account mapping.',
            ]);
        }

        return $entry;
    }

    /**
     * Mirror a posted entry with debits and credits swapped, dated today.
     */
    private function reverseEntry(string $originalKey, string $reversalKey, string $eventType, Model $source, string $reason, string $label): ?JournalEntry
    {
        return DB::transaction(function () use ($originalKey, $reversalKey, $eventType, $source, $reason, $label) {
            $original = JournalEntry::where('posting_key', $originalKey)->lockForUpdate()->with('lines')->first();
            if (!$original) {
                return null;
            }

            $existing = JournalEntry::where('posting_key', $reversalKey)->first();
            if ($existing) {
                return $existing;
            }

            if ($original->status !== 'posted') {
                throw ValidationException::withMessages([
                    'ledger' => 'The ledger entry for this transaction is not posted and cannot be reversed.',
                ]);
            }

            $today = now()->toDateString();
            $period = $this->openPeriod($today);
            $actor = Auth::id() ?: $original->created_by;

            $reversal = JournalEntry::create([
                'accounting_period_id' => $period->id,
                'entry_number' => $this->generateJournalEntryNumber(),
                'posting_key' => $reversalKey,
                'transaction_date' => $today,
                'posting_date' => $today,
                'reference_number' => $original->entry_number,
                'event_type' => $eventType,
                'source_type' => get_class($source),
                'source_id' => $source->getKey(),
                'description' => 'Reversal of ' . $original->entry_number . ' (' . $label . ')',
                'status' => 'posted',
                'created_by' => $actor,
                'posted_by' => $actor,
                'posted_at' => now(),
                'reversal_of_id' => $original->id,
                'reversal_reason' => $reason,
            ]);

            foreach ($original->lines as $line) {
                JournalEntryLine::create([
                    'journal_entry_id' => $reversal->id,
                    'chart_of_account_id' => $line->chart_of_account_id,
                    'debit' => $line->credit,
                    'credit' => $line->debit,
                    'description' => 'Reversal: ' . $line->description,
                    'member_id' => $line->member_id,
                    'loan_id' => $line->loan_id,
                    'savings_account_id' => $line->savings_account_id,
                    'share_account_id' => $line->share_account_id,
                ]);
            }

            $original->update(['status' => 'reversed']);

            return $reversal;
        });
    }

    private function openPeriod($date): AccountingPeriod
    {
        $period = AccountingPeriod::where('status', 'open')
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->lockForUpdate()
            ->first();

        if (!$period) {
            throw ValidationException::withMessages([
                'ledger' => 'There is no open accounting period for ' . \Carbon\Carbon::parse($date)->format('d M Y') . '.',
            ]);
        }

        return $period;
    }

    /**
     * The next entry number for this year, JE-YYYY-000001 onwards.
     *
     * Numbers come from a locked per-year counter, so two postings never
     * receive the same number and a deleted draft cannot cause a clash. The
     * counter starts after the highest number already issued that year. It
     * moves with the surrounding transaction, so a rolled-back posting leaves
     * no gap.
     */
    public function generateJournalEntryNumber(): string
    {
        $year = (int) now()->format('Y');

        return DB::transaction(function () use ($year) {
            $row = DB::table('journal_number_sequences')->where('year', $year)->lockForUpdate()->first();
            if (!$row) {
                $highest = (int) JournalEntry::where('entry_number', 'like', 'JE-' . $year . '-%')
                    ->max(DB::raw('CAST(SUBSTRING(entry_number, 9) AS UNSIGNED)'));
                DB::table('journal_number_sequences')->insertOrIgnore(['year' => $year, 'last_number' => $highest]);
                $row = DB::table('journal_number_sequences')->where('year', $year)->lockForUpdate()->first();
            }

            $next = (int) $row->last_number + 1;
            DB::table('journal_number_sequences')->where('year', $year)->update(['last_number' => $next]);

            return 'JE-' . $year . '-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
        });
    }
}

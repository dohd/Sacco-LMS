<?php

namespace App\Services;

use App\Models\Accounting\AccountingPeriod;
use App\Models\Accounting\ChartOfAccount;
use App\Models\Accounting\JournalEntry;
use App\Models\Accounting\JournalEntryLine;
use App\Models\LoanApplications\LoanDisbursement;
use App\Models\LoanApplications\LoanRepayment;
use App\Models\Savings\SavingsTransaction;
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
     * Interest and fees are recognised in full when the loan is disbursed and
     * collected later through the receivable accounts as repayments arrive.
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
            if ($interest > 0) {
                $lines[] = ['account' => $product->interest_receivable_account_id, 'debit' => $interest, 'description' => 'Interest charged'];
                $lines[] = ['account' => $product->interest_income_account_id, 'credit' => $interest, 'description' => 'Interest income'];
            }
            if ($fees > 0) {
                $lines[] = ['account' => $product->fee_receivable_account_id, 'debit' => $fees, 'description' => 'Fees capitalised'];
                $lines[] = ['account' => $product->processing_fee_income_account_id, 'credit' => $fees, 'description' => 'Fee income'];
            }

            return $this->postEntry([
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
        });
    }

    /**
     * Reverse the journal posted for a disbursement. Disbursements processed
     * before ledger posting existed have no entry, so there is nothing to reverse.
     */
    public function reverseLoanDisbursement(LoanDisbursement $disbursement, string $reason): ?JournalEntry
    {
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
                ['account' => $product->penalty_income_account_id, 'amount' => $repayment->penalty_amount, 'label' => 'Penalty'],
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
                'member_id' => $header['member_id'] ?? null,
                'loan_id' => $header['loan_id'] ?? null,
                'savings_account_id' => $header['savings_account_id'] ?? null,
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

    private function generateJournalEntryNumber(): string
    {
        do {
            $number = 'JE-' . now()->format('Y') . '-' .
                str_pad(
                    JournalEntry::whereYear('created_at', now()->year)->count() + 1,
                    6,
                    '0',
                    STR_PAD_LEFT
                );
        } while (JournalEntry::where('entry_number', $number)->exists());

        return $number;
    }
}

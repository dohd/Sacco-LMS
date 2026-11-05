<?php

namespace App\Services;

use App\Models\LoanApplications\Loan;
use App\Models\LoanApplications\LoanRepayment;
use App\Models\LoanApplications\LoanRepaymentSchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Confirms a pending loan repayment: allocates it to the schedule (penalty,
 * interest, fees, then principal, oldest installment first), updates the loan
 * balances and status, releases guarantors on full repayment and posts the
 * receipt to the ledger.
 *
 * Must run inside a transaction.
 */
class LoanRepaymentConfirmationService
{
    public function confirm(int $id, bool $post = true): LoanRepayment
    {
        $repayment = LoanRepayment::whereKey($id)
            ->lockForUpdate()
            ->firstOrFail();

        if ($repayment->status !== 'pending') {
            throw ValidationException::withMessages([
                'repayment' => 'Only pending repayments can be confirmed.'
            ]);
        }

        $loan = Loan::whereKey($repayment->loan_id)
            ->lockForUpdate()
            ->firstOrFail();

        if ((float) $loan->amount_disbursed <= 0) {
            throw ValidationException::withMessages(['loan_id' => 'The loan must be disbursed before accepting repayments.']);
        }

        if (!in_array($loan->status, ['active', 'in_arrears'])) {
            throw ValidationException::withMessages([
                'repayment' => "Repayments cannot be posted to a {$loan->status} loan."
            ]);
        }

        // Match the 4-decimal precision tracking used in the approval workflow engine
        $remaining = round((float) $repayment->amount_paid, 4);

        $principalAmount = 0;
        $interestAmount = 0;
        $feesAmount = 0;
        $penaltyAmount = 0;

        $allocations = [];
        $scheduleStatuses = []; // Tracks schedule outcomes dynamically for absolute status updating

        $schedules = LoanRepaymentSchedule::where('loan_id', $loan->id)
            ->whereIn('status', ['pending', 'partially_paid', 'overdue'])
            ->where('outstanding_amount', '>', 0)
            ->orderBy('due_date')
            ->orderBy('installment_number')
            ->lockForUpdate()
            ->get();

        foreach ($schedules as $schedule) {

            if ($remaining <= 0) {
                // Unmodified schedules retain their existing state profiles
                $scheduleStatuses[] = $schedule->status;
                continue;
            }

            // 1. Penalty Component Allocation
            $penaltyOutstanding = max(0, round($schedule->penalty_due - $schedule->penalty_paid, 4));
            $penaltyPaid = min($remaining, $penaltyOutstanding);
            $remaining = round($remaining - $penaltyPaid, 4);
            $penaltyAmount = round($penaltyAmount + $penaltyPaid, 4);

            // 2. Interest Component Allocation
            $interestOutstanding = max(0, round($schedule->interest_due - $schedule->interest_paid, 4));
            $interestPaid = min($remaining, $interestOutstanding);
            $remaining = round($remaining - $interestPaid, 4);
            $interestAmount = round($interestAmount + $interestPaid, 4);

            // 3. Capitalized Fees Component Allocation
            $feesOutstanding = max(0, round($schedule->fees_due - $schedule->fees_paid, 4));
            $feesPaid = min($remaining, $feesOutstanding);
            $remaining = round($remaining - $feesPaid, 4);
            $feesAmount = round($feesAmount + $feesPaid, 4);

            // 4. Base Principal Component Allocation
            $principalOutstanding = max(0, round($schedule->principal_due - $schedule->principal_paid, 4));
            $principalPaid = min($remaining, $principalOutstanding);
            $remaining = round($remaining - $principalPaid, 4);
            $principalAmount = round($principalAmount + $principalPaid, 4);

            $allocationTotal = round($principalPaid + $interestPaid + $feesPaid + $penaltyPaid, 4);

            if ($allocationTotal <= 0) {
                $scheduleStatuses[] = $schedule->status;
                continue;
            }

            $newPrincipalPaid = round($schedule->principal_paid + $principalPaid, 4);
            $newInterestPaid = round($schedule->interest_paid + $interestPaid, 4);
            $newFeesPaid = round($schedule->fees_paid + $feesPaid, 4);
            $newPenaltyPaid = round($schedule->penalty_paid + $penaltyPaid, 4);

            $totalPaid = round($newPrincipalPaid + $newInterestPaid + $newFeesPaid + $newPenaltyPaid, 4);
            $outstanding = max(0, round($schedule->total_due - $totalPaid, 4));

            if ($outstanding <= 0) {
                $status = 'paid';
                $fullyPaidDate = $repayment->payment_date;
            } elseif (Carbon::parse($schedule->due_date)->lt(Carbon::parse($repayment->payment_date))) {
                $status = 'overdue';
                $fullyPaidDate = null;
            } else {
                $status = 'partially_paid';
                $fullyPaidDate = null;
            }

            $scheduleStatuses[] = $status;

            // Compute the closing principal balance reduction to align with approval capitalization flow
            $newClosingPrincipal = max(0, round($schedule->opening_principal_balance - ($newPrincipalPaid + $newFeesPaid), 4));

            $schedule->update([
                'principal_paid' => $newPrincipalPaid,
                'interest_paid' => $newInterestPaid,
                'fees_paid' => $newFeesPaid,
                'penalty_paid' => $newPenaltyPaid,
                'total_paid' => $totalPaid,
                'outstanding_amount' => $outstanding,
                'closing_principal_balance' => $newClosingPrincipal,
                'fully_paid_date' => $fullyPaidDate,
                'status' => $status,
            ]);

            $allocations[] = [
                'loan_repayment_schedule_id' => $schedule->id,
                'principal_allocated' => $principalPaid,
                'interest_allocated' => $interestPaid,
                'fees_allocated' => $feesPaid,
                'penalty_allocated' => $penaltyPaid,
                'total_allocated' => $allocationTotal,
            ];
        }

        $unallocatedAmount = round($remaining, 4);

        /*
         * Process structural adjustments against Loan balances.
         * NOTE: Because fees were capitalized into the principal asset baseline at approval,
         * both principalAmount and feesAmount are deducted from the principal_balance parameter.
         */
        $newPrincipalBalance = max(0, round($loan->principal_balance - ($principalAmount + $feesAmount), 4));
        $newInterestBalance = max(0, round($loan->interest_balance - $interestAmount, 4));
        $newFeesBalance = max(0, round($loan->fees_balance - $feesAmount, 4));
        $newPenaltyBalance = max(0, round($loan->penalty_balance - $penaltyAmount, 4));

        $newTotalOutstanding = round($newPrincipalBalance + $newInterestBalance + $newFeesBalance + $newPenaltyBalance, 4);

        $newLoanPrincipalPaid = round($loan->principal_paid + $principalAmount, 4);
        $newLoanInterestPaid = round($loan->interest_paid + $interestAmount, 4);
        $newLoanFeesPaid = round($loan->fees_paid + $feesAmount, 4);
        $newLoanPenaltiesPaid = round($loan->penalties_paid + $penaltyAmount, 4);

        $newTotalPaid = round($loan->total_paid + $principalAmount + $interestAmount + $feesAmount + $penaltyAmount, 4);

        // Deterministically deduce the target loan state matrix directly from active records
        $hasOverdue = in_array('overdue', $scheduleStatuses);
        $loanStatus = $newTotalOutstanding <= 0 ? 'fully_paid' : ($hasOverdue ? 'in_arrears' : 'active');

        /*
         * Persist transaction allocation detail records
         */
        foreach ($allocations as $allocation) {
            $repayment->allocations()->create([
                'loan_repayment_schedule_id' => $allocation['loan_repayment_schedule_id'],
                'loan_id' => $loan->id,
                'member_id' => $loan->member_id,
                'principal_allocated' => $allocation['principal_allocated'],
                'interest_allocated' => $allocation['interest_allocated'],
                'fees_allocated' => $allocation['fees_allocated'],
                'penalty_allocated' => $allocation['penalty_allocated'],
                'total_allocated' => $allocation['total_allocated'],
                'allocated_by' => Auth::id(),
                'allocated_at' => now(),
                'status' => 'active',
            ]);
        }

        /*
         * Update the parent repayment execution entity ledger state
         */
        $repayment->update([
            'principal_amount' => round($principalAmount, 2),
            'interest_amount' => round($interestAmount, 2),
            'fees_amount' => round($feesAmount, 2),
            'penalty_amount' => round($penaltyAmount, 2),
            'unallocated_amount' => round($unallocatedAmount, 2),
            'status' => 'confirmed',
        ]);

        /*
         * Sync core parameters on the active Loan entity model
         */
        $loan->update([
            'principal_balance' => round($newPrincipalBalance, 2),
            'interest_balance' => round($newInterestBalance, 2),
            'fees_balance' => round($newFeesBalance, 2),
            'penalty_balance' => round($newPenaltyBalance, 2),
            'total_outstanding_balance' => round($newTotalOutstanding, 2),
            'principal_paid' => round($newLoanPrincipalPaid, 2),
            'interest_paid' => round($newLoanInterestPaid, 2),
            'fees_paid' => round($newLoanFeesPaid, 2),
            'penalties_paid' => round($newLoanPenaltiesPaid, 2),
            'total_paid' => round($newTotalPaid, 2),
            'status' => $loanStatus,
        ]);

        // A fully repaid loan frees the guarantors' shares.
        if ($loanStatus === 'fully_paid') {
            app(\App\Services\LoanGuarantorService::class)->releaseForLoan($loan);
        }

        // Post the receipt to the general ledger; any failure rolls back the confirmation.
        // Guarantee repayments are posted by the guarantee invocation itself.
        if ($post) {
            app(JournalPostingService::class)->postLoanRepaymentReceipt($repayment->fresh());
        }

        return $repayment->fresh();
    }
}

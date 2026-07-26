<?php

namespace App\Services;

use App\Models\LoanApplications\LoanRepayment;
use App\Models\LoanApplications\LoanRepaymentSchedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LoanRepaymentAllocationService
{
    /**
     * Allocate a confirmed repayment to the oldest outstanding
     * repayment schedules.
     */
    public function allocate(LoanRepayment $repayment, ?int $allocatedBy = null): LoanRepayment 
    {
        return DB::transaction(function () use ($repayment,$allocatedBy) {
            $repayment = LoanRepayment::query()
                ->with('loan')
                ->lockForUpdate()
                ->findOrFail($repayment->id);

            if ($repayment->status !== 'confirmed') {
                throw ValidationException::withMessages([
                    'repayment' =>
                        'Only confirmed repayments can be allocated.',
                ]);
            }

            $existingAllocation = $repayment->allocations()
                ->where('status', 'active')
                ->exists();

            if ($existingAllocation) {
                throw ValidationException::withMessages([
                    'repayment' =>
                        'This repayment has already been allocated.',
                ]);
            }

            $remainingAmount = round((float) $repayment->amount_paid,2);

            if ($remainingAmount <= 0) {
                throw ValidationException::withMessages([
                    'amount_paid' => 'The repayment amount must be greater than zero.',                        
                ]);
            }

            $totalPrincipal = 0;
            $totalInterest = 0;
            $totalFees = 0;
            $totalPenalty = 0;

            $schedules = LoanRepaymentSchedule::query()
                ->where('loan_id', $repayment->loan_id)
                ->whereIn('status', [
                    'pending',
                    'partially_paid',
                    'overdue',
                ])
                ->orderBy('due_date')
                ->orderBy('installment_number')
                ->lockForUpdate()
                ->get();

            foreach ($schedules as $schedule) {
                if ($remainingAmount <= 0) {
                    break;
                }

                $penaltyOutstanding = max(
                    0,
                    round(
                        (float) $schedule->penalty_due
                        - (float) $schedule->penalty_paid,
                        2
                    )
                );

                $feesOutstanding = max(
                    0,
                    round(
                        (float) $schedule->fees_due
                        - (float) $schedule->fees_paid,
                        2
                    )
                );

                $interestOutstanding = max(
                    0,
                    round(
                        (float) $schedule->interest_due
                        - (float) $schedule->interest_paid,
                        2
                    )
                );

                $principalOutstanding = max(
                    0,
                    round(
                        (float) $schedule->principal_due
                        - (float) $schedule->principal_paid,
                        2
                    )
                );

                /*
                 * Allocation policy:
                 * penalties, fees, interest, then principal.
                 */
                $penaltyAllocated = min(
                    $remainingAmount,
                    $penaltyOutstanding
                );

                $remainingAmount -= $penaltyAllocated;

                $feesAllocated = min(
                    $remainingAmount,
                    $feesOutstanding
                );

                $remainingAmount -= $feesAllocated;

                $interestAllocated = min(
                    $remainingAmount,
                    $interestOutstanding
                );

                $remainingAmount -= $interestAllocated;

                $principalAllocated = min(
                    $remainingAmount,
                    $principalOutstanding
                );

                $remainingAmount -= $principalAllocated;

                $allocatedAmount = round(
                    $penaltyAllocated
                    + $feesAllocated
                    + $interestAllocated
                    + $principalAllocated,
                    2
                );

                if ($allocatedAmount <= 0) {
                    continue;
                }

                $repayment->allocations()->create([
                    'loan_repayment_schedule_id' => $schedule->id,
                    'principal_allocated' => $principalAllocated,
                    'interest_allocated' => $interestAllocated,
                    'fees_allocated' => $feesAllocated,
                    'penalty_allocated' => $penaltyAllocated,
                    'allocated_by' => $allocatedBy,
                    'allocated_at' => now(),
                    'status' => 'active',
                ]);

                $newPrincipalPaid = round(
                    (float) $schedule->principal_paid
                    + $principalAllocated,
                    2
                );

                $newInterestPaid = round(
                    (float) $schedule->interest_paid
                    + $interestAllocated,
                    2
                );

                $newFeesPaid = round(
                    (float) $schedule->fees_paid
                    + $feesAllocated,
                    2
                );

                $newPenaltyPaid = round(
                    (float) $schedule->penalty_paid
                    + $penaltyAllocated,
                    2
                );

                $newTotalPaid = round(
                    $newPrincipalPaid
                    + $newInterestPaid
                    + $newFeesPaid
                    + $newPenaltyPaid,
                    2
                );

                $newOutstanding = max(
                    0,
                    round(
                        (float) $schedule->total_due
                        - $newTotalPaid,
                        2
                    )
                );

                if ($newOutstanding <= 0) {
                    $scheduleStatus = 'paid';
                    $fullyPaidDate = $repayment->payment_date;
                } elseif ($newTotalPaid > 0) {
                    $scheduleStatus = 'partially_paid';
                    $fullyPaidDate = null;
                } else {
                    $scheduleStatus = $schedule->status;
                    $fullyPaidDate = null;
                }

                $schedule->update([
                    'principal_paid' => $newPrincipalPaid,
                    'interest_paid' => $newInterestPaid,
                    'fees_paid' => $newFeesPaid,
                    'penalty_paid' => $newPenaltyPaid,
                    'total_paid' => $newTotalPaid,
                    'outstanding_amount' => $newOutstanding,
                    'fully_paid_date' => $fullyPaidDate,
                    'status' => $scheduleStatus,
                ]);

                $totalPrincipal += $principalAllocated;
                $totalInterest += $interestAllocated;
                $totalFees += $feesAllocated;
                $totalPenalty += $penaltyAllocated;
            }

            $repayment->update([
                'principal_amount' => round($totalPrincipal, 2),
                'interest_amount' => round($totalInterest, 2),
                'fees_amount' => round($totalFees, 2),
                'penalty_amount' => round($totalPenalty, 2),
                'unallocated_amount' => round($remainingAmount, 2),
            ]);

            /*
             * Reduce the running balances on the active loan account.
             */
            $loan = $repayment->loan()
                ->lockForUpdate()
                ->firstOrFail();

            $loan->update([
                'principal_balance' => max(
                    0,
                    round(
                        (float) $loan->principal_balance
                        - $totalPrincipal,
                        2
                    )
                ),

                'interest_balance' => max(
                    0,
                    round(
                        (float) $loan->interest_balance
                        - $totalInterest,
                        2
                    )
                ),

                'penalty_balance' => max(
                    0,
                    round(
                        (float) $loan->penalty_balance
                        - $totalPenalty,
                        2
                    )
                ),

                'principal_paid' => round(
                    (float) $loan->principal_paid
                    + $totalPrincipal,
                    2
                ),

                'interest_paid' => round(
                    (float) $loan->interest_paid
                    + $totalInterest,
                    2
                ),

                'penalties_paid' => round(
                    (float) $loan->penalties_paid
                    + $totalPenalty,
                    2
                ),

                'total_paid' => round(
                    (float) $loan->total_paid
                    + (float) $repayment->amount_paid
                    - $remainingAmount,
                    2
                ),

                'total_outstanding_balance' => max(
                    0,
                    round(
                        (float) $loan->total_outstanding_balance
                        - (
                            $totalPrincipal
                            + $totalInterest
                            + $totalPenalty
                        ),
                        2
                    )
                ),
            ]);

            return $repayment->fresh([
                'allocations.schedule',
                'loan',
            ]);
       });
    } 
}
<?php

namespace App\Services;

use App\Models\LoanApplications\Loan;
use App\Models\LoanApplications\LoanRepayment;
use App\Models\LoanApplications\LoanRepaymentAllocation;
use App\Models\LoanApplications\LoanRepaymentSchedule;
use App\Models\LoanApplications\LoanRestructure;
use App\Models\LoanApplications\LoanProduct;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LoanRestructuringService
{
    /** Replace unearned forecasts and build a forward amortization schedule. */
    public function restructure(Loan $loan, int $months, string $firstDueDate, string $reason): LoanRestructure
    {
        return DB::transaction(function () use ($loan, $months, $firstDueDate, $reason) {
            $loan = Loan::with('loanApplication.loanProduct')->whereKey($loan->id)->lockForUpdate()->firstOrFail();
            if (!in_array($loan->status, ['active', 'in_arrears', 'restructured'], true)) {
                throw ValidationException::withMessages(['loan' => 'Only active loans can be restructured.']);
            }
            if (!$loan->is_opening_balance && (float) $loan->amount_disbursed <= 0) {
                throw ValidationException::withMessages(['loan' => 'A loan must be disbursed before its repayment schedule can be restructured.']);
            }
            if (LoanRepayment::where('loan_id', $loan->id)->where('status', 'pending')->exists()) {
                throw ValidationException::withMessages(['loan' => 'Resolve pending repayments before restructuring this loan.']);
            }
            if (LoanRepaymentAllocation::where('loan_id', $loan->id)->where('status', 'active')->exists()) {
                throw ValidationException::withMessages(['loan' => 'A loan with allocated repayments cannot have its schedule replaced.']);
            }

            $product = optional($loan->loanApplication)->loanProduct;
            if (!$product) {
                throw ValidationException::withMessages(['loan' => 'The loan product is required to calculate the restructured interest.']);
            }

            $effectiveDate = today()->toDateString();
            $activeSchedules = LoanRepaymentSchedule::where('loan_id', $loan->id)
                ->whereIn('status', ['pending', 'partially_paid', 'overdue'])
                ->where('outstanding_amount', '>', 0)
                ->lockForUpdate()
                ->get();

            if ($loan->interest_deferred) {
                foreach ($activeSchedules as $schedule) {
                    if (!$schedule->interest_released_on
                        && round((float) $schedule->deferred_interest_due, 2) > 0
                        && Carbon::parse($schedule->due_date)->toDateString() <= $effectiveDate) {
                        app(JournalPostingService::class)->postLoanInterestRelease($schedule, $effectiveDate);
                        $schedule->refresh();
                    }
                }
            }

            $oldDeferredInterest = 0.0;
            $oldRecognizedInterest = 0.0;
            foreach ($activeSchedules as $schedule) {
                $futureInterest = $schedule->future_interest_due;
                $deferredInterest = round((float) $schedule->deferred_interest_due, 2);
                $isFutureDue = Carbon::parse($schedule->due_date)->toDateString() > $effectiveDate;

                if ($futureInterest !== null) {
                    if ($deferredInterest > 0 && !$schedule->interest_released_on) {
                        $oldDeferredInterest += $deferredInterest;
                    } elseif ($isFutureDue) {
                        $oldRecognizedInterest += (float) $futureInterest;
                    }
                } elseif ($loan->interest_deferred) {
                    if (!$schedule->interest_released_on) {
                        $oldDeferredInterest += $deferredInterest > 0
                            ? $deferredInterest
                            : (float) $schedule->interest_due;
                    }
                } elseif ($isFutureDue) {
                    $oldRecognizedInterest += (float) $schedule->interest_due;
                }
            }

            $oldDeferredInterest = round($oldDeferredInterest, 2);
            $oldRecognizedInterest = round($oldRecognizedInterest, 2);
            $carriedInterest = max(0, round(
                (float) $loan->interest_balance - $oldDeferredInterest - $oldRecognizedInterest,
                2
            ));

            // Origination fees are included in principal_balance and scheduled
            // separately. Opening-balance imports keep their fee balance separate.
            $capitalizedFees = $loan->is_opening_balance ? 0 : round($activeSchedules->sum(function ($schedule) {
                return max(0, (float) $schedule->fees_due - (float) $schedule->fees_paid);
            }), 2);
            $separateFees = round((float) $loan->fees_balance, 2);
            $penalty = round((float) $loan->penalty_balance, 2);
            $principal = round((float) $loan->principal_balance, 2);

            if ($principal + $carriedInterest + $capitalizedFees + $separateFees + $penalty <= 0) {
                throw ValidationException::withMessages(['loan' => 'There is no outstanding balance to schedule.']);
            }

            $nextNumber = ((int) LoanRepaymentSchedule::where('loan_id', $loan->id)->max('installment_number')) + 1;
            $schedule = app(LoanAmortizationService::class)->buildRestructureSchedule(
                $principal,
                $capitalizedFees,
                $carriedInterest,
                $separateFees,
                $penalty,
                $months,
                (float) $product->interest_rate,
                $product->interest_method,
                $product->interest_frequency,
                $firstDueDate,
                $nextNumber,
                (bool) $product->unearned_interest_account_id
            );

            $newInterestBalance = round($carriedInterest + $schedule['interest_total'], 2);
            $newTotalOutstanding = round(
                $principal + $newInterestBalance + $separateFees + $penalty,
                2
            );
            $newSchedules = [];
            foreach ($schedule['rows'] as $row) {
                $row['loan_id'] = $loan->id;
                $row['member_id'] = $loan->member_id;
                $row['remarks'] = 'Forward amortization schedule created by loan restructuring.';
                $row['created_at'] = now();
                $row['updated_at'] = now();
                $newSchedules[] = $row;
            }

            LoanRepaymentSchedule::where('loan_id', $loan->id)
                ->whereIn('status', ['pending', 'partially_paid', 'overdue'])
                ->update(['status' => 'waived', 'outstanding_amount' => 0]);
            LoanRepaymentSchedule::insert($newSchedules);

            $previousStatus = $loan->status;
            $loan->update([
                'repayment_period_months' => $months,
                'monthly_installment' => $schedule['monthly_installment'],
                'first_repayment_date' => $firstDueDate,
                'maturity_date' => $schedule['maturity_date'],
                'interest_rate' => $product->interest_rate,
                'interest_method' => $product->interest_method,
                'interest_balance' => $newInterestBalance,
                'total_outstanding_balance' => $newTotalOutstanding,
                'interest_deferred' => (bool) $product->unearned_interest_account_id && $schedule['interest_total'] > 0,
                'status' => 'restructured',
            ]);

            $restructure = LoanRestructure::create([
                'loan_id' => $loan->id,
                'member_id' => $loan->member_id,
                'effective_date' => $effectiveDate,
                'first_repayment_date' => $firstDueDate,
                'remaining_months' => $months,
                'installment_amount' => $schedule['monthly_installment'],
                'principal_balance' => $principal,
                'interest_balance' => $newInterestBalance,
                'fees_balance' => round($capitalizedFees + $separateFees, 2),
                'penalty_balance' => $penalty,
                'previous_status' => $previousStatus,
                'reason' => $reason,
                'performed_by' => Auth::id(),
            ]);

            app(JournalPostingService::class)->postLoanRestructureInterest(
                $restructure,
                $oldDeferredInterest,
                $oldRecognizedInterest,
                $schedule['interest_total'],
                $effectiveDate
            );

            return $restructure;
        });
    }
}

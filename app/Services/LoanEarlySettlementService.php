<?php

namespace App\Services;

use App\Models\LoanApplications\Loan;
use App\Models\LoanApplications\LoanRepaymentSchedule;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class LoanEarlySettlementService
{
    /**
     * Quote the balance as of the last installment due on or before the
     * settlement date. Interest on later installments is treated as unearned
     * and waived; this MVP rule does not prorate interest by day.
     */
    public function quote(Loan $loan, $settlementDate, ?Collection $schedules = null): array
    {
        $date = Carbon::parse($settlementDate)->toDateString();
        $schedules = $schedules ?: LoanRepaymentSchedule::where('loan_id', $loan->id)
            ->where('status', '!=', 'waived')
            ->whereDate('due_date', '>', $date)
            ->get();

        $futureInterest = 0.0;
        foreach ($schedules as $schedule) {
            if (Carbon::parse($schedule->due_date)->toDateString() <= $date || $schedule->status === 'waived') {
                continue;
            }
            $futureInterest += $this->unpaidFutureInterest($schedule);
        }

        $interestWaived = round(min(max(0, (float) $loan->interest_balance), $futureInterest), 2);
        $interestDue = round(max(0, (float) $loan->interest_balance - $interestWaived), 2);
        $principal = round(max(0, (float) $loan->principal_balance), 2);
        $fees = round(max(0, (float) $loan->fees_balance), 2);
        $penalties = round(max(0, (float) $loan->penalty_balance), 2);

        return [
            'settlement_date' => $date,
            'principal' => $principal,
            'interest_due' => $interestDue,
            'interest_waived' => $interestWaived,
            'fees' => $fees,
            'penalties' => $penalties,
            'settlement_amount' => round($principal + $interestDue + $fees + $penalties, 2),
        ];
    }

    /**
     * Restructured schedules separate carried accrued interest from new
     * future interest. Original schedules have a NULL future_interest_due,
     * which means their scheduled interest is all forecast interest.
     */
    public function unpaidFutureInterest(LoanRepaymentSchedule $schedule): float
    {
        $interestDue = max(0, (float) $schedule->interest_due);
        $interestPaid = max(0, (float) $schedule->interest_paid);
        if ($schedule->future_interest_due === null) {
            return round(max(0, $interestDue - $interestPaid), 2);
        }

        $futureInterest = min($interestDue, max(0, (float) $schedule->future_interest_due));
        $carriedInterest = max(0, $interestDue - $futureInterest);
        $paidFutureInterest = max(0, $interestPaid - $carriedInterest);

        return round(max(0, $futureInterest - $paidFutureInterest), 2);
    }
}

<?php

namespace App\Services;

use App\Models\LoanApplications\Loan;
use App\Models\LoanApplications\LoanRepayment;
use App\Models\LoanApplications\LoanRepaymentAllocation;
use App\Models\LoanApplications\LoanRepaymentSchedule;
use App\Models\LoanApplications\LoanRestructure;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LoanRestructuringService
{
    /** Create a forward-only, equal-component schedule without changing balances. */
    public function restructure(Loan $loan, int $months, string $firstDueDate, string $reason): LoanRestructure
    {
        return DB::transaction(function () use ($loan, $months, $firstDueDate, $reason) {
            $loan = Loan::whereKey($loan->id)->lockForUpdate()->firstOrFail();
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

            $principal = round((float) $loan->principal_balance, 2);
            $interest = round((float) $loan->interest_balance, 2);
            $fees = round((float) $loan->fees_balance, 2);
            $penalty = round((float) $loan->penalty_balance, 2);
            if ($principal + $interest + $fees + $penalty <= 0) {
                throw ValidationException::withMessages(['loan' => 'There is no outstanding balance to schedule.']);
            }

            $nextNumber = ((int) LoanRepaymentSchedule::where('loan_id', $loan->id)->max('installment_number')) + 1;
            LoanRepaymentSchedule::where('loan_id', $loan->id)
                ->whereIn('status', ['pending', 'partially_paid', 'overdue'])
                ->update(['status' => 'waived', 'outstanding_amount' => 0]);

            $principalParts = $this->split($principal, $months);
            $interestParts = $this->split($interest, $months);
            $feeParts = $this->split($fees, $months);
            $penaltyParts = $this->split($penalty, $months);
            $date = Carbon::parse($firstDueDate);
            $installments = [];
            $principalTrack = $principal;

            for ($i = 0; $i < $months; $i++) {
                $principalDue = $principalParts[$i];
                $interestDue = $interestParts[$i];
                $feeDue = $feeParts[$i];
                $penaltyDue = $penaltyParts[$i];
                $totalDue = round($principalDue + $interestDue + $feeDue + $penaltyDue, 2);
                $principalTrack = max(0, round($principalTrack - $principalDue, 2));
                $date = $i === 0 ? $date : $date->copy()->addMonthNoOverflow();

                $installments[] = [
                    'loan_id' => $loan->id,
                    'member_id' => $loan->member_id,
                    'installment_number' => $nextNumber + $i,
                    'due_date' => $date->toDateString(),
                    'opening_principal_balance' => round($principalTrack + $principalDue, 2),
                    'principal_due' => $principalDue,
                    'interest_due' => $interestDue,
                    'fees_due' => $feeDue,
                    'penalty_due' => $penaltyDue,
                    'total_due' => $totalDue,
                    'closing_principal_balance' => $principalTrack,
                    'outstanding_amount' => $totalDue,
                    'status' => 'pending',
                    'remarks' => 'Forward schedule created by loan restructuring.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            LoanRepaymentSchedule::insert($installments);

            $installmentAmount = round(array_sum(array_column($installments, 'total_due')) / $months, 2);
            $previousStatus = $loan->status;
            $lastDate = end($installments)['due_date'];
            $loan->update([
                'repayment_period_months' => $months,
                'monthly_installment' => $installmentAmount,
                'first_repayment_date' => $firstDueDate,
                'maturity_date' => $lastDate,
                'status' => 'restructured',
            ]);

            return LoanRestructure::create([
                'loan_id' => $loan->id,
                'member_id' => $loan->member_id,
                'effective_date' => today()->toDateString(),
                'first_repayment_date' => $firstDueDate,
                'remaining_months' => $months,
                'installment_amount' => $installmentAmount,
                'principal_balance' => $principal,
                'interest_balance' => $interest,
                'fees_balance' => $fees,
                'penalty_balance' => $penalty,
                'previous_status' => $previousStatus,
                'reason' => $reason,
                'performed_by' => Auth::id(),
            ]);
        });
    }

    private function split(float $amount, int $count): array
    {
        $cents = (int) round($amount * 100);
        $base = intdiv($cents, $count);
        $remainder = $cents % $count;
        $parts = [];
        for ($i = 0; $i < $count; $i++) {
            $parts[] = ($base + ($i < $remainder ? 1 : 0)) / 100;
        }
        return $parts;
    }
}

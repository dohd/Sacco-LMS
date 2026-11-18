<?php

namespace App\Services;

use App\Models\LoanApplications\LoanProduct;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

/** Builds monthly loan schedules using the terms configured on a loan product. */
class LoanAmortizationService
{
    public function feesFor(LoanProduct $product, float $principal): float
    {
        $fees = (float) ($product->application_fee ?? 0)
            + ($principal * (float) $product->processing_fee_percentage / 100)
            + ($principal * (float) $product->insurance_fee_percentage / 100);

        return $this->money($fees);
    }

    public function estimateInstallment(float $principal, int $months, LoanProduct $product): float
    {
        $schedule = $this->buildOriginationSchedule(
            $principal,
            $this->feesFor($product, $principal),
            $months,
            $product->interest_rate,
            $product->interest_method,
            $product->interest_frequency,
            Carbon::today()->addMonthNoOverflow()->toDateString()
        );

        return $schedule['monthly_installment'];
    }

    /**
     * Create an origination schedule. Fees are financed into the amortizing
     * balance but remain a separate installment component for accounting.
     */
    public function buildOriginationSchedule(
        float $principal,
        float $fees,
        int $months,
        float $annualOrMonthlyRate,
        string $method,
        string $frequency,
        string $firstDueDate
    ): array {
        $principal = $this->money($principal);
        $fees = $this->money($fees);
        $balance = $this->money($principal + $fees);

        if ($principal <= 0 || $months < 1) {
            throw ValidationException::withMessages(['repayment_period_months' => 'A positive loan amount and repayment period are required to build a schedule.']);
        }

        $feeParts = $this->split($fees, $months);
        $periodicRate = $this->periodicRate($annualOrMonthlyRate, $frequency);
        $flatInterest = $method === 'flat_rate' || $frequency === 'one_time';

        if ($flatInterest) {
            $interestTotal = $this->flatInterest($balance, $annualOrMonthlyRate, $frequency, $months);
            $interestParts = $this->split($interestTotal, $months);
            $principalParts = $this->split($principal, $months);
            $rows = [];
            $dueDate = Carbon::parse($firstDueDate);

            for ($index = 0; $index < $months; $index++) {
                $principalDue = $principalParts[$index];
                $feeDue = $feeParts[$index];
                $interestDue = $interestParts[$index];
                $opening = $balance;
                $balance = max(0, $this->money($balance - $principalDue - $feeDue));
                $rows[] = $this->row(
                    $index + 1,
                    $dueDate,
                    $opening,
                    $principalDue,
                    $interestDue,
                    $feeDue,
                    0,
                    $balance,
                    0
                );
                $dueDate = $dueDate->copy()->addMonthNoOverflow();
            }

            return $this->result($rows, $interestTotal);
        }

        $payment = $this->paymentFor($balance, $months, $periodicRate);
        $rows = [];
        $dueDate = Carbon::parse($firstDueDate);
        $interestTotal = 0.0;

        for ($index = 0; $index < $months; $index++) {
            $opening = $balance;
            $interestDue = $this->money($opening * $periodicRate);
            $feeDue = $feeParts[$index];
            $principalDue = $index === $months - 1
                ? max(0, $this->money($opening - $feeDue))
                : max(0, $this->money($payment - $interestDue - $feeDue));
            $balance = max(0, $this->money($opening - $principalDue - $feeDue));
            $interestTotal = $this->money($interestTotal + $interestDue);

            $rows[] = $this->row(
                $index + 1,
                $dueDate,
                $opening,
                $principalDue,
                $interestDue,
                $feeDue,
                0,
                $balance,
                0
            );
            $dueDate = $dueDate->copy()->addMonthNoOverflow();
        }

        return $this->result($rows, $interestTotal);
    }

    /**
     * Build an opening-balance restructuring schedule. Existing accrued
     * interest is carried into the schedule; new interest is calculated from
     * the current product rate and tracked separately for ledger release.
     */
    public function buildRestructureSchedule(
        float $amortizedBalance,
        float $capitalizedFees,
        float $carriedInterest,
        float $separateFees,
        float $penalties,
        int $months,
        float $rate,
        string $method,
        string $frequency,
        string $firstDueDate,
        int $firstInstallmentNumber,
        bool $deferFutureInterest
    ): array {
        $amortizedBalance = $this->money($amortizedBalance);
        $capitalizedFees = min($amortizedBalance, $this->money($capitalizedFees));
        $carriedInterest = $this->money($carriedInterest);
        $separateFees = $this->money($separateFees);
        $penalties = $this->money($penalties);

        if ($amortizedBalance <= 0 || $months < 1) {
            throw ValidationException::withMessages(['repayment_period_months' => 'A positive outstanding principal and repayment period are required to build a schedule.']);
        }

        $capitalizedFeeParts = $this->split($capitalizedFees, $months);
        $separateFeeParts = $this->split($separateFees, $months);
        $penaltyParts = $this->split($penalties, $months);
        $carriedInterestParts = $this->split($carriedInterest, $months);
        $periodicRate = $this->periodicRate($rate, $frequency);
        $flatInterest = $method === 'flat_rate' || $frequency === 'one_time';
        $rows = [];
        $dueDate = Carbon::parse($firstDueDate);
        $balance = $amortizedBalance;
        $futureInterestTotal = 0.0;

        if ($flatInterest) {
            $futureInterestTotal = $this->flatInterest($amortizedBalance, $rate, $frequency, $months);
            $futureInterestParts = $this->split($futureInterestTotal, $months);
            $principalParts = $this->split($amortizedBalance - $capitalizedFees, $months);

            for ($index = 0; $index < $months; $index++) {
                $opening = $balance;
                $principalDue = $principalParts[$index];
                $feeDue = $this->money($capitalizedFeeParts[$index] + $separateFeeParts[$index]);
                $penaltyDue = $penaltyParts[$index];
                $futureInterestDue = $futureInterestParts[$index];
                $interestDue = $this->money($carriedInterestParts[$index] + $futureInterestDue);
                $balance = max(0, $this->money($balance - $principalDue - $capitalizedFeeParts[$index]));

                $rows[] = $this->row(
                    $firstInstallmentNumber + $index,
                    $dueDate,
                    $opening,
                    $principalDue,
                    $interestDue,
                    $feeDue,
                    $penaltyDue,
                    $balance,
                    $deferFutureInterest ? $futureInterestDue : 0,
                    $futureInterestDue
                );
                $dueDate = $dueDate->copy()->addMonthNoOverflow();
            }
        } else {
            $payment = $this->paymentFor($amortizedBalance, $months, $periodicRate);

            for ($index = 0; $index < $months; $index++) {
                $opening = $balance;
                $futureInterestDue = $this->money($opening * $periodicRate);
                $capitalizedFeeDue = $capitalizedFeeParts[$index];
                $principalDue = $index === $months - 1
                    ? max(0, $this->money($balance - $capitalizedFeeDue))
                    : max(0, $this->money($payment - $futureInterestDue - $capitalizedFeeDue));
                $balance = max(0, $this->money($balance - $principalDue - $capitalizedFeeDue));
                $feeDue = $this->money($capitalizedFeeDue + $separateFeeParts[$index]);
                $penaltyDue = $penaltyParts[$index];
                $interestDue = $this->money($carriedInterestParts[$index] + $futureInterestDue);
                $futureInterestTotal = $this->money($futureInterestTotal + $futureInterestDue);

                $rows[] = $this->row(
                    $firstInstallmentNumber + $index,
                    $dueDate,
                    $opening,
                    $principalDue,
                    $interestDue,
                    $feeDue,
                    $penaltyDue,
                    $balance,
                    $deferFutureInterest ? $futureInterestDue : 0,
                    $futureInterestDue
                );
                $dueDate = $dueDate->copy()->addMonthNoOverflow();
            }
        }

        return $this->result($rows, $futureInterestTotal);
    }

    private function row(
        int $number,
        Carbon $dueDate,
        float $opening,
        float $principal,
        float $interest,
        float $fees,
        float $penalty,
        float $closing,
        float $deferredInterest,
        float $futureInterest = 0
    ): array {
        return [
            'installment_number' => $number,
            'due_date' => $dueDate->toDateString(),
            'opening_principal_balance' => $this->money($opening),
            'principal_due' => $this->money($principal),
            'interest_due' => $this->money($interest),
            'fees_due' => $this->money($fees),
            'penalty_due' => $this->money($penalty),
            'total_due' => $this->money($principal + $interest + $fees + $penalty),
            'closing_principal_balance' => $this->money($closing),
            'outstanding_amount' => $this->money($principal + $interest + $fees + $penalty),
            'deferred_interest_due' => $this->money($deferredInterest),
            'future_interest_due' => $this->money($futureInterest),
            'status' => 'pending',
        ];
    }

    private function result(array $rows, float $interestTotal): array
    {
        $installment = $rows ? (float) $rows[0]['total_due'] : 0.0;

        return [
            'rows' => $rows,
            'interest_total' => $this->money($interestTotal),
            'monthly_installment' => $this->money($installment),
            'maturity_date' => $rows ? end($rows)['due_date'] : null,
        ];
    }

    private function paymentFor(float $principal, int $months, float $periodicRate): float
    {
        if ($periodicRate <= 0) {
            return $this->money($principal / $months);
        }

        $factor = pow(1 + $periodicRate, $months);

        return $this->money($principal * $periodicRate * $factor / ($factor - 1));
    }

    private function periodicRate(float $rate, string $frequency): float
    {
        $rate = $rate / 100;
        if ($frequency === 'annual') {
            return $rate / 12;
        }

        return $frequency === 'monthly' ? $rate : 0.0;
    }

    private function flatInterest(float $principal, float $rate, string $frequency, int $months): float
    {
        $rate = $rate / 100;
        $factor = $frequency === 'annual'
            ? $months / 12
            : ($frequency === 'monthly' ? $months : 1);

        return $this->money($principal * $rate * $factor);
    }

    private function split(float $amount, int $count): array
    {
        if ($count < 1) {
            throw ValidationException::withMessages(['repayment_period_months' => 'The repayment period must be at least one month.']);
        }

        $cents = (int) round($this->money($amount) * 100);
        $base = intdiv($cents, $count);
        $remainder = $cents % $count;
        $parts = [];

        for ($index = 0; $index < $count; $index++) {
            $parts[] = ($base + ($index < $remainder ? 1 : 0)) / 100;
        }

        return $parts;
    }

    private function money(float $amount): float
    {
        return round($amount, 2);
    }
}

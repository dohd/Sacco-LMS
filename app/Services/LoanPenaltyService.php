<?php

namespace App\Services;

use App\Models\LoanApplications\Loan;
use App\Models\LoanApplications\LoanRepaymentSchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Keeps loan arrears up to date and assesses penalties.
 *
 * An installment becomes overdue the day after its due date. Once the
 * product's grace period has also passed, a one-off penalty of
 * penalty_rate % of the installment's unpaid principal, interest and fees is
 * added to the installment and the loan, and accrued in the ledger. Each
 * installment is assessed once, whatever number of times this runs.
 */
class LoanPenaltyService
{
    /** @var JournalPostingService */
    private $postingService;

    public function __construct(JournalPostingService $postingService)
    {
        $this->postingService = $postingService;
    }

    /**
     * @return array{overdue:int, penalties:int, total:float, failed:int}
     */
    public function run(Carbon $asOf): array
    {
        $result = ['overdue' => 0, 'penalties' => 0, 'total' => 0.0, 'failed' => 0];
        $today = $asOf->toDateString();

        // Loans with an installment past due, plus loans still flagged as in arrears
        // so that paid-up loans are cleared.
        $loanIds = Loan::whereIn('status', ['active', 'in_arrears'])
            ->where(function ($query) use ($today) {
                $query->where('status', 'in_arrears')
                    ->orWhere('arrears_amount', '>', 0)
                    ->orWhereHas('repaymentSchedules', fn($q) => $q->whereDate('due_date', '<', $today)
                        ->whereIn('status', ['pending', 'partially_paid', 'overdue']));
            })
            ->orderBy('id')
            ->pluck('id');

        foreach ($loanIds as $loanId) {
            try {
                $summary = DB::transaction(fn() => $this->processLoan($loanId, $asOf));
                $result['overdue'] += $summary['overdue'];
                $result['penalties'] += $summary['penalties'];
                $result['total'] = round($result['total'] + $summary['total'], 2);
            } catch (\Throwable $e) {
                // One bad loan (for example a closed period) must not stop the rest.
                $result['failed']++;
                Log::error('Loan penalty assessment failed for loan ' . $loanId . ': ' . $e->getMessage());
            }
        }

        return $result;
    }

    private function processLoan(int $loanId, Carbon $asOf): array
    {
        $summary = ['overdue' => 0, 'penalties' => 0, 'total' => 0.0];
        $today = $asOf->toDateString();

        $loan = Loan::with('loanApplication.loanProduct')->whereKey($loanId)->lockForUpdate()->firstOrFail();
        if (!in_array($loan->status, ['active', 'in_arrears'], true)) {
            return $summary;
        }

        $product = optional($loan->loanApplication)->loanProduct;
        $rate = $product ? (float) $product->penalty_rate : 0.0;
        $grace = $product ? (int) $product->grace_period_days : 0;

        $schedules = LoanRepaymentSchedule::where('loan_id', $loan->id)
            ->whereDate('due_date', '<', $today)
            ->whereIn('status', ['pending', 'partially_paid', 'overdue'])
            ->orderBy('due_date')
            ->orderBy('installment_number')
            ->lockForUpdate()
            ->get();

        foreach ($schedules as $schedule) {
            if (round((float) $schedule->outstanding_amount, 2) <= 0) {
                continue;
            }

            if ($schedule->status !== 'overdue') {
                $schedule->update(['status' => 'overdue']);
                $summary['overdue']++;
            }

            $graceEnds = Carbon::parse($schedule->due_date)->addDays($grace);
            if ($rate <= 0 || $schedule->penalty_assessed_on || !$asOf->gt($graceEnds)) {
                continue;
            }

            $base = max(0, round(
                ((float) $schedule->principal_due - (float) $schedule->principal_paid)
                + ((float) $schedule->interest_due - (float) $schedule->interest_paid)
                + ((float) $schedule->fees_due - (float) $schedule->fees_paid),
                2
            ));
            $penalty = round($base * $rate / 100, 2);
            if ($penalty <= 0) {
                continue;
            }

            $schedule->update([
                'penalty_due' => round((float) $schedule->penalty_due + $penalty, 2),
                'total_due' => round((float) $schedule->total_due + $penalty, 2),
                'outstanding_amount' => round((float) $schedule->outstanding_amount + $penalty, 2),
                'penalty_assessed_on' => $today,
            ]);
            $loan->update([
                'penalty_balance' => round((float) $loan->penalty_balance + $penalty, 2),
                'total_outstanding_balance' => round((float) $loan->total_outstanding_balance + $penalty, 2),
            ]);

            $this->postingService->postLoanPenaltyAccrual($schedule->fresh(), $penalty, $today);
            $summary['penalties']++;
            $summary['total'] = round($summary['total'] + $penalty, 2);
        }

        $this->refreshArrears($loan, $asOf);

        return $summary;
    }

    /**
     * Arrears are everything still owed on installments that are past due.
     */
    private function refreshArrears(Loan $loan, Carbon $asOf): void
    {
        $due = LoanRepaymentSchedule::where('loan_id', $loan->id)
            ->whereDate('due_date', '<', $asOf->toDateString())
            ->whereIn('status', ['pending', 'partially_paid', 'overdue'])
            ->where('outstanding_amount', '>', 0);

        $arrears = round((float) (clone $due)->sum('outstanding_amount'), 2);
        $oldest = (clone $due)->min('due_date');
        $days = ($arrears > 0 && $oldest) ? Carbon::parse($oldest)->diffInDays($asOf) : 0;

        $loan->update([
            'arrears_amount' => $arrears,
            'days_in_arrears' => $days,
            'status' => $arrears > 0 ? 'in_arrears' : 'active',
        ]);
    }
}

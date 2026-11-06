<?php

namespace App\Services;

use App\Models\LoanApplications\Loan;
use App\Models\LoanApplications\LoanRepaymentSchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Releases interest from unearned interest to income as installments fall due.
 *
 * Only loans disbursed (or reclassified) under an unearned interest account
 * take part. An installment is released once, on or after its due date,
 * whether or not it has been paid. Written-off and closed loans are skipped.
 */
class LoanInterestReleaseService
{
    /** @var JournalPostingService */
    private $postingService;

    public function __construct(JournalPostingService $postingService)
    {
        $this->postingService = $postingService;
    }

    /**
     * @return array{released:int, total:float, failed:int}
     */
    public function run(Carbon $asOf): array
    {
        $result = ['released' => 0, 'total' => 0.0, 'failed' => 0];
        $today = $asOf->toDateString();

        $scheduleIds = LoanRepaymentSchedule::whereIn('loan_id', Loan::where('interest_deferred', true)
                ->whereIn('status', ['active', 'in_arrears', 'fully_paid'])->select('id'))
            ->whereNull('interest_released_on')
            ->whereDate('due_date', '<=', $today)
            ->where('status', '!=', 'waived')
            ->orderBy('due_date')->orderBy('id')
            ->pluck('id');

        foreach ($scheduleIds as $scheduleId) {
            try {
                $amount = DB::transaction(function () use ($scheduleId, $today) {
                    $schedule = LoanRepaymentSchedule::findOrFail($scheduleId);
                    $loan = Loan::whereKey($schedule->loan_id)->lockForUpdate()->firstOrFail();
                    if (!$loan->interest_deferred || !in_array($loan->status, ['active', 'in_arrears', 'fully_paid'], true)) {
                        return null;
                    }
                    $entry = $this->postingService->postLoanInterestRelease($schedule, $today);

                    return $entry ? round((float) $schedule->interest_due, 2) : 0.0;
                });
                if ($amount !== null) {
                    $result['released']++;
                    $result['total'] = round($result['total'] + $amount, 2);
                }
            } catch (\Throwable $e) {
                // One bad installment (for example a closed period) must not stop the rest.
                $result['failed']++;
                Log::error('Interest release failed for schedule ' . $scheduleId . ': ' . $e->getMessage());
            }
        }

        return $result;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\LoanApplications\Loan;
use App\Services\JournalPostingService;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * One-off: moves interest not yet due on loans booked before unearned interest
 * existed out of income and into the product's unearned interest account.
 */
class ReclassifyLoanInterest extends Command
{
    protected $signature = 'loans:reclassify-interest {--date= : Treat interest due after this date as unearned (default today)} {--dry-run : Show what would be moved without posting}';

    protected $description = 'Move interest not yet due on existing loans from income to unearned interest';

    public function handle(JournalPostingService $posting): int
    {
        $asOf = ($this->option('date') ? Carbon::parse($this->option('date')) : Carbon::today())->toDateString();
        $dry = (bool) $this->option('dry-run');

        $loans = Loan::where('interest_deferred', false)
            ->whereIn('status', ['active', 'in_arrears', 'fully_paid'])
            ->whereHas('loanApplication.loanProduct', fn($q) => $q->whereNotNull('unearned_interest_account_id'))
            ->orderBy('id')->get();

        $rows = [];
        $failed = 0;
        foreach ($loans as $loan) {
            try {
                if ($dry) {
                    $amount = (float) \App\Models\LoanApplications\LoanRepaymentSchedule::where('loan_id', $loan->id)
                        ->whereNull('interest_released_on')->whereDate('due_date', '>', $asOf)->sum('interest_due');
                } else {
                    $amount = $posting->reclassifyLoanInterest($loan, $asOf);
                }
                if ($amount !== null) {
                    $rows[] = [$loan->loan_number, number_format((float) $amount, 2)];
                }
            } catch (\Throwable $e) {
                $failed++;
                $this->error($loan->loan_number . ': ' . $e->getMessage());
            }
        }

        $this->table(['Loan', $dry ? 'Would move' : 'Moved'], $rows);

        return $failed ? 1 : 0;
    }
}

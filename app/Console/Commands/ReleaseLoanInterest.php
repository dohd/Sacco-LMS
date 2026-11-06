<?php

namespace App\Console\Commands;

use App\Services\LoanInterestReleaseService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ReleaseLoanInterest extends Command
{
    protected $signature = 'loans:release-interest {--date= : Release installments due on or before this date (default today)}';

    protected $description = 'Release interest from unearned interest to income for installments that have fallen due';

    public function handle(LoanInterestReleaseService $service): int
    {
        $asOf = $this->option('date') ? Carbon::parse($this->option('date')) : Carbon::today();
        $result = $service->run($asOf);

        $this->info("Released {$result['released']} installment(s), total interest " . number_format($result['total'], 2) . '.');
        if ($result['failed'] > 0) {
            $this->error("{$result['failed']} installment(s) failed; see the log.");
            return 1;
        }

        return 0;
    }
}

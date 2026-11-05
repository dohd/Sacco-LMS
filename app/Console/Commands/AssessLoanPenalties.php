<?php

namespace App\Console\Commands;

use App\Services\LoanPenaltyService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AssessLoanPenalties extends Command
{
    protected $signature = 'loans:assess-penalties
        {--date= : Assess as at this date (Y-m-d). Defaults to today.}';

    protected $description = 'Mark overdue loan installments, update loan arrears and assess penalties';

    public function handle(LoanPenaltyService $service)
    {
        $date = $this->option('date') ? Carbon::parse($this->option('date'))->startOfDay() : Carbon::today();

        $result = $service->run($date);
        $this->info(sprintf(
            'Loan arrears as at %s: %d installment(s) newly overdue, %d penalt%s assessed totalling %s, %d loan(s) failed.',
            $date->toDateString(),
            $result['overdue'],
            $result['penalties'],
            $result['penalties'] === 1 ? 'y' : 'ies',
            number_format($result['total'], 2),
            $result['failed']
        ));

        return $result['failed'] > 0 ? 1 : 0;
    }
}

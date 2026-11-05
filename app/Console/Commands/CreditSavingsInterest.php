<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\SavingsInterestService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CreditSavingsInterest extends Command
{
    protected $signature = 'savings:credit-interest
        {--date= : Interest period end date (Y-m-d). Defaults to yesterday.}
        {--user= : User id recorded as the author of the credits. Defaults to the first sacco_admin.}';

    protected $description = 'Calculate and credit savings interest for products whose interest period ended on the given date';

    public function handle(SavingsInterestService $service)
    {
        $date = $this->option('date') ? Carbon::parse($this->option('date'))->startOfDay() : Carbon::yesterday();

        $actorId = $this->option('user') ?: DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('roles.name', 'sacco_admin')
            ->where('model_has_roles.model_type', User::class)
            ->value('model_id');
        if (!$actorId) {
            $this->error('No sacco_admin user found to record the interest credits against. Pass --user=ID.');

            return 1;
        }

        $result = $service->run($date, (int) $actorId);
        $this->info(sprintf(
            'Interest for period ending %s: %d account(s) credited, %d skipped, %d failed, total %s.',
            $date->toDateString(),
            $result['credited'],
            $result['skipped'],
            $result['failed'],
            number_format($result['total'], 2)
        ));

        return $result['failed'] > 0 ? 1 : 0;
    }
}

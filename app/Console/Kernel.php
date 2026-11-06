<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Runs daily; credits interest only for products whose period ended yesterday.
        $schedule->command('savings:credit-interest')->dailyAt('00:30')->withoutOverlapping();
        // Marks overdue installments, refreshes loan arrears and assesses penalties.
        $schedule->command('loans:assess-penalties')->dailyAt('01:00')->withoutOverlapping();

        // Demo copies only: wipes the demo data each night. Off unless DEMO_RESET_ENABLED=true.
        if (config('demo.reset_enabled')) {
            $schedule->command('demo:reset --force')->dailyAt(config('demo.reset_time'))
                ->environments(config('demo.allowed_environments'))->withoutOverlapping();
        }
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}

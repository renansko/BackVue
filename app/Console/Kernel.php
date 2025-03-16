<?php

namespace App\Console;

use App\Jobs\SendNewsEmailJob;
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
        // Run the job daily at 8:00 AM
        $schedule->job(new SendNewsEmailJob)->daily()->at('08:00');
        
        // Or hourly if you need more frequent updates
        // $schedule->job(new SendNewsEmailJob)->hourly();
        
        // Or with custom frequency
        // $schedule->job(new SendNewsEmailJob)->cron('0 */4 * * *'); // Every 4 hours
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

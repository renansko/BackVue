<?php

namespace App\Console;

use App\Jobs\getNewsJob;
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
        // $schedule->job(new getNewsJob)->daily()->at('08:00');
        $schedule->job(new getNewsJob)->everyMinute();
        
        //Verify the news table every day a prune operation
        $schedule->command('model:prune', [
            '--model' => 'App\Models\News',
        ])->daily();
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

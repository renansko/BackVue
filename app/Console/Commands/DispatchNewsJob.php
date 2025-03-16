<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DispatchNewsJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dispatch:news'; // Change this line

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch job to fetch and process UOL tech news';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Dispatching news job...');
        dispatch(new \App\Jobs\SendNewsEmailJob());
        $this->info('Job dispatched!');
        
        return Command::SUCCESS;
    }
}
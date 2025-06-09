<?php

namespace Alfan06\LaravelHealthcheck\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SchedulerLog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'healthcheck:scheduler-log';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a log entry for the scheduler to check if the scheduler is running.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $context = [
            'scheduler_running' => true,
            'include_in_metrics' => false,
        ];

        Log::info('Scheduler is running.', $context);
    }
}

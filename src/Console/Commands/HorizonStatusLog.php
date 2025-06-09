<?php

namespace Alfan06\LaravelHealthcheck\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;

class HorizonStatusLog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'healthcheck:horizon-log';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a log entry for Horizon status.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $isHorizonRunning = false;
        $message = 'Horizon is not running.';
        if (config('queue.default') === 'redis') {
            $horizonCurrentStatus = $this->horizonCurrentStatus();
            $message = 'Horizon is ' . $horizonCurrentStatus;
            if ($horizonCurrentStatus === 'running') {
                $isHorizonRunning = true;
            }
        }

        $context = [
            'horizon_running' => $isHorizonRunning,
            'include_in_metrics' => false,
        ];

        if ($isHorizonRunning) {
            Log::info($message, $context);
        } else {
            Log::error($message, $context);
        }
    }

    protected function horizonCurrentStatus(): string
    {
        if (!$masters = app(MasterSupervisorRepository::class)->all()) {
            return 'inactive';
        }

        return collect($masters)->every(function ($master) {
            return $master->status === 'paused';
        }) ? 'paused' : 'running';
    }
}

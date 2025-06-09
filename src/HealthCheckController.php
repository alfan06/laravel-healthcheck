<?php

namespace Alfan06\LaravelHealthcheck;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;

class HealthCheckController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $status = 200; // Default status code is OK

        $response = [];

        // Check database connection
        if (config('healthcheck.database', true) === true) {
            try {
                DB::connection()->getPdo();
                $dbStatus = 'OK';
            } catch (Exception $e) {
                $status = 500;
                // Add logs for cloudwatch to monitor
                Log::error('Unable to connect to Database', [
                    'is_database_connected' => false,
                    'include_in_metrics' => false,
                ]);
                $dbStatus = 'Unable to connect to the database: ' . $e->getMessage();
            }
            $response['db_status'] = $dbStatus;
        }

        // Check Redis connection
        if (config('healthcheck.redis', true) === true) {
            try {
                Redis::ping();
                $redisStatus = 'OK';
            } catch (Exception $e) {
                $status = 500;
                $redisStatus = 'Unable to connect to Redis: ' . $e->getMessage();

                // Add logs for cloudwatch to monitor
                Log::error('Unable to connect to Redis', [
                    'is_redis_connected' => false,
                    'include_in_metrics' => false,
                ]);
            }
            $response['redis_status'] = $redisStatus;
        }

        // Check Horizon status
        if (config('healthcheck.horizon', true) === true) {
            $horizonStatus = 'Queue driver is not Redis';
            if (config('queue.default') === 'redis') {
                $horizonCurrentStatus = $this->horizonCurrentStatus();
                $horizonStatus = 'Horizon is currently ' . $horizonCurrentStatus;
                if ($horizonCurrentStatus === 'running') {
                    $horizonStatus = 'OK';
                }
            }
            if ($horizonStatus !== 'OK') {
                // Add logs for cloudwatch to monitor
                Log::error($horizonStatus, [
                    'is_horizon_running' => false,
                    'include_in_metrics' => false,
                ]);
            }
            $response['horizon_status'] = $horizonStatus;
        }

        if (config('healthcheck.scheduler', false)) {
            $response['scheduler_status'] = 'OK';
            $response['scheduler_last_run'] = $this->getLastRun()?->format('Y-m-d H:i:s');

            // Check if the last run was more than or equal to 2 minutes ago
            if (empty($this->getLastRun()) || $this->getLastRun()?->diffInMinutes(Carbon::now()) >= 2) {
                $response['scheduler_status'] = 'Scheduler is not running';
                Log::error('Scheduler is not running.', [
                    'is_scheduler_running' => false,
                    'last_run' => $this->getLastRun()?->format('Y-m-d H:i:s'),
                    'include_in_metrics' => false,
                ]);
            }
        }

        // Assign status to the response but put it in the beginning of the array
        $response = array_merge(['status' => $status], $response);

        return response()->json($response, $status);
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

    private function getLastRun(): ?Carbon
    {
        return Cache::get('scheduler_last_run');
    }
}

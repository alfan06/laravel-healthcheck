<?php

namespace Coreproc\LaravelHealthcheck;

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

        $logContext = [];

        // Check database connection
        if (config('healthcheck.database', true) === true) {
            try {
                DB::connection()->getPdo();
                $dbStatus = 'OK';
                $logContext['is_database_connected'] = 'true';
            } catch (Exception $e) {
                $status = 500;
                $logContext['is_database_connected'] = 'false';
                $dbStatus = 'Unable to connect to the database: ' . $e->getMessage();
            }
            $response['db_status'] = $dbStatus;
        }

        // Check Redis connection
        if (config('healthcheck.redis', true) === true) {
            try {
                Redis::ping();
                $redisStatus = 'OK';
                $logContext['is_redis_connected'] = 'true';
            } catch (Exception $e) {
                $status = 500;
                $redisStatus = 'Unable to connect to Redis: ' . $e->getMessage();
                $logContext['is_redis_connected'] = 'false';
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
                    $logContext['is_horizon_running'] = 'true';
                }
            }
            if ($horizonStatus !== 'OK') {
                // Add logs for cloudwatch to monitor
                $logContext['is_horizon_running'] = 'false';
            }
            $response['horizon_status'] = $horizonStatus;
        }

        if (config('healthcheck.scheduler', false)) {
            $response['scheduler_status'] = 'OK';
            $response['scheduler_last_run'] = $this->getLastRun()?->format('Y-m-d H:i:s');
            $logContext['is_scheduler_running'] = 'true';
            $logContext['scheduler_last_run'] = $response['scheduler_last_run'];

            // Check if the last run was more than or equal to 2 minutes ago
            if (empty($this->getLastRun()) || $this->getLastRun()?->diffInMinutes(Carbon::now()) >= 2) {
                $response['scheduler_status'] = 'Scheduler is not running';
                $logContext['is_scheduler_running'] = 'false';
            }
        }

        if (config('healthcheck.logging', true)) {
            Log::info('Healthcheck status', $logContext);
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

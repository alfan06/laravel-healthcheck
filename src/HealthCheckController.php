<?php

namespace Alfan06\LaravelHealthcheck;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
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
            $response['horizon_status'] = $horizonStatus;
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
}

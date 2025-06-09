<?php

namespace Coreproc\LaravelHealthcheck\Listeners;

use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Log;

class LogJobFailedListener
{
    /**
     * Handle the event.
     */
    public function handle(JobFailed $event): void
    {
        $payload = $event->job->payload();

        $startTime = $payload['pushedAt'];
        $endTime = microtime(true);

        $processingTime = ($endTime - $startTime) / 1000;

        Log::info('Job Failed', [
            'event' => 'job.failed',
            'job' => $payload['displayName'] ?? 'Unknown Job',
            'connection' => $event->connectionName,
            'queue' => $event->job->getQueue(),
            'payload' => $event->job->payload(),
            'processing_time' => $processingTime,
        ]);
    }
}

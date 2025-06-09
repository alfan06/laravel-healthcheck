<?php

namespace Coreproc\LaravelHealthcheck\Listeners;

use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Log;

class LogJobProcessedListener
{
    /**
     * Handle the event.
     */
    public function handle(JobProcessed $event): void
    {
        $payload = $event->job->payload();

        $startTime = $payload['pushedAt'] ?? null;

        if(empty($startTime)) {
            Log::info('Job Processed', [
                'event' => 'job.processed',
                'job' => $payload['displayName'] ?? 'Unknown Job',
                'connection' => $event->connectionName,
                'queue' => $event->job->getQueue(),
                'payload' => $payload,
                'processing_time' => null,
            ]);

            return;
        }

        $endTime = microtime(true);

        $processingTime = ($endTime - $startTime) / 1000;

        Log::info('Job Processed', [
            'event' => 'job.processed',
            'job' => $payload['displayName'] ?? 'Unknown Job',
            'connection' => $event->connectionName,
            'queue' => $event->job->getQueue(),
            'payload' => $payload,
            'processing_time' => $processingTime,
        ]);
    }
}

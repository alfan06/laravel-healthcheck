<?php

namespace Alfan06\LaravelHealthcheck\Listeners;

use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Log;

class LogJobProcessingListener
{
    /**
     * Handle the event.
     */
    public function handle(JobProcessing $event): void
    {
        $payload = $event->job->payload();

        Log::info('Job Processing', [
            'event' => 'job.processing',
            'job' => $payload['displayName'] ?? 'Unknown Job',
            'connection' => $event->connectionName,
            'queue' => $event->job->getQueue(),
            'payload' => $payload,
        ]);
    }
}

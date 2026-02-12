<?php

namespace Rylxes\Observability\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Rylxes\Observability\Models\RequestTrace;

class StoreTraceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected array $traceData
    ) {
        $this->onQueue(config('observability.queue.queue_name', 'observability'));
        $this->onConnection(config('observability.queue.connection'));
    }

    public function handle(): void
    {
        RequestTrace::create($this->traceData);
    }
}

<?php

namespace Rylxes\Observability\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Rylxes\Observability\Models\QueryLog;

class StoreQueryLogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected array $queryData
    ) {
        $this->onQueue(config('observability.queue.queue_name', 'observability'));
        $this->onConnection(config('observability.queue.connection'));
    }

    public function handle(): void
    {
        QueryLog::create($this->queryData);
    }
}

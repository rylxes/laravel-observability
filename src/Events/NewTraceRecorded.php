<?php

namespace Rylxes\Observability\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewTraceRecorded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $traceId,
        public string $method,
        public string $url,
        public int $statusCode,
        public float $durationMs,
        public int $queryCount,
        public ?string $routeName = null,
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel(config('observability.broadcasting.channel', 'observability')),
        ];
    }

    public function broadcastAs(): string
    {
        return 'trace.recorded';
    }

    public function broadcastWith(): array
    {
        return [
            'trace_id' => $this->traceId,
            'method' => $this->method,
            'url' => $this->url,
            'status_code' => $this->statusCode,
            'duration_ms' => $this->durationMs,
            'query_count' => $this->queryCount,
            'route_name' => $this->routeName,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}

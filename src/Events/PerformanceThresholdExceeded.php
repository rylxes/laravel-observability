<?php

namespace Rylxes\Observability\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PerformanceThresholdExceeded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $type,
        public string $severity,
        public string $message,
        public array $routes,
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
        return 'performance.threshold_exceeded';
    }

    public function broadcastWith(): array
    {
        return [
            'type' => $this->type,
            'severity' => $this->severity,
            'message' => $this->message,
            'routes' => $this->routes,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}

<?php

namespace Rylxes\Observability\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AlertTriggered implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $alertId,
        public string $alertType,
        public string $severity,
        public string $title,
        public string $description,
        public ?string $source = null,
        public ?array $context = null,
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
        return 'alert.triggered';
    }

    public function broadcastWith(): array
    {
        return [
            'alert_id' => $this->alertId,
            'alert_type' => $this->alertType,
            'severity' => $this->severity,
            'title' => $this->title,
            'description' => $this->description,
            'source' => $this->source,
            'context' => $this->context,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}

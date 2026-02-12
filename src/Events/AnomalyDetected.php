<?php

namespace Rylxes\Observability\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AnomalyDetected implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $metricType,
        public string $metricName,
        public float $value,
        public float $baseline,
        public float $zScore,
        public float $deviationPercent,
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
        return 'anomaly.detected';
    }

    public function broadcastWith(): array
    {
        return [
            'metric_type' => $this->metricType,
            'metric_name' => $this->metricName,
            'value' => $this->value,
            'baseline' => $this->baseline,
            'z_score' => $this->zScore,
            'deviation_percent' => $this->deviationPercent,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}

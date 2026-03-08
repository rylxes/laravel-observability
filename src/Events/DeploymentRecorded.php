<?php

namespace Rylxes\Observability\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeploymentRecorded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $deploymentId,
        public ?string $version,
        public ?string $description,
        public ?string $commitHash,
        public ?string $branch,
        public ?string $deployer,
        public string $environment,
        public string $deployedAt,
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
        return 'deployment.recorded';
    }

    public function broadcastWith(): array
    {
        return [
            'deployment_id' => $this->deploymentId,
            'version' => $this->version,
            'description' => $this->description,
            'commit_hash' => $this->commitHash,
            'branch' => $this->branch,
            'deployer' => $this->deployer,
            'environment' => $this->environment,
            'deployed_at' => $this->deployedAt,
        ];
    }
}

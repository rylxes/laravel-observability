<?php

namespace Rylxes\Observability\Console\Commands;

use Illuminate\Console\Command;
use Rylxes\Observability\Models\Deployment;
use Rylxes\Observability\Events\DeploymentRecorded;

class DeploymentMarkerCommand extends Command
{
    protected $signature = 'observability:deploy
                            {--version= : Version tag (e.g. 1.3.0)}
                            {--description= : Description of the deployment}
                            {--commit= : Git commit hash (auto-detected if not provided)}
                            {--branch= : Git branch (auto-detected if not provided)}
                            {--deployer= : Name of the deployer}
                            {--environment= : Environment (auto-detected from APP_ENV if not provided)}';

    protected $description = 'Record a deployment marker for performance correlation';

    public function handle(): int
    {
        if (!config('observability.deployments.enabled', true)) {
            $this->warn('Deployment tracking is disabled.');
            return self::SUCCESS;
        }

        $commitHash = $this->option('commit') ?? $this->detectCommitHash();
        $branch = $this->option('branch') ?? $this->detectBranch();
        $environment = $this->option('environment') ?? config('app.env', 'production');

        $deployment = Deployment::create([
            'version' => $this->option('version'),
            'description' => $this->option('description'),
            'commit_hash' => $commitHash,
            'branch' => $branch,
            'deployer' => $this->option('deployer'),
            'environment' => $environment,
            'deployed_at' => now(),
        ]);

        $this->info("Deployment marker recorded (ID: {$deployment->id})");

        if ($this->option('version')) {
            $this->line("  Version: {$deployment->version}");
        }
        if ($commitHash) {
            $this->line("  Commit: {$commitHash}");
        }
        if ($branch) {
            $this->line("  Branch: {$branch}");
        }
        $this->line("  Environment: {$environment}");

        // Broadcast event
        if (config('observability.broadcasting.enabled')) {
            event(new DeploymentRecorded(
                deploymentId: $deployment->id,
                version: $deployment->version,
                description: $deployment->description,
                commitHash: $deployment->commit_hash,
                branch: $deployment->branch,
                deployer: $deployment->deployer,
                environment: $deployment->environment,
                deployedAt: $deployment->deployed_at->toIso8601String(),
            ));
        }

        return self::SUCCESS;
    }

    /**
     * Auto-detect the current git commit hash.
     *
     * Uses hardcoded git commands with no user input - safe from injection.
     */
    protected function detectCommitHash(): ?string
    {
        if (!config('observability.deployments.auto_detect_git', true)) {
            return null;
        }

        try {
            $process = proc_open(
                ['git', 'rev-parse', 'HEAD'],
                [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                $pipes
            );

            if (!is_resource($process)) {
                return null;
            }

            $hash = trim(stream_get_contents($pipes[1]));
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);

            return !empty($hash) ? $hash : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Auto-detect the current git branch.
     *
     * Uses hardcoded git commands with no user input - safe from injection.
     */
    protected function detectBranch(): ?string
    {
        if (!config('observability.deployments.auto_detect_git', true)) {
            return null;
        }

        try {
            $process = proc_open(
                ['git', 'rev-parse', '--abbrev-ref', 'HEAD'],
                [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                $pipes
            );

            if (!is_resource($process)) {
                return null;
            }

            $branch = trim(stream_get_contents($pipes[1]));
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);

            return !empty($branch) ? $branch : null;
        } catch (\Throwable) {
            return null;
        }
    }
}

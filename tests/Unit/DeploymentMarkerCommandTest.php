<?php

namespace Rylxes\Observability\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Rylxes\Observability\Console\Commands\DeploymentMarkerCommand;

class DeploymentMarkerCommandTest extends TestCase
{
    /** @test */
    public function it_extends_command(): void
    {
        $reflection = new \ReflectionClass(DeploymentMarkerCommand::class);

        $this->assertTrue($reflection->isSubclassOf(\Illuminate\Console\Command::class));
    }

    /** @test */
    public function it_has_correct_signature(): void
    {
        $command = new \ReflectionClass(DeploymentMarkerCommand::class);
        $prop = $command->getProperty('signature');
        $prop->setAccessible(true);

        $signature = $prop->getValue(new DeploymentMarkerCommand());

        $this->assertStringContainsString('observability:deploy', $signature);
        $this->assertStringContainsString('--version', $signature);
        $this->assertStringContainsString('--description', $signature);
        $this->assertStringContainsString('--commit', $signature);
        $this->assertStringContainsString('--branch', $signature);
        $this->assertStringContainsString('--deployer', $signature);
        $this->assertStringContainsString('--environment', $signature);
    }

    /** @test */
    public function it_has_handle_method(): void
    {
        $reflection = new \ReflectionClass(DeploymentMarkerCommand::class);

        $this->assertTrue($reflection->hasMethod('handle'));
        $this->assertTrue($reflection->getMethod('handle')->isPublic());
    }

    /** @test */
    public function it_has_git_detection_methods(): void
    {
        $reflection = new \ReflectionClass(DeploymentMarkerCommand::class);

        $this->assertTrue($reflection->hasMethod('detectCommitHash'));
        $this->assertTrue($reflection->hasMethod('detectBranch'));
    }
}

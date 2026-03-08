<?php

namespace Rylxes\Observability\Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use Rylxes\Observability\Models\Deployment;

class DeploymentTest extends TestCase
{
    /** @test */
    public function it_extends_eloquent_model(): void
    {
        $deployment = new Deployment();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Model::class, $deployment);
    }

    /** @test */
    public function it_uses_guarded_empty_array(): void
    {
        $deployment = new Deployment();

        $this->assertEquals([], $deployment->getGuarded());
    }

    /** @test */
    public function it_casts_metadata_as_array(): void
    {
        $deployment = new Deployment();
        $casts = $deployment->getCasts();

        $this->assertArrayHasKey('metadata', $casts);
        $this->assertEquals('array', $casts['metadata']);
    }

    /** @test */
    public function it_casts_deployed_at_as_datetime(): void
    {
        $deployment = new Deployment();
        $casts = $deployment->getCasts();

        $this->assertArrayHasKey('deployed_at', $casts);
        $this->assertEquals('datetime', $casts['deployed_at']);
    }

    /** @test */
    public function it_has_latest_static_method(): void
    {
        $reflection = new \ReflectionClass(Deployment::class);

        $this->assertTrue($reflection->hasMethod('latest'));
        $this->assertTrue($reflection->getMethod('latest')->isStatic());
    }

    /** @test */
    public function it_has_metrics_after_method(): void
    {
        $reflection = new \ReflectionClass(Deployment::class);

        $this->assertTrue($reflection->hasMethod('metricsAfter'));
        $this->assertTrue($reflection->getMethod('metricsAfter')->isPublic());
    }

    /** @test */
    public function it_has_metrics_before_method(): void
    {
        $reflection = new \ReflectionClass(Deployment::class);

        $this->assertTrue($reflection->hasMethod('metricsBefore'));
        $this->assertTrue($reflection->getMethod('metricsBefore')->isPublic());
    }

    /** @test */
    public function it_has_performance_impact_method(): void
    {
        $reflection = new \ReflectionClass(Deployment::class);

        $this->assertTrue($reflection->hasMethod('performanceImpact'));
        $this->assertTrue($reflection->getMethod('performanceImpact')->isPublic());
    }

    /** @test */
    public function it_calculates_percent_change_correctly(): void
    {
        $deployment = new Deployment();
        $reflection = new \ReflectionClass($deployment);
        $method = $reflection->getMethod('percentChange');
        $method->setAccessible(true);

        // No change
        $this->assertEquals(0.0, $method->invoke($deployment, 100, 100));

        // 50% increase
        $this->assertEquals(50.0, $method->invoke($deployment, 100, 150));

        // 50% decrease
        $this->assertEquals(-50.0, $method->invoke($deployment, 100, 50));

        // Zero before, non-zero after
        $this->assertEquals(100.0, $method->invoke($deployment, 0, 50));

        // Both zero
        $this->assertEquals(0.0, $method->invoke($deployment, 0, 0));
    }

    /** @test */
    public function it_determines_verdict_as_degraded(): void
    {
        $deployment = new Deployment();
        $reflection = new \ReflectionClass($deployment);
        $method = $reflection->getMethod('determineVerdict');
        $method->setAccessible(true);

        // Large response time increase = degraded
        $before = ['avg_response_time_ms' => 100, 'error_rate' => 1];
        $after = ['avg_response_time_ms' => 200, 'error_rate' => 1];

        $this->assertEquals('degraded', $method->invoke($deployment, $before, $after));
    }

    /** @test */
    public function it_determines_verdict_as_improved(): void
    {
        $deployment = new Deployment();
        $reflection = new \ReflectionClass($deployment);
        $method = $reflection->getMethod('determineVerdict');
        $method->setAccessible(true);

        // Significant response time decrease = improved
        $before = ['avg_response_time_ms' => 200, 'error_rate' => 1];
        $after = ['avg_response_time_ms' => 100, 'error_rate' => 1];

        $this->assertEquals('improved', $method->invoke($deployment, $before, $after));
    }

    /** @test */
    public function it_determines_verdict_as_neutral(): void
    {
        $deployment = new Deployment();
        $reflection = new \ReflectionClass($deployment);
        $method = $reflection->getMethod('determineVerdict');
        $method->setAccessible(true);

        // Minimal change = neutral
        $before = ['avg_response_time_ms' => 100, 'error_rate' => 1];
        $after = ['avg_response_time_ms' => 105, 'error_rate' => 1.2];

        $this->assertEquals('neutral', $method->invoke($deployment, $before, $after));
    }

    /** @test */
    public function it_has_environment_scope(): void
    {
        $reflection = new \ReflectionClass(Deployment::class);

        $this->assertTrue($reflection->hasMethod('scopeEnvironment'));
    }

    /** @test */
    public function it_has_recent_scope(): void
    {
        $reflection = new \ReflectionClass(Deployment::class);

        $this->assertTrue($reflection->hasMethod('scopeRecent'));
    }
}

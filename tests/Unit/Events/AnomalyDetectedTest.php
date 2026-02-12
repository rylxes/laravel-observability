<?php

namespace Rylxes\Observability\Tests\Unit\Events;

use PHPUnit\Framework\TestCase;
use Rylxes\Observability\Events\AnomalyDetected;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class AnomalyDetectedTest extends TestCase
{
    public function test_it_implements_should_broadcast(): void
    {
        $this->assertTrue(
            in_array(ShouldBroadcast::class, class_implements(AnomalyDetected::class))
        );
    }

    public function test_broadcast_as_returns_correct_event_name(): void
    {
        $event = new AnomalyDetected(
            metricType: 'response_time',
            metricName: 'avg_duration',
            value: 500.0,
            baseline: 200.0,
            zScore: 3.5,
            deviationPercent: 150.0
        );

        $this->assertEquals('anomaly.detected', $event->broadcastAs());
    }

    public function test_constructor_stores_properties_correctly(): void
    {
        $event = new AnomalyDetected(
            metricType: 'query_count',
            metricName: 'queries_per_request',
            value: 50.0,
            baseline: 10.0,
            zScore: 4.2,
            deviationPercent: 400.0
        );

        $this->assertEquals('query_count', $event->metricType);
        $this->assertEquals('queries_per_request', $event->metricName);
        $this->assertEquals(50.0, $event->value);
        $this->assertEquals(10.0, $event->baseline);
        $this->assertEquals(4.2, $event->zScore);
        $this->assertEquals(400.0, $event->deviationPercent);
    }

    public function test_it_uses_required_traits(): void
    {
        $traits = class_uses_recursive(AnomalyDetected::class);

        $this->assertContains(\Illuminate\Foundation\Events\Dispatchable::class, $traits);
        $this->assertContains(\Illuminate\Broadcasting\InteractsWithSockets::class, $traits);
        $this->assertContains(\Illuminate\Queue\SerializesModels::class, $traits);
    }

    public function test_class_has_broadcast_on_method(): void
    {
        $reflection = new \ReflectionClass(AnomalyDetected::class);

        $this->assertTrue($reflection->hasMethod('broadcastOn'));
        $this->assertTrue($reflection->getMethod('broadcastOn')->isPublic());
    }

    public function test_class_has_broadcast_with_method(): void
    {
        $reflection = new \ReflectionClass(AnomalyDetected::class);

        $this->assertTrue($reflection->hasMethod('broadcastWith'));
        $this->assertTrue($reflection->getMethod('broadcastWith')->isPublic());
    }

    public function test_constructor_requires_all_parameters(): void
    {
        $reflection = new \ReflectionClass(AnomalyDetected::class);
        $constructor = $reflection->getConstructor();
        $params = $constructor->getParameters();

        $this->assertCount(6, $params);

        $expectedParams = ['metricType', 'metricName', 'value', 'baseline', 'zScore', 'deviationPercent'];
        foreach ($params as $index => $param) {
            $this->assertEquals($expectedParams[$index], $param->getName());
            $this->assertFalse($param->isOptional(), "Parameter {$param->getName()} should be required");
        }
    }
}

<?php

namespace Rylxes\Observability\Tests\Unit\Events;

use PHPUnit\Framework\TestCase;
use Rylxes\Observability\Events\PerformanceThresholdExceeded;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class PerformanceThresholdExceededTest extends TestCase
{
    public function test_it_implements_should_broadcast(): void
    {
        $this->assertTrue(
            in_array(ShouldBroadcast::class, class_implements(PerformanceThresholdExceeded::class))
        );
    }

    public function test_broadcast_as_returns_correct_event_name(): void
    {
        $event = new PerformanceThresholdExceeded(
            type: 'slow_response',
            severity: 'warning',
            message: 'Response time exceeded threshold',
            routes: ['/api/users' => ['avg' => 2500, 'threshold' => 1000]]
        );

        $this->assertEquals('performance.threshold_exceeded', $event->broadcastAs());
    }

    public function test_constructor_stores_properties_correctly(): void
    {
        $routes = [
            '/api/users' => ['avg' => 2500, 'threshold' => 1000],
            '/api/orders' => ['avg' => 3000, 'threshold' => 1000],
        ];

        $event = new PerformanceThresholdExceeded(
            type: 'high_query_count',
            severity: 'critical',
            message: 'Query count exceeded safe limit',
            routes: $routes
        );

        $this->assertEquals('high_query_count', $event->type);
        $this->assertEquals('critical', $event->severity);
        $this->assertEquals('Query count exceeded safe limit', $event->message);
        $this->assertEquals($routes, $event->routes);
    }

    public function test_it_uses_required_traits(): void
    {
        $traits = class_uses_recursive(PerformanceThresholdExceeded::class);

        $this->assertContains(\Illuminate\Foundation\Events\Dispatchable::class, $traits);
        $this->assertContains(\Illuminate\Broadcasting\InteractsWithSockets::class, $traits);
        $this->assertContains(\Illuminate\Queue\SerializesModels::class, $traits);
    }

    public function test_class_has_broadcast_on_method(): void
    {
        $reflection = new \ReflectionClass(PerformanceThresholdExceeded::class);

        $this->assertTrue($reflection->hasMethod('broadcastOn'));
        $this->assertTrue($reflection->getMethod('broadcastOn')->isPublic());
    }

    public function test_class_has_broadcast_with_method(): void
    {
        $reflection = new \ReflectionClass(PerformanceThresholdExceeded::class);

        $this->assertTrue($reflection->hasMethod('broadcastWith'));
        $this->assertTrue($reflection->getMethod('broadcastWith')->isPublic());
    }

    public function test_constructor_requires_all_parameters(): void
    {
        $reflection = new \ReflectionClass(PerformanceThresholdExceeded::class);
        $constructor = $reflection->getConstructor();
        $params = $constructor->getParameters();

        $this->assertCount(4, $params);

        $expectedParams = ['type', 'severity', 'message', 'routes'];
        foreach ($params as $index => $param) {
            $this->assertEquals($expectedParams[$index], $param->getName());
            $this->assertFalse($param->isOptional(), "Parameter {$param->getName()} should be required");
        }
    }

    public function test_routes_parameter_accepts_array(): void
    {
        $reflection = new \ReflectionClass(PerformanceThresholdExceeded::class);
        $constructor = $reflection->getConstructor();
        $routesParam = $constructor->getParameters()[3];

        $this->assertNotNull($routesParam->getType());
        $this->assertEquals('array', $routesParam->getType()->getName());
    }
}

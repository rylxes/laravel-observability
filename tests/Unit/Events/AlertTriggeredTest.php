<?php

namespace Rylxes\Observability\Tests\Unit\Events;

use PHPUnit\Framework\TestCase;
use Rylxes\Observability\Events\AlertTriggered;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class AlertTriggeredTest extends TestCase
{
    public function test_it_implements_should_broadcast(): void
    {
        $this->assertTrue(
            in_array(ShouldBroadcast::class, class_implements(AlertTriggered::class))
        );
    }

    public function test_broadcast_as_returns_correct_event_name(): void
    {
        $event = new AlertTriggered(
            alertId: 1,
            alertType: 'slow_response',
            severity: 'warning',
            title: 'Slow Response Detected',
            description: 'Response time exceeded threshold'
        );

        $this->assertEquals('alert.triggered', $event->broadcastAs());
    }

    public function test_constructor_stores_properties_correctly(): void
    {
        $context = ['threshold' => 1000, 'actual' => 2500];

        $event = new AlertTriggered(
            alertId: 42,
            alertType: 'high_error_rate',
            severity: 'critical',
            title: 'High Error Rate',
            description: 'Error rate exceeded 5%',
            source: 'api.users.index',
            context: $context
        );

        $this->assertEquals(42, $event->alertId);
        $this->assertEquals('high_error_rate', $event->alertType);
        $this->assertEquals('critical', $event->severity);
        $this->assertEquals('High Error Rate', $event->title);
        $this->assertEquals('Error rate exceeded 5%', $event->description);
        $this->assertEquals('api.users.index', $event->source);
        $this->assertEquals($context, $event->context);
    }

    public function test_constructor_optional_params_default_to_null(): void
    {
        $event = new AlertTriggered(
            alertId: 1,
            alertType: 'test',
            severity: 'info',
            title: 'Test Alert',
            description: 'Test description'
        );

        $this->assertNull($event->source);
        $this->assertNull($event->context);
    }

    public function test_it_uses_required_traits(): void
    {
        $traits = class_uses_recursive(AlertTriggered::class);

        $this->assertContains(\Illuminate\Foundation\Events\Dispatchable::class, $traits);
        $this->assertContains(\Illuminate\Broadcasting\InteractsWithSockets::class, $traits);
        $this->assertContains(\Illuminate\Queue\SerializesModels::class, $traits);
    }

    public function test_class_has_broadcast_on_method(): void
    {
        $reflection = new \ReflectionClass(AlertTriggered::class);

        $this->assertTrue($reflection->hasMethod('broadcastOn'));
        $this->assertTrue($reflection->getMethod('broadcastOn')->isPublic());
    }

    public function test_class_has_broadcast_with_method(): void
    {
        $reflection = new \ReflectionClass(AlertTriggered::class);

        $this->assertTrue($reflection->hasMethod('broadcastWith'));
        $this->assertTrue($reflection->getMethod('broadcastWith')->isPublic());
    }
}

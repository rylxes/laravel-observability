<?php

namespace Rylxes\Observability\Tests\Unit\Events;

use PHPUnit\Framework\TestCase;
use Rylxes\Observability\Events\NewTraceRecorded;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class NewTraceRecordedTest extends TestCase
{
    public function test_it_implements_should_broadcast(): void
    {
        $this->assertTrue(
            in_array(ShouldBroadcast::class, class_implements(NewTraceRecorded::class))
        );
    }

    public function test_broadcast_as_returns_correct_event_name(): void
    {
        $event = new NewTraceRecorded(
            traceId: 'trace-123',
            method: 'GET',
            url: '/api/test',
            statusCode: 200,
            durationMs: 150.5,
            queryCount: 3,
            routeName: 'api.test'
        );

        $this->assertEquals('trace.recorded', $event->broadcastAs());
    }

    public function test_constructor_stores_properties_correctly(): void
    {
        $event = new NewTraceRecorded(
            traceId: 'trace-456',
            method: 'POST',
            url: '/api/users',
            statusCode: 201,
            durationMs: 250.0,
            queryCount: 5,
            routeName: 'api.users.store'
        );

        $this->assertEquals('trace-456', $event->traceId);
        $this->assertEquals('POST', $event->method);
        $this->assertEquals('/api/users', $event->url);
        $this->assertEquals(201, $event->statusCode);
        $this->assertEquals(250.0, $event->durationMs);
        $this->assertEquals(5, $event->queryCount);
        $this->assertEquals('api.users.store', $event->routeName);
    }

    public function test_constructor_route_name_defaults_to_null(): void
    {
        $event = new NewTraceRecorded(
            traceId: 'trace-789',
            method: 'GET',
            url: '/test',
            statusCode: 200,
            durationMs: 100.0,
            queryCount: 1
        );

        $this->assertNull($event->routeName);
    }

    public function test_it_uses_required_traits(): void
    {
        $traits = class_uses_recursive(NewTraceRecorded::class);

        $this->assertContains(\Illuminate\Foundation\Events\Dispatchable::class, $traits);
        $this->assertContains(\Illuminate\Broadcasting\InteractsWithSockets::class, $traits);
        $this->assertContains(\Illuminate\Queue\SerializesModels::class, $traits);
    }

    public function test_class_has_broadcast_on_method(): void
    {
        $reflection = new \ReflectionClass(NewTraceRecorded::class);

        $this->assertTrue($reflection->hasMethod('broadcastOn'));
        $this->assertTrue($reflection->getMethod('broadcastOn')->isPublic());
    }

    public function test_class_has_broadcast_with_method(): void
    {
        $reflection = new \ReflectionClass(NewTraceRecorded::class);

        $this->assertTrue($reflection->hasMethod('broadcastWith'));
        $this->assertTrue($reflection->getMethod('broadcastWith')->isPublic());
    }
}

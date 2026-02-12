<?php

namespace Rylxes\Observability\Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Rylxes\Observability\Models\Alert;

class AlertTest extends TestCase
{
    protected Alert $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new Alert();
    }

    /** @test */
    public function it_casts_context_as_array(): void
    {
        $reflection = new ReflectionClass($this->model);
        $property = $reflection->getProperty('casts');
        $property->setAccessible(true);

        $casts = $property->getValue($this->model);

        $this->assertArrayHasKey('context', $casts);
        $this->assertEquals('array', $casts['context']);
    }

    /** @test */
    public function it_casts_notified_as_boolean(): void
    {
        $reflection = new ReflectionClass($this->model);
        $property = $reflection->getProperty('casts');
        $property->setAccessible(true);

        $casts = $property->getValue($this->model);

        $this->assertArrayHasKey('notified', $casts);
        $this->assertEquals('boolean', $casts['notified']);
    }

    /** @test */
    public function it_casts_resolved_as_boolean(): void
    {
        $reflection = new ReflectionClass($this->model);
        $property = $reflection->getProperty('casts');
        $property->setAccessible(true);

        $casts = $property->getValue($this->model);

        $this->assertArrayHasKey('resolved', $casts);
        $this->assertEquals('boolean', $casts['resolved']);
    }

    /** @test */
    public function it_casts_notified_at_as_datetime(): void
    {
        $reflection = new ReflectionClass($this->model);
        $property = $reflection->getProperty('casts');
        $property->setAccessible(true);

        $casts = $property->getValue($this->model);

        $this->assertArrayHasKey('notified_at', $casts);
        $this->assertEquals('datetime', $casts['notified_at']);
    }

    /** @test */
    public function it_casts_resolved_at_as_datetime(): void
    {
        $reflection = new ReflectionClass($this->model);
        $property = $reflection->getProperty('casts');
        $property->setAccessible(true);

        $casts = $property->getValue($this->model);

        $this->assertArrayHasKey('resolved_at', $casts);
        $this->assertEquals('datetime', $casts['resolved_at']);
    }

    /** @test */
    public function it_has_all_expected_casts(): void
    {
        $reflection = new ReflectionClass($this->model);
        $property = $reflection->getProperty('casts');
        $property->setAccessible(true);

        $casts = $property->getValue($this->model);

        $expectedCasts = [
            'context' => 'array',
            'notified' => 'boolean',
            'resolved' => 'boolean',
            'notified_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];

        foreach ($expectedCasts as $key => $type) {
            $this->assertArrayHasKey($key, $casts, "Cast key '{$key}' should be defined");
            $this->assertEquals($type, $casts[$key], "Cast key '{$key}' should be '{$type}'");
        }
    }

    /** @test */
    public function it_extends_eloquent_model(): void
    {
        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Model::class,
            $this->model
        );
    }

    /** @test */
    public function it_has_mark_resolved_method(): void
    {
        $reflection = new ReflectionClass($this->model);

        $this->assertTrue(
            $reflection->hasMethod('markResolved'),
            'Alert should have a markResolved() method'
        );

        $method = $reflection->getMethod('markResolved');
        $this->assertTrue($method->isPublic(), 'markResolved() should be public');
    }

    /** @test */
    public function it_has_mark_notified_method(): void
    {
        $reflection = new ReflectionClass($this->model);

        $this->assertTrue(
            $reflection->hasMethod('markNotified'),
            'Alert should have a markNotified() method'
        );

        $method = $reflection->getMethod('markNotified');
        $this->assertTrue($method->isPublic(), 'markNotified() should be public');
    }

    /** @test */
    public function it_uses_guarded_property(): void
    {
        $reflection = new ReflectionClass($this->model);
        $property = $reflection->getProperty('guarded');
        $property->setAccessible(true);

        $guarded = $property->getValue($this->model);

        $this->assertIsArray($guarded);
        $this->assertEmpty($guarded);
    }
}

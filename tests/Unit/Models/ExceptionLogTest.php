<?php

namespace Rylxes\Observability\Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Rylxes\Observability\Models\ExceptionLog;

class ExceptionLogTest extends TestCase
{
    protected ExceptionLog $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new ExceptionLog();
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
    public function it_uses_guarded_property(): void
    {
        $reflection = new ReflectionClass($this->model);
        $property = $reflection->getProperty('guarded');
        $property->setAccessible(true);

        $guarded = $property->getValue($this->model);

        $this->assertIsArray($guarded);
        $this->assertEmpty($guarded);
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
    public function it_casts_metadata_as_array(): void
    {
        $reflection = new ReflectionClass($this->model);
        $property = $reflection->getProperty('casts');
        $property->setAccessible(true);

        $casts = $property->getValue($this->model);

        $this->assertArrayHasKey('metadata', $casts);
        $this->assertEquals('array', $casts['metadata']);
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
    public function it_casts_occurrence_count_as_integer(): void
    {
        $reflection = new ReflectionClass($this->model);
        $property = $reflection->getProperty('casts');
        $property->setAccessible(true);

        $casts = $property->getValue($this->model);

        $this->assertArrayHasKey('occurrence_count', $casts);
        $this->assertEquals('integer', $casts['occurrence_count']);
    }

    /** @test */
    public function it_casts_datetime_fields(): void
    {
        $reflection = new ReflectionClass($this->model);
        $property = $reflection->getProperty('casts');
        $property->setAccessible(true);

        $casts = $property->getValue($this->model);

        $this->assertArrayHasKey('first_seen_at', $casts);
        $this->assertEquals('datetime', $casts['first_seen_at']);

        $this->assertArrayHasKey('last_seen_at', $casts);
        $this->assertEquals('datetime', $casts['last_seen_at']);

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
            'metadata' => 'array',
            'resolved' => 'boolean',
            'occurrence_count' => 'integer',
            'line' => 'integer',
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];

        foreach ($expectedCasts as $key => $type) {
            $this->assertArrayHasKey($key, $casts, "Cast key '{$key}' should be defined");
            $this->assertEquals($type, $casts[$key], "Cast key '{$key}' should be '{$type}'");
        }
    }

    /** @test */
    public function it_generates_group_hash(): void
    {
        $hash = ExceptionLog::generateGroupHash(
            'App\\Exceptions\\TestException',
            '/app/Http/Controllers/TestController.php',
            42
        );

        $this->assertIsString($hash);
        $this->assertEquals(32, strlen($hash));

        // Same inputs should produce same hash
        $hash2 = ExceptionLog::generateGroupHash(
            'App\\Exceptions\\TestException',
            '/app/Http/Controllers/TestController.php',
            42
        );
        $this->assertEquals($hash, $hash2);

        // Different inputs should produce different hash
        $hash3 = ExceptionLog::generateGroupHash(
            'App\\Exceptions\\OtherException',
            '/app/Http/Controllers/TestController.php',
            42
        );
        $this->assertNotEquals($hash, $hash3);
    }

    /** @test */
    public function it_generates_different_hash_for_different_line(): void
    {
        $hash1 = ExceptionLog::generateGroupHash('Exception', '/app/Test.php', 10);
        $hash2 = ExceptionLog::generateGroupHash('Exception', '/app/Test.php', 20);

        $this->assertNotEquals($hash1, $hash2);
    }

    /** @test */
    public function it_has_mark_resolved_method(): void
    {
        $reflection = new ReflectionClass($this->model);

        $this->assertTrue(
            $reflection->hasMethod('markResolved'),
            'ExceptionLog should have a markResolved() method'
        );

        $method = $reflection->getMethod('markResolved');
        $this->assertTrue($method->isPublic(), 'markResolved() should be public');
    }

    /** @test */
    public function it_has_trace_relationship(): void
    {
        $reflection = new ReflectionClass($this->model);

        $this->assertTrue(
            $reflection->hasMethod('trace'),
            'ExceptionLog should have a trace() relationship'
        );
    }

    /** @test */
    public function it_has_expected_scopes(): void
    {
        $reflection = new ReflectionClass($this->model);

        $expectedScopes = ['scopeUnresolved', 'scopeByClass', 'scopeGrouped', 'scopeRecent', 'scopeSeverity'];

        foreach ($expectedScopes as $scope) {
            $this->assertTrue(
                $reflection->hasMethod($scope),
                "ExceptionLog should have {$scope} scope"
            );
        }
    }
}

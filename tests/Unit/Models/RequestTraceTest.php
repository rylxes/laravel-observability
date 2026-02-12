<?php

namespace Rylxes\Observability\Tests\Unit\Models;

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Rylxes\Observability\Models\RequestTrace;

class RequestTraceTest extends TestCase
{
    protected RequestTrace $model;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up a minimal container so config() calls in getTable() work
        $app = new Container();
        $app->instance('config', new Repository([
            'observability' => [
                'table_prefix' => 'observability_',
            ],
        ]));
        Container::setInstance($app);

        $this->model = new RequestTrace();
    }

    protected function tearDown(): void
    {
        Container::setInstance(null);
        parent::tearDown();
    }

    /** @test */
    public function it_returns_correct_table_name_with_prefix(): void
    {
        $table = $this->model->getTable();

        $this->assertIsString($table);
        $this->assertEquals('observability_traces', $table);
    }

    /** @test */
    public function it_has_correct_casts_defined(): void
    {
        $reflection = new ReflectionClass($this->model);
        $property = $reflection->getProperty('casts');
        $property->setAccessible(true);

        $casts = $property->getValue($this->model);

        $this->assertArrayHasKey('metadata', $casts);
        $this->assertEquals('array', $casts['metadata']);

        $this->assertArrayHasKey('headers', $casts);
        $this->assertEquals('array', $casts['headers']);

        $this->assertArrayHasKey('request_payload', $casts);
        $this->assertEquals('array', $casts['request_payload']);

        $this->assertArrayHasKey('duration_ms', $casts);
        $this->assertEquals('integer', $casts['duration_ms']);

        $this->assertArrayHasKey('memory_usage', $casts);
        $this->assertEquals('integer', $casts['memory_usage']);

        $this->assertArrayHasKey('query_count', $casts);
        $this->assertEquals('integer', $casts['query_count']);

        $this->assertArrayHasKey('query_time_ms', $casts);
        $this->assertEquals('integer', $casts['query_time_ms']);

        $this->assertArrayHasKey('status_code', $casts);
        $this->assertEquals('integer', $casts['status_code']);
    }

    /** @test */
    public function it_has_queries_relationship_defined(): void
    {
        $reflection = new ReflectionClass($this->model);

        $this->assertTrue(
            $reflection->hasMethod('queries'),
            'RequestTrace should have a queries() relationship method'
        );

        $method = $reflection->getMethod('queries');
        $this->assertTrue($method->isPublic(), 'queries() should be public');

        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType, 'queries() should have a return type');
        $this->assertEquals(
            'Illuminate\Database\Eloquent\Relations\HasMany',
            $returnType->getName()
        );
    }

    /** @test */
    public function it_has_slow_queries_relationship_defined(): void
    {
        $reflection = new ReflectionClass($this->model);

        $this->assertTrue(
            $reflection->hasMethod('slowQueries'),
            'RequestTrace should have a slowQueries() relationship method'
        );

        $method = $reflection->getMethod('slowQueries');
        $this->assertTrue($method->isPublic(), 'slowQueries() should be public');

        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType, 'slowQueries() should have a return type');
        $this->assertEquals(
            'Illuminate\Database\Eloquent\Relations\HasMany',
            $returnType->getName()
        );
    }

    /** @test */
    public function it_has_children_relationship_defined(): void
    {
        $reflection = new ReflectionClass($this->model);

        $this->assertTrue(
            $reflection->hasMethod('children'),
            'RequestTrace should have a children() relationship method'
        );

        $method = $reflection->getMethod('children');
        $this->assertTrue($method->isPublic(), 'children() should be public');

        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType, 'children() should have a return type');
        $this->assertEquals(
            'Illuminate\Database\Eloquent\Relations\HasMany',
            $returnType->getName()
        );
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
}

<?php

namespace Rylxes\Observability\Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Rylxes\Observability\Models\QueryLog;

class QueryLogTest extends TestCase
{
    protected QueryLog $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new QueryLog();
    }

    // -------------------------------------------------------
    // extractQueryType() tests
    // -------------------------------------------------------

    /** @test */
    public function it_extracts_select_query_type(): void
    {
        $this->assertEquals('SELECT', QueryLog::extractQueryType('SELECT * FROM users WHERE id = 1'));
    }

    /** @test */
    public function it_extracts_select_query_type_case_insensitive(): void
    {
        $this->assertEquals('SELECT', QueryLog::extractQueryType('select id, name from users'));
    }

    /** @test */
    public function it_extracts_insert_query_type(): void
    {
        $this->assertEquals('INSERT', QueryLog::extractQueryType('INSERT INTO users (name) VALUES ("John")'));
    }

    /** @test */
    public function it_extracts_update_query_type(): void
    {
        $this->assertEquals('UPDATE', QueryLog::extractQueryType('UPDATE users SET name = "Jane" WHERE id = 1'));
    }

    /** @test */
    public function it_extracts_delete_query_type(): void
    {
        $this->assertEquals('DELETE', QueryLog::extractQueryType('DELETE FROM users WHERE id = 1'));
    }

    /** @test */
    public function it_returns_other_for_unknown_query_type(): void
    {
        $this->assertEquals('OTHER', QueryLog::extractQueryType('DESCRIBE users'));
    }

    /** @test */
    public function it_returns_other_for_empty_string(): void
    {
        $this->assertEquals('OTHER', QueryLog::extractQueryType(''));
    }

    /** @test */
    public function it_handles_query_with_leading_whitespace(): void
    {
        $this->assertEquals('SELECT', QueryLog::extractQueryType('   SELECT * FROM users'));
    }

    // -------------------------------------------------------
    // extractTableName() tests
    // -------------------------------------------------------

    /** @test */
    public function it_extracts_table_name_from_select_query(): void
    {
        $this->assertEquals('users', QueryLog::extractTableName('SELECT * FROM users WHERE id = 1'));
    }

    /** @test */
    public function it_extracts_table_name_from_select_with_backticks(): void
    {
        $this->assertEquals('users', QueryLog::extractTableName('SELECT * FROM `users` WHERE id = 1'));
    }

    /** @test */
    public function it_extracts_table_name_from_insert_query(): void
    {
        $this->assertEquals('users', QueryLog::extractTableName('INSERT INTO users (name) VALUES ("John")'));
    }

    /** @test */
    public function it_extracts_table_name_from_update_query(): void
    {
        $this->assertEquals('users', QueryLog::extractTableName('UPDATE users SET name = "Jane"'));
    }

    /** @test */
    public function it_returns_null_for_unparseable_sql(): void
    {
        $this->assertNull(QueryLog::extractTableName('SHOW DATABASES'));
    }

    /** @test */
    public function it_returns_null_for_empty_sql(): void
    {
        $this->assertNull(QueryLog::extractTableName(''));
    }

    // -------------------------------------------------------
    // Casts tests
    // -------------------------------------------------------

    /** @test */
    public function it_has_correct_casts_defined(): void
    {
        $reflection = new ReflectionClass($this->model);
        $property = $reflection->getProperty('casts');
        $property->setAccessible(true);

        $casts = $property->getValue($this->model);

        $this->assertArrayHasKey('bindings', $casts);
        $this->assertEquals('array', $casts['bindings']);

        $this->assertArrayHasKey('duration_ms', $casts);
        $this->assertEquals('integer', $casts['duration_ms']);

        $this->assertArrayHasKey('is_slow', $casts);
        $this->assertEquals('boolean', $casts['is_slow']);

        $this->assertArrayHasKey('is_duplicate', $casts);
        $this->assertEquals('boolean', $casts['is_duplicate']);
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
    public function it_has_trace_relationship(): void
    {
        $reflection = new ReflectionClass($this->model);

        $this->assertTrue(
            $reflection->hasMethod('trace'),
            'QueryLog should have a trace() relationship method'
        );

        $method = $reflection->getMethod('trace');
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals(
            'Illuminate\Database\Eloquent\Relations\BelongsTo',
            $returnType->getName()
        );
    }
}

<?php

namespace Rylxes\Observability\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Illuminate\Container\Container;
use Illuminate\Config\Repository;
use Illuminate\Support\Facades\Facade;
use Mockery;
use Rylxes\Observability\Analyzers\ExplainAnalyzer;

class ExplainAnalyzerTest extends TestCase
{
    protected ExplainAnalyzer $analyzer;

    protected function setUp(): void
    {
        parent::setUp();

        $app = new Container();
        $app->singleton('config', fn () => new Repository([
            'observability' => [
                'queries' => [
                    'capture_explain' => true,
                    'explain_only_select' => true,
                    'explain_timeout' => 5,
                ],
            ],
            'database' => [
                'default' => 'mysql',
                'connections' => [
                    'mysql' => ['driver' => 'mysql'],
                    'pgsql' => ['driver' => 'pgsql'],
                    'sqlite' => ['driver' => 'sqlite'],
                ],
            ],
        ]));

        Container::setInstance($app);
        Facade::setFacadeApplication($app);

        $this->analyzer = new ExplainAnalyzer();
    }

    protected function tearDown(): void
    {
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
        Container::setInstance(null);
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_has_explain_method(): void
    {
        $reflection = new \ReflectionClass($this->analyzer);

        $this->assertTrue($reflection->hasMethod('explain'));

        $method = $reflection->getMethod('explain');
        $this->assertTrue($method->isPublic());
    }

    /** @test */
    public function it_returns_null_when_disabled(): void
    {
        $app = Container::getInstance();
        $app->singleton('config', fn () => new Repository([
            'observability' => [
                'queries' => [
                    'capture_explain' => false,
                ],
            ],
            'database' => [
                'default' => 'mysql',
                'connections' => ['mysql' => ['driver' => 'mysql']],
            ],
        ]));

        $result = $this->analyzer->explain('SELECT * FROM users');

        $this->assertNull($result);
    }

    /** @test */
    public function it_returns_null_for_non_select_queries_when_explain_only_select(): void
    {
        $queries = [
            'INSERT INTO users (name) VALUES (?)',
            'UPDATE users SET name = ? WHERE id = ?',
            'DELETE FROM users WHERE id = ?',
        ];

        foreach ($queries as $sql) {
            $result = $this->analyzer->explain($sql);
            $this->assertNull($result, "Expected null for: {$sql}");
        }
    }

    /** @test */
    public function it_detects_side_effects_in_sql(): void
    {
        $reflection = new \ReflectionClass($this->analyzer);
        $method = $reflection->getMethod('containsSideEffects');
        $method->setAccessible(true);

        $sideEffectQueries = [
            'INSERT INTO users (name) VALUES (?)',
            'UPDATE users SET name = ?',
            'DELETE FROM users',
            'DROP TABLE users',
            'ALTER TABLE users ADD COLUMN age INT',
            'CREATE TABLE test (id INT)',
            'TRUNCATE TABLE users',
        ];

        foreach ($sideEffectQueries as $sql) {
            $this->assertTrue(
                $method->invoke($this->analyzer, $sql),
                "Expected side effects for: {$sql}"
            );
        }

        $safeSql = 'SELECT * FROM users WHERE id = ?';
        $this->assertFalse($method->invoke($this->analyzer, $safeSql));
    }

    /** @test */
    public function it_maps_mysql_scan_types_correctly(): void
    {
        $reflection = new \ReflectionClass($this->analyzer);
        $method = $reflection->getMethod('mapMysqlScanType');
        $method->setAccessible(true);

        $expected = [
            'ALL' => 'full_scan',
            'all' => 'full_scan',
            'index' => 'index_scan',
            'range' => 'range_scan',
            'ref' => 'ref',
            'eq_ref' => 'ref',
            'const' => 'const',
            'system' => 'const',
            'fulltext' => 'fulltext',
            'unknown_type' => 'unknown',
        ];

        foreach ($expected as $input => $expectedType) {
            $this->assertEquals(
                $expectedType,
                $method->invoke($this->analyzer, $input),
                "Expected '{$expectedType}' for MySQL type '{$input}'"
            );
        }
    }

    /** @test */
    public function it_maps_postgres_scan_types_correctly(): void
    {
        $reflection = new \ReflectionClass($this->analyzer);
        $method = $reflection->getMethod('mapPostgresScanType');
        $method->setAccessible(true);

        $expected = [
            'Seq Scan' => 'full_scan',
            'Index Scan' => 'index_scan',
            'Index Only Scan' => 'index_scan',
            'Bitmap Index Scan' => 'index_scan',
            'Bitmap Heap Scan' => 'range_scan',
            'Unknown Type' => 'unknown',
        ];

        foreach ($expected as $input => $expectedType) {
            $this->assertEquals(
                $expectedType,
                $method->invoke($this->analyzer, $input),
                "Expected '{$expectedType}' for PostgreSQL type '{$input}'"
            );
        }
    }

    /** @test */
    public function it_gets_correct_driver_for_connection(): void
    {
        $reflection = new \ReflectionClass($this->analyzer);
        $method = $reflection->getMethod('getDriver');
        $method->setAccessible(true);

        $this->assertEquals('mysql', $method->invoke($this->analyzer, 'mysql'));
        $this->assertEquals('pgsql', $method->invoke($this->analyzer, 'pgsql'));
        $this->assertEquals('sqlite', $method->invoke($this->analyzer, 'sqlite'));
        // null defaults to the default connection driver
        $this->assertEquals('mysql', $method->invoke($this->analyzer, null));
    }

    /** @test */
    public function it_normalizes_mysql_explain_output(): void
    {
        $reflection = new \ReflectionClass($this->analyzer);
        $method = $reflection->getMethod('normalizeMysql');
        $method->setAccessible(true);

        $rows = [
            (object) [
                'type' => 'ALL',
                'rows' => 250000,
                'key' => null,
                'possible_keys' => 'idx_user_id,idx_created_at',
                'table' => 'orders',
                'Extra' => 'Using filesort',
            ],
        ];

        $result = $method->invoke($this->analyzer, $rows);

        $this->assertEquals('full_scan', $result['scan_type']);
        $this->assertEquals(250000, $result['rows_examined']);
        $this->assertNull($result['index_used']);
        $this->assertContains('idx_user_id', $result['possible_indexes']);
        $this->assertContains('idx_created_at', $result['possible_indexes']);
        $this->assertNotEmpty($result['warnings']);
        $this->assertNotEmpty($result['suggestions']);
    }

    /** @test */
    public function it_normalizes_mysql_explain_with_full_table_scan(): void
    {
        $reflection = new \ReflectionClass($this->analyzer);
        $method = $reflection->getMethod('normalizeMysql');
        $method->setAccessible(true);

        $rows = [
            (object) [
                'type' => 'ALL',
                'rows' => 1000,
                'key' => null,
                'possible_keys' => null,
                'table' => 'users',
                'Extra' => '',
            ],
        ];

        $result = $method->invoke($this->analyzer, $rows);

        $this->assertEquals('full_scan', $result['scan_type']);
        $this->assertNotEmpty($result['warnings']);
        // Should warn about full table scan
        $this->assertStringContainsString('Full table scan', $result['warnings'][0]);
    }

    /** @test */
    public function it_normalizes_mysql_explain_with_filesort_warning(): void
    {
        $reflection = new \ReflectionClass($this->analyzer);
        $method = $reflection->getMethod('normalizeMysql');
        $method->setAccessible(true);

        $rows = [
            (object) [
                'type' => 'ref',
                'rows' => 100,
                'key' => 'idx_user_id',
                'possible_keys' => 'idx_user_id',
                'table' => 'orders',
                'Extra' => 'Using filesort',
            ],
        ];

        $result = $method->invoke($this->analyzer, $rows);

        $this->assertEquals('ref', $result['scan_type']);
        $this->assertEquals('idx_user_id', $result['index_used']);
        $warnings = implode(' ', $result['warnings']);
        $this->assertStringContainsString('filesort', $warnings);
    }

    /** @test */
    public function it_normalizes_mysql_explain_with_temporary_table_warning(): void
    {
        $reflection = new \ReflectionClass($this->analyzer);
        $method = $reflection->getMethod('normalizeMysql');
        $method->setAccessible(true);

        $rows = [
            (object) [
                'type' => 'ALL',
                'rows' => 500,
                'key' => null,
                'possible_keys' => null,
                'table' => 'logs',
                'Extra' => 'Using temporary',
            ],
        ];

        $result = $method->invoke($this->analyzer, $rows);

        $warnings = implode(' ', $result['warnings']);
        $this->assertStringContainsString('Temporary table', $warnings);
    }

    /** @test */
    public function it_normalizes_postgres_explain_output(): void
    {
        $reflection = new \ReflectionClass($this->analyzer);
        $method = $reflection->getMethod('normalizePostgres');
        $method->setAccessible(true);

        $plan = [
            [
                'Plan' => [
                    'Node Type' => 'Seq Scan',
                    'Plan Rows' => 50000,
                    'Relation Name' => 'users',
                ],
            ],
        ];

        $result = $method->invoke($this->analyzer, $plan);

        $this->assertEquals('full_scan', $result['scan_type']);
        $this->assertEquals(50000, $result['rows_examined']);
        $this->assertNull($result['index_used']);
        $this->assertNotEmpty($result['warnings']);
        $this->assertStringContainsString('Sequential scan', $result['warnings'][0]);
    }

    /** @test */
    public function it_normalizes_postgres_explain_with_index(): void
    {
        $reflection = new \ReflectionClass($this->analyzer);
        $method = $reflection->getMethod('normalizePostgres');
        $method->setAccessible(true);

        $plan = [
            [
                'Plan' => [
                    'Node Type' => 'Index Scan',
                    'Plan Rows' => 1,
                    'Index Name' => 'users_pkey',
                    'Relation Name' => 'users',
                ],
            ],
        ];

        $result = $method->invoke($this->analyzer, $plan);

        $this->assertEquals('index_scan', $result['scan_type']);
        $this->assertEquals(1, $result['rows_examined']);
        $this->assertEquals('users_pkey', $result['index_used']);
        $this->assertEmpty($result['warnings']);
    }

    /** @test */
    public function it_normalizes_sqlite_explain_output(): void
    {
        $reflection = new \ReflectionClass($this->analyzer);
        $method = $reflection->getMethod('normalizeSqlite');
        $method->setAccessible(true);

        $rows = [
            (object) ['detail' => 'SCAN users'],
        ];

        $result = $method->invoke($this->analyzer, $rows);

        $this->assertEquals('full_scan', $result['scan_type']);
        $this->assertNotEmpty($result['warnings']);
    }

    /** @test */
    public function it_normalizes_sqlite_explain_with_index(): void
    {
        $reflection = new \ReflectionClass($this->analyzer);
        $method = $reflection->getMethod('normalizeSqlite');
        $method->setAccessible(true);

        $rows = [
            (object) ['detail' => 'SEARCH users USING INDEX users_email_idx (email=?)'],
        ];

        $result = $method->invoke($this->analyzer, $rows);

        $this->assertEquals('index_scan', $result['scan_type']);
        $this->assertEmpty($result['warnings']);
    }

    /** @test */
    public function it_normalizes_sqlite_explain_with_using_index(): void
    {
        $reflection = new \ReflectionClass($this->analyzer);
        $method = $reflection->getMethod('normalizeSqlite');
        $method->setAccessible(true);

        $rows = [
            (object) ['detail' => 'USING INDEX idx_orders_user_id'],
        ];

        $result = $method->invoke($this->analyzer, $rows);

        $this->assertEquals('index_scan', $result['scan_type']);
        $this->assertEquals('idx_orders_user_id', $result['index_used']);
    }

    /** @test */
    public function normalize_output_has_consistent_structure(): void
    {
        $reflection = new \ReflectionClass($this->analyzer);
        $methods = [
            'normalizeMysql' => [[(object) ['type' => 'const', 'rows' => 1, 'key' => 'PRIMARY', 'possible_keys' => 'PRIMARY', 'table' => 'users', 'Extra' => '']]],
            'normalizePostgres' => [[['Plan' => ['Node Type' => 'Index Scan', 'Plan Rows' => 1, 'Index Name' => 'pkey']]]],
            'normalizeSqlite' => [[(object) ['detail' => 'SEARCH users USING INDEX idx (id=?)']]],
        ];

        $requiredKeys = ['raw_output', 'scan_type', 'rows_examined', 'index_used', 'possible_indexes', 'warnings', 'suggestions'];

        foreach ($methods as $methodName => $args) {
            $method = $reflection->getMethod($methodName);
            $method->setAccessible(true);

            $result = $method->invoke($this->analyzer, ...$args);

            foreach ($requiredKeys as $key) {
                $this->assertArrayHasKey(
                    $key,
                    $result,
                    "{$methodName}() result should contain '{$key}'"
                );
            }
        }
    }
}

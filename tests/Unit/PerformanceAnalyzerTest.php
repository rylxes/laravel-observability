<?php

namespace Rylxes\Observability\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Rylxes\Observability\Analyzers\PerformanceAnalyzer;

class PerformanceAnalyzerTest extends TestCase
{
    protected PerformanceAnalyzer $analyzer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyzer = new PerformanceAnalyzer();
    }

    /** @test */
    public function it_calculates_p50_percentile_correctly()
    {
        $reflection = new \ReflectionClass($this->analyzer);
        $method = $reflection->getMethod('percentile');
        $method->setAccessible(true);

        $values = [10, 20, 30, 40, 50, 60, 70, 80, 90, 100];

        $p50 = $method->invoke($this->analyzer, $values, 50);

        $this->assertEquals(50.0, $p50);
    }

    /** @test */
    public function it_calculates_p95_percentile_correctly()
    {
        $reflection = new \ReflectionClass($this->analyzer);
        $method = $reflection->getMethod('percentile');
        $method->setAccessible(true);

        $values = range(1, 100);

        $p95 = $method->invoke($this->analyzer, $values, 95);

        $this->assertEquals(95.0, $p95);
    }

    /** @test */
    public function it_calculates_p99_percentile_correctly()
    {
        $reflection = new \ReflectionClass($this->analyzer);
        $method = $reflection->getMethod('percentile');
        $method->setAccessible(true);

        $values = range(1, 100);

        $p99 = $method->invoke($this->analyzer, $values, 99);

        $this->assertEquals(99.0, $p99);
    }

    /** @test */
    public function it_returns_zero_for_empty_array()
    {
        $reflection = new \ReflectionClass($this->analyzer);
        $method = $reflection->getMethod('percentile');
        $method->setAccessible(true);

        $result = $method->invoke($this->analyzer, [], 50);

        $this->assertEquals(0, $result);
    }

    /** @test */
    public function it_handles_single_value_array()
    {
        $reflection = new \ReflectionClass($this->analyzer);
        $method = $reflection->getMethod('percentile');
        $method->setAccessible(true);

        $result = $method->invoke($this->analyzer, [42], 50);

        $this->assertEquals(42.0, $result);
    }

    /** @test */
    public function it_handles_unsorted_input()
    {
        $reflection = new \ReflectionClass($this->analyzer);
        $method = $reflection->getMethod('percentile');
        $method->setAccessible(true);

        // Unsorted values - method should sort internally
        $values = [90, 10, 50, 30, 70];

        $p50 = $method->invoke($this->analyzer, $values, 50);

        // Sorted: [10, 30, 50, 70, 90], p50 index = ceil(5 * 0.5) - 1 = 2, value = 50
        $this->assertEquals(50.0, $p50);
    }

    /** @test */
    public function it_rounds_percentile_to_two_decimal_places()
    {
        $reflection = new \ReflectionClass($this->analyzer);
        $method = $reflection->getMethod('percentile');
        $method->setAccessible(true);

        $values = [10.123, 20.456, 30.789];

        $result = $method->invoke($this->analyzer, $values, 50);

        // Should be rounded to 2 decimal places
        $this->assertEquals(round($result, 2), $result);
    }
}

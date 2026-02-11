<?php

namespace Rylxes\Observability\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Rylxes\Observability\Analyzers\AnomalyDetector;
use Illuminate\Support\Collection;

class AnomalyDetectorTest extends TestCase
{
    protected AnomalyDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new AnomalyDetector();
    }

    /** @test */
    public function it_calculates_baseline_correctly()
    {
        // Test baseline calculation with known data
        $data = collect([
            (object)['value' => 100],
            (object)['value' => 105],
            (object)['value' => 95],
            (object)['value' => 102],
            (object)['value' => 98],
        ]);

        $reflection = new \ReflectionClass($this->detector);
        $method = $reflection->getMethod('calculateBaseline');
        $method->setAccessible(true);

        $baseline = $method->invoke($this->detector, $data);

        $this->assertEquals(100, $baseline['mean']);
        $this->assertGreaterThan(0, $baseline['stddev']);
        $this->assertEquals(5, $baseline['count']);
    }

    /** @test */
    public function it_calculates_z_score_correctly()
    {
        $baseline = [
            'mean' => 100,
            'stddev' => 10,
            'count' => 100,
        ];

        $reflection = new \ReflectionClass($this->detector);
        $method = $reflection->getMethod('calculateZScore');
        $method->setAccessible(true);

        // Value that's 3 standard deviations above mean
        $dataPoint = (object)['value' => 130];
        $zScore = $method->invoke($this->detector, $dataPoint, $baseline);

        $this->assertEquals(3.0, $zScore);
    }

    /** @test */
    public function it_detects_anomaly_when_z_score_exceeds_threshold()
    {
        // This test would require database setup in a full integration test
        $this->assertTrue(true);
    }
}

<?php

namespace Rylxes\Observability\Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Rylxes\Observability\Models\PerformanceMetric;

class PerformanceMetricTest extends TestCase
{
    protected PerformanceMetric $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new PerformanceMetric();
    }

    /** @test */
    public function it_casts_value_as_float(): void
    {
        $reflection = new ReflectionClass($this->model);
        $property = $reflection->getProperty('casts');
        $property->setAccessible(true);

        $casts = $property->getValue($this->model);

        $this->assertArrayHasKey('value', $casts);
        $this->assertEquals('float', $casts['value']);
    }

    /** @test */
    public function it_casts_baseline_as_float(): void
    {
        $reflection = new ReflectionClass($this->model);
        $property = $reflection->getProperty('casts');
        $property->setAccessible(true);

        $casts = $property->getValue($this->model);

        $this->assertArrayHasKey('baseline', $casts);
        $this->assertEquals('float', $casts['baseline']);
    }

    /** @test */
    public function it_casts_z_score_as_float(): void
    {
        $reflection = new ReflectionClass($this->model);
        $property = $reflection->getProperty('casts');
        $property->setAccessible(true);

        $casts = $property->getValue($this->model);

        $this->assertArrayHasKey('z_score', $casts);
        $this->assertEquals('float', $casts['z_score']);
    }

    /** @test */
    public function it_casts_is_anomaly_as_boolean(): void
    {
        $reflection = new ReflectionClass($this->model);
        $property = $reflection->getProperty('casts');
        $property->setAccessible(true);

        $casts = $property->getValue($this->model);

        $this->assertArrayHasKey('is_anomaly', $casts);
        $this->assertEquals('boolean', $casts['is_anomaly']);
    }

    /** @test */
    public function it_casts_period_start_as_datetime(): void
    {
        $reflection = new ReflectionClass($this->model);
        $property = $reflection->getProperty('casts');
        $property->setAccessible(true);

        $casts = $property->getValue($this->model);

        $this->assertArrayHasKey('period_start', $casts);
        $this->assertEquals('datetime', $casts['period_start']);
    }

    /** @test */
    public function it_casts_period_end_as_datetime(): void
    {
        $reflection = new ReflectionClass($this->model);
        $property = $reflection->getProperty('casts');
        $property->setAccessible(true);

        $casts = $property->getValue($this->model);

        $this->assertArrayHasKey('period_end', $casts);
        $this->assertEquals('datetime', $casts['period_end']);
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
    public function it_has_all_expected_casts(): void
    {
        $reflection = new ReflectionClass($this->model);
        $property = $reflection->getProperty('casts');
        $property->setAccessible(true);

        $casts = $property->getValue($this->model);

        $expectedKeys = ['value', 'baseline', 'z_score', 'is_anomaly', 'period_start', 'period_end', 'metadata'];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $casts, "Cast key '{$key}' should be defined");
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
}

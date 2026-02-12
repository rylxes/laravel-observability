<?php

namespace Rylxes\Observability\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Mockery;
use Rylxes\Observability\ObservabilityManager;
use Rylxes\Observability\Analyzers\PerformanceAnalyzer;
use Rylxes\Observability\Analyzers\SlowQueryDetector;
use Rylxes\Observability\Analyzers\AnomalyDetector;
use Rylxes\Observability\Exporters\PrometheusExporter;

class ObservabilityManagerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_has_performance_method()
    {
        $reflection = new \ReflectionClass(ObservabilityManager::class);

        $this->assertTrue($reflection->hasMethod('performance'));
        $method = $reflection->getMethod('performance');
        $this->assertTrue($method->isPublic());
    }

    /** @test */
    public function it_has_slow_queries_method()
    {
        $reflection = new \ReflectionClass(ObservabilityManager::class);

        $this->assertTrue($reflection->hasMethod('slowQueries'));
        $method = $reflection->getMethod('slowQueries');
        $this->assertTrue($method->isPublic());
    }

    /** @test */
    public function it_has_anomalies_method()
    {
        $reflection = new \ReflectionClass(ObservabilityManager::class);

        $this->assertTrue($reflection->hasMethod('anomalies'));
        $method = $reflection->getMethod('anomalies');
        $this->assertTrue($method->isPublic());
    }

    /** @test */
    public function it_has_prometheus_method()
    {
        $reflection = new \ReflectionClass(ObservabilityManager::class);

        $this->assertTrue($reflection->hasMethod('prometheus'));
        $method = $reflection->getMethod('prometheus');
        $this->assertTrue($method->isPublic());
    }

    /** @test */
    public function it_has_analyze_method()
    {
        $reflection = new \ReflectionClass(ObservabilityManager::class);

        $this->assertTrue($reflection->hasMethod('analyze'));
        $method = $reflection->getMethod('analyze');
        $this->assertTrue($method->isPublic());

        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertEquals('days', $params[0]->getName());
        $this->assertEquals(1, $params[0]->getDefaultValue());
    }

    /** @test */
    public function it_has_export_metrics_method()
    {
        $reflection = new \ReflectionClass(ObservabilityManager::class);

        $this->assertTrue($reflection->hasMethod('exportMetrics'));
        $method = $reflection->getMethod('exportMetrics');
        $this->assertTrue($method->isPublic());
    }

    /** @test */
    public function it_accepts_app_in_constructor()
    {
        $reflection = new \ReflectionClass(ObservabilityManager::class);
        $constructor = $reflection->getConstructor();

        $this->assertNotNull($constructor);
        $this->assertCount(1, $constructor->getParameters());
        $this->assertEquals('app', $constructor->getParameters()[0]->getName());
    }

    /** @test */
    public function performance_returns_correct_type()
    {
        $reflection = new \ReflectionClass(ObservabilityManager::class);
        $method = $reflection->getMethod('performance');
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertEquals(PerformanceAnalyzer::class, $returnType->getName());
    }

    /** @test */
    public function slow_queries_returns_correct_type()
    {
        $reflection = new \ReflectionClass(ObservabilityManager::class);
        $method = $reflection->getMethod('slowQueries');
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertEquals(SlowQueryDetector::class, $returnType->getName());
    }

    /** @test */
    public function anomalies_returns_correct_type()
    {
        $reflection = new \ReflectionClass(ObservabilityManager::class);
        $method = $reflection->getMethod('anomalies');
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertEquals(AnomalyDetector::class, $returnType->getName());
    }

    /** @test */
    public function prometheus_returns_correct_type()
    {
        $reflection = new \ReflectionClass(ObservabilityManager::class);
        $method = $reflection->getMethod('prometheus');
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertEquals(PrometheusExporter::class, $returnType->getName());
    }
}

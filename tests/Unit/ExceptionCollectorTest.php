<?php

namespace Rylxes\Observability\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Mockery;
use Illuminate\Container\Container;
use Illuminate\Config\Repository;
use Illuminate\Support\Facades\Facade;
use Rylxes\Observability\Collectors\ExceptionCollector;

class ExceptionCollectorTest extends TestCase
{
    protected ExceptionCollector $collector;

    protected function setUp(): void
    {
        parent::setUp();

        $app = new Container();
        $app->singleton('config', fn () => new Repository([
            'observability' => [
                'enabled' => true,
                'exceptions' => [
                    'enabled' => true,
                    'capture_stack_trace' => true,
                    'max_stack_trace_depth' => 20,
                    'ignored_exceptions' => [],
                    'alert_on_new' => false,
                    'alert_frequency_threshold' => 10,
                ],
                'notifications' => [
                    'throttle' => [
                        'window_minutes' => 15,
                    ],
                ],
                'broadcasting' => [
                    'enabled' => false,
                ],
            ],
        ]));
        $app->singleton('events', fn () => Mockery::mock(
            \Illuminate\Contracts\Events\Dispatcher::class
        )->shouldIgnoreMissing());

        Container::setInstance($app);
        Facade::setFacadeApplication($app);

        $this->collector = new ExceptionCollector();
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
    public function it_has_capture_method(): void
    {
        $reflection = new \ReflectionClass($this->collector);

        $this->assertTrue(
            $reflection->hasMethod('capture'),
            'ExceptionCollector should have a capture() method'
        );

        $method = $reflection->getMethod('capture');
        $this->assertTrue($method->isPublic(), 'capture() should be public');
    }

    /** @test */
    public function it_returns_null_when_disabled(): void
    {
        $app = Container::getInstance();
        $app->singleton('config', fn () => new Repository([
            'observability' => [
                'enabled' => false,
                'exceptions' => ['enabled' => true],
            ],
        ]));

        $exception = new \RuntimeException('Test error');
        $result = $this->collector->capture($exception);

        $this->assertNull($result);
    }

    /** @test */
    public function it_returns_null_when_exceptions_disabled(): void
    {
        $app = Container::getInstance();
        $app->singleton('config', fn () => new Repository([
            'observability' => [
                'enabled' => true,
                'exceptions' => ['enabled' => false],
            ],
        ]));

        $exception = new \RuntimeException('Test error');
        $result = $this->collector->capture($exception);

        $this->assertNull($result);
    }

    /** @test */
    public function it_determines_severity_for_errors(): void
    {
        $reflection = new \ReflectionClass($this->collector);
        $method = $reflection->getMethod('determineSeverity');
        $method->setAccessible(true);

        // Error types should be critical
        $error = new \Error('Fatal error');
        $this->assertEquals('critical', $method->invoke($this->collector, $error));
    }

    /** @test */
    public function it_determines_severity_for_runtime_exceptions(): void
    {
        $reflection = new \ReflectionClass($this->collector);
        $method = $reflection->getMethod('determineSeverity');
        $method->setAccessible(true);

        $exception = new \RuntimeException('Runtime error');
        $this->assertEquals('error', $method->invoke($this->collector, $exception));
    }

    /** @test */
    public function it_determines_severity_for_generic_exceptions(): void
    {
        $reflection = new \ReflectionClass($this->collector);
        $method = $reflection->getMethod('determineSeverity');
        $method->setAccessible(true);

        $exception = new \Exception('Generic error');
        $this->assertEquals('error', $method->invoke($this->collector, $exception));
    }

    /** @test */
    public function it_sanitizes_context_data(): void
    {
        $reflection = new \ReflectionClass($this->collector);
        $method = $reflection->getMethod('sanitizeContext');
        $method->setAccessible(true);

        $context = [
            'url' => 'https://example.com/api/users',
            'password' => 'secret123',
            'token' => 'abc-token-xyz',
            'api_key' => 'key-123',
            'user_id' => 42,
        ];

        $sanitized = $method->invoke($this->collector, $context);

        $this->assertEquals('https://example.com/api/users', $sanitized['url']);
        $this->assertEquals('***REDACTED***', $sanitized['password']);
        $this->assertEquals('***REDACTED***', $sanitized['token']);
        $this->assertEquals('***REDACTED***', $sanitized['api_key']);
        $this->assertEquals(42, $sanitized['user_id']);
    }

    /** @test */
    public function it_sanitizes_nested_context_data(): void
    {
        $reflection = new \ReflectionClass($this->collector);
        $method = $reflection->getMethod('sanitizeContext');
        $method->setAccessible(true);

        $context = [
            'request' => [
                'body' => [
                    'password' => 'secret',
                    'name' => 'John',
                ],
            ],
        ];

        $sanitized = $method->invoke($this->collector, $context);

        $this->assertEquals('***REDACTED***', $sanitized['request']['body']['password']);
        $this->assertEquals('John', $sanitized['request']['body']['name']);
    }

    /** @test */
    public function it_formats_stack_trace(): void
    {
        $reflection = new \ReflectionClass($this->collector);
        $method = $reflection->getMethod('formatStackTrace');
        $method->setAccessible(true);

        $exception = new \RuntimeException('Test');
        $result = $method->invoke($this->collector, $exception);

        $this->assertIsString($result);
        $this->assertJson($result);

        $decoded = json_decode($result, true);
        $this->assertIsArray($decoded);
        $this->assertLessThanOrEqual(20, count($decoded));

        // Each frame should have expected keys
        if (!empty($decoded)) {
            $frame = $decoded[0];
            $this->assertArrayHasKey('file', $frame);
            $this->assertArrayHasKey('line', $frame);
            $this->assertArrayHasKey('function', $frame);
        }
    }

    /** @test */
    public function it_checks_ignored_exceptions(): void
    {
        $reflection = new \ReflectionClass($this->collector);
        $method = $reflection->getMethod('shouldIgnore');
        $method->setAccessible(true);

        // Default config has no ignored exceptions
        $exception = new \RuntimeException('Test');
        $this->assertFalse($method->invoke($this->collector, $exception));
    }

    /** @test */
    public function it_ignores_configured_exception_classes(): void
    {
        $app = Container::getInstance();
        $app->singleton('config', fn () => new Repository([
            'observability' => [
                'enabled' => true,
                'exceptions' => [
                    'enabled' => true,
                    'ignored_exceptions' => [
                        \InvalidArgumentException::class,
                    ],
                ],
            ],
        ]));

        $reflection = new \ReflectionClass($this->collector);
        $method = $reflection->getMethod('shouldIgnore');
        $method->setAccessible(true);

        $ignored = new \InvalidArgumentException('Ignored');
        $this->assertTrue($method->invoke($this->collector, $ignored));

        $notIgnored = new \RuntimeException('Not ignored');
        $this->assertFalse($method->invoke($this->collector, $notIgnored));
    }
}

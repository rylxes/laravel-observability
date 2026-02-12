<?php

namespace Rylxes\Observability\Tests\Unit;

use Carbon\Carbon;
use Illuminate\Container\Container;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Rylxes\Observability\Models\Alert;
use Rylxes\Observability\Notifications\TelegramNotifier;

class TelegramNotifierTest extends TestCase
{
    protected TelegramNotifier $notifier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->notifier = new TelegramNotifier();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Create a mock Alert with the given properties.
     *
     * Uses shouldIgnoreMissing() so unexpected method calls return null,
     * and sets up getAttribute() expectations for property access
     * via Eloquent's __get magic method.
     */
    protected function createMockAlert(array $overrides = []): MockInterface
    {
        $defaults = [
            'severity' => 'critical',
            'title' => 'Test Alert Title',
            'description' => 'Test alert description',
            'source' => null,
            'context' => [],
            'created_at' => Carbon::parse('2025-01-15 10:30:00'),
        ];

        $data = array_merge($defaults, $overrides);

        $alert = Mockery::mock(Alert::class)->shouldIgnoreMissing();
        $alert->shouldReceive('getAttribute')->with('severity')->andReturn($data['severity']);
        $alert->shouldReceive('getAttribute')->with('title')->andReturn($data['title']);
        $alert->shouldReceive('getAttribute')->with('description')->andReturn($data['description']);
        $alert->shouldReceive('getAttribute')->with('source')->andReturn($data['source']);
        $alert->shouldReceive('getAttribute')->with('context')->andReturn($data['context']);
        $alert->shouldReceive('getAttribute')->with('created_at')->andReturn($data['created_at']);

        return $alert;
    }

    /**
     * Invoke the protected buildMessage method via Reflection.
     */
    protected function invokeBuildMessage(Alert $alert): string
    {
        $reflection = new ReflectionClass($this->notifier);
        $method = $reflection->getMethod('buildMessage');
        $method->setAccessible(true);

        return $method->invoke($this->notifier, $alert);
    }

    /** @test */
    public function it_contains_critical_emoji_for_critical_severity(): void
    {
        $alert = $this->createMockAlert(['severity' => 'critical']);
        $message = $this->invokeBuildMessage($alert);

        $this->assertStringContainsString("\xF0\x9F\x9A\xA8", $message);
    }

    /** @test */
    public function it_contains_warning_emoji_for_warning_severity(): void
    {
        $alert = $this->createMockAlert(['severity' => 'warning']);
        $message = $this->invokeBuildMessage($alert);

        $this->assertStringContainsString("\xE2\x9A\xA0", $message);
    }

    /** @test */
    public function it_contains_alert_title_in_message(): void
    {
        $alert = $this->createMockAlert(['title' => 'Slow Response Detected']);
        $message = $this->invokeBuildMessage($alert);

        $this->assertStringContainsString('Slow Response Detected', $message);
    }

    /** @test */
    public function it_contains_alert_description_in_message(): void
    {
        $alert = $this->createMockAlert(['description' => 'Response time exceeded 5000ms']);
        $message = $this->invokeBuildMessage($alert);

        $this->assertStringContainsString('Response time exceeded 5000ms', $message);
    }

    /** @test */
    public function it_includes_source_when_present(): void
    {
        $alert = $this->createMockAlert(['source' => 'api/v1/orders']);
        $message = $this->invokeBuildMessage($alert);

        $this->assertStringContainsString('*Source:*', $message);
        $this->assertStringContainsString('api/v1/orders', $message);
    }

    /** @test */
    public function it_excludes_source_line_when_source_is_null(): void
    {
        $alert = $this->createMockAlert(['source' => null]);
        $message = $this->invokeBuildMessage($alert);

        $this->assertStringNotContainsString('*Source:*', $message);
    }

    /** @test */
    public function it_includes_context_fields_in_message(): void
    {
        $context = [
            'response_time' => '3500ms',
            'endpoint' => '/api/users',
        ];

        $alert = $this->createMockAlert(['context' => $context]);
        $message = $this->invokeBuildMessage($alert);

        $this->assertStringContainsString('*Details:*', $message);
        $this->assertStringContainsString('Response time', $message);
        $this->assertStringContainsString('3500ms', $message);
        $this->assertStringContainsString('Endpoint', $message);
        $this->assertStringContainsString('/api/users', $message);
    }

    /** @test */
    public function it_does_not_include_details_section_when_context_is_empty(): void
    {
        $alert = $this->createMockAlert(['context' => []]);
        $message = $this->invokeBuildMessage($alert);

        $this->assertStringNotContainsString('*Details:*', $message);
    }

    /** @test */
    public function it_includes_severity_label_in_message(): void
    {
        $alert = $this->createMockAlert(['severity' => 'warning']);
        $message = $this->invokeBuildMessage($alert);

        $this->assertStringContainsString('*Severity:* Warning', $message);
    }

    /** @test */
    public function it_includes_formatted_time_in_message(): void
    {
        $createdAt = Carbon::parse('2025-01-15 10:30:00');
        $alert = $this->createMockAlert(['created_at' => $createdAt]);
        $message = $this->invokeBuildMessage($alert);

        $this->assertStringContainsString('*Time:* 2025-01-15 10:30:00', $message);
    }
}

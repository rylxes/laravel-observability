<?php

namespace Rylxes\Observability\Tests\Unit;

use Carbon\Carbon;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Rylxes\Observability\Models\Alert;
use Rylxes\Observability\Notifications\SlackNotifier;

class SlackNotifierTest extends TestCase
{
    protected SlackNotifier $notifier;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up a minimal container so config() calls work
        $app = new Container();
        $app->instance('config', new Repository([
            'observability' => [
                'notifications' => [
                    'slack' => [
                        'channel' => '#alerts',
                        'username' => 'Observability Bot',
                        'icon_emoji' => ':robot_face:',
                    ],
                ],
            ],
        ]));
        Container::setInstance($app);

        $this->notifier = new SlackNotifier();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        Container::setInstance(null);
        parent::tearDown();
    }

    /**
     * Create a mock Alert with the given properties.
     *
     * Uses shouldIgnoreMissing() so unexpected method calls (like setAttribute)
     * return null, and sets up getAttribute() expectations for property access
     * via Eloquent's __get magic method.
     */
    protected function createMockAlert(array $overrides = []): MockInterface
    {
        $defaults = [
            'severity' => 'critical',
            'title' => 'Test Alert Title',
            'description' => 'Test alert description',
            'context' => ['key_one' => 'value1', 'key_two' => 'value2'],
            'created_at' => Carbon::parse('2025-01-15 10:30:00'),
        ];

        $data = array_merge($defaults, $overrides);

        $alert = Mockery::mock(Alert::class)->shouldIgnoreMissing();
        $alert->shouldReceive('getAttribute')->with('severity')->andReturn($data['severity']);
        $alert->shouldReceive('getAttribute')->with('title')->andReturn($data['title']);
        $alert->shouldReceive('getAttribute')->with('description')->andReturn($data['description']);
        $alert->shouldReceive('getAttribute')->with('context')->andReturn($data['context']);
        $alert->shouldReceive('getAttribute')->with('created_at')->andReturn($data['created_at']);

        return $alert;
    }

    /**
     * Invoke the protected buildPayload method via Reflection.
     */
    protected function invokeBuildPayload(Alert $alert): array
    {
        $reflection = new ReflectionClass($this->notifier);
        $method = $reflection->getMethod('buildPayload');
        $method->setAccessible(true);

        return $method->invoke($this->notifier, $alert);
    }

    /** @test */
    public function it_maps_critical_severity_to_danger_color(): void
    {
        $alert = $this->createMockAlert(['severity' => 'critical']);
        $payload = $this->invokeBuildPayload($alert);

        $this->assertEquals('danger', $payload['attachments'][0]['color']);
    }

    /** @test */
    public function it_maps_warning_severity_to_warning_color(): void
    {
        $alert = $this->createMockAlert(['severity' => 'warning']);
        $payload = $this->invokeBuildPayload($alert);

        $this->assertEquals('warning', $payload['attachments'][0]['color']);
    }

    /** @test */
    public function it_maps_info_severity_to_good_color(): void
    {
        $alert = $this->createMockAlert(['severity' => 'info']);
        $payload = $this->invokeBuildPayload($alert);

        $this->assertEquals('good', $payload['attachments'][0]['color']);
    }

    /** @test */
    public function it_includes_context_fields_as_slack_fields(): void
    {
        $context = [
            'response_time' => '250ms',
            'endpoint' => '/api/users',
        ];

        $alert = $this->createMockAlert(['context' => $context]);
        $payload = $this->invokeBuildPayload($alert);

        $fields = $payload['attachments'][0]['fields'];

        $this->assertCount(2, $fields);

        $this->assertEquals('Response time', $fields[0]['title']);
        $this->assertEquals('250ms', $fields[0]['value']);
        $this->assertTrue($fields[0]['short']);

        $this->assertEquals('Endpoint', $fields[1]['title']);
        $this->assertEquals('/api/users', $fields[1]['value']);
        $this->assertTrue($fields[1]['short']);
    }

    /** @test */
    public function it_includes_channel_username_and_icon_emoji_from_config(): void
    {
        $alert = $this->createMockAlert();
        $payload = $this->invokeBuildPayload($alert);

        $this->assertArrayHasKey('channel', $payload);
        $this->assertEquals('#alerts', $payload['channel']);

        $this->assertArrayHasKey('username', $payload);
        $this->assertEquals('Observability Bot', $payload['username']);

        $this->assertArrayHasKey('icon_emoji', $payload);
        $this->assertEquals(':robot_face:', $payload['icon_emoji']);

        $this->assertArrayHasKey('attachments', $payload);
    }

    /** @test */
    public function it_includes_alert_title_and_description_in_attachment(): void
    {
        $alert = $this->createMockAlert([
            'title' => 'High Memory Usage',
            'description' => 'Memory usage exceeded threshold',
        ]);

        $payload = $this->invokeBuildPayload($alert);

        $this->assertEquals('High Memory Usage', $payload['attachments'][0]['title']);
        $this->assertEquals('Memory usage exceeded threshold', $payload['attachments'][0]['text']);
    }

    /** @test */
    public function it_includes_footer_and_timestamp_in_attachment(): void
    {
        $createdAt = Carbon::parse('2025-06-01 12:00:00');
        $alert = $this->createMockAlert(['created_at' => $createdAt]);
        $payload = $this->invokeBuildPayload($alert);

        $this->assertEquals('Laravel Observability', $payload['attachments'][0]['footer']);
        $this->assertEquals($createdAt->timestamp, $payload['attachments'][0]['ts']);
    }

    /** @test */
    public function it_handles_empty_context_gracefully(): void
    {
        $alert = $this->createMockAlert(['context' => []]);
        $payload = $this->invokeBuildPayload($alert);

        $this->assertEmpty($payload['attachments'][0]['fields']);
    }

    /** @test */
    public function it_handles_null_context_gracefully(): void
    {
        $alert = $this->createMockAlert(['context' => null]);
        $payload = $this->invokeBuildPayload($alert);

        $this->assertEmpty($payload['attachments'][0]['fields']);
    }
}

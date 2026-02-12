<?php

namespace Rylxes\Observability\Tests\Unit\Jobs;

use PHPUnit\Framework\TestCase;
use Rylxes\Observability\Jobs\StoreQueryLogJob;
use Illuminate\Contracts\Queue\ShouldQueue;

class StoreQueryLogJobTest extends TestCase
{
    public function test_it_implements_should_queue(): void
    {
        $this->assertTrue(
            in_array(ShouldQueue::class, class_implements(StoreQueryLogJob::class))
        );
    }

    public function test_it_uses_required_traits(): void
    {
        $traits = class_uses_recursive(StoreQueryLogJob::class);

        $this->assertContains(\Illuminate\Foundation\Bus\Dispatchable::class, $traits);
        $this->assertContains(\Illuminate\Queue\InteractsWithQueue::class, $traits);
        $this->assertContains(\Illuminate\Bus\Queueable::class, $traits);
        $this->assertContains(\Illuminate\Queue\SerializesModels::class, $traits);
    }

    public function test_constructor_accepts_query_data(): void
    {
        $reflection = new \ReflectionClass(StoreQueryLogJob::class);
        $constructor = $reflection->getConstructor();

        $this->assertNotNull($constructor);
        $this->assertCount(1, $constructor->getParameters());
        $this->assertEquals('queryData', $constructor->getParameters()[0]->getName());
    }

    public function test_constructor_parameter_is_array_type(): void
    {
        $reflection = new \ReflectionClass(StoreQueryLogJob::class);
        $constructor = $reflection->getConstructor();
        $param = $constructor->getParameters()[0];

        $this->assertNotNull($param->getType());
        $this->assertEquals('array', $param->getType()->getName());
    }

    public function test_class_has_handle_method(): void
    {
        $reflection = new \ReflectionClass(StoreQueryLogJob::class);

        $this->assertTrue($reflection->hasMethod('handle'));
        $this->assertTrue($reflection->getMethod('handle')->isPublic());
    }
}

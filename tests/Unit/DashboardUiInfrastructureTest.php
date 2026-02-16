<?php

namespace Rylxes\Observability\Tests\Unit;

use Illuminate\Contracts\View\View;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Rylxes\Observability\Http\Controllers\ObservabilityDashboardController;
use Rylxes\Observability\Support\DashboardRouteConfig;

class DashboardUiInfrastructureTest extends TestCase
{
    /** @test */
    public function dashboard_route_config_exposes_expected_static_methods(): void
    {
        $reflection = new ReflectionClass(DashboardRouteConfig::class);

        $this->assertTrue($reflection->hasMethod('availableGuards'));
        $this->assertTrue($reflection->hasMethod('middleware'));
        $this->assertTrue($reflection->hasMethod('routePrefix'));

        $availableGuards = $reflection->getMethod('availableGuards');
        $middleware = $reflection->getMethod('middleware');
        $routePrefix = $reflection->getMethod('routePrefix');

        $this->assertTrue($availableGuards->isPublic());
        $this->assertTrue($availableGuards->isStatic());
        $this->assertEquals('array', $availableGuards->getReturnType()?->getName());

        $this->assertTrue($middleware->isPublic());
        $this->assertTrue($middleware->isStatic());
        $this->assertEquals('array', $middleware->getReturnType()?->getName());

        $this->assertTrue($routePrefix->isPublic());
        $this->assertTrue($routePrefix->isStatic());
        $this->assertEquals('string', $routePrefix->getReturnType()?->getName());
    }

    /** @test */
    public function dashboard_controller_is_invokable_and_returns_view(): void
    {
        $reflection = new ReflectionClass(ObservabilityDashboardController::class);

        $this->assertTrue($reflection->hasMethod('__invoke'));

        $invoke = $reflection->getMethod('__invoke');

        $this->assertTrue($invoke->isPublic());
        $this->assertEquals(View::class, $invoke->getReturnType()?->getName());
    }
}

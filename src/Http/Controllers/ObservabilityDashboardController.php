<?php

namespace Rylxes\Observability\Http\Controllers;

use Illuminate\Contracts\View\View;

class ObservabilityDashboardController
{
    /**
     * Display the built-in observability dashboard.
     */
    public function __invoke(): View
    {
        $resolveRoute = static function (string $name, array $parameters = []): ?string {
            try {
                return route($name, $parameters);
            } catch (\Throwable) {
                return null;
            }
        };

        return view('observability::dashboard', [
            'apiEndpoints' => [
                'dashboard' => $resolveRoute('observability.dashboard'),
                'traces' => $resolveRoute('observability.traces'),
                'alerts' => $resolveRoute('observability.alerts'),
                'health' => $resolveRoute('observability.health'),
                'resolveAlert' => $resolveRoute('observability.alerts.resolve', ['alert' => '__ALERT_ID__']),
            ],
            'refreshIntervalSeconds' => max((int) config('observability.dashboard.refresh_interval_seconds', 30), 5),
        ]);
    }
}

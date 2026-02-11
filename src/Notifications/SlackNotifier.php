<?php

namespace Rylxes\Observability\Notifications;

use GuzzleHttp\Client;
use Rylxes\Observability\Models\Alert;

class SlackNotifier
{
    protected Client $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    /**
     * Send notification to Slack.
     */
    public function notify(Alert $alert): bool
    {
        if (!config('observability.notifications.slack.enabled')) {
            return false;
        }

        $webhookUrl = config('observability.notifications.slack.webhook_url');

        if (!$webhookUrl) {
            \Log::error('Slack webhook URL not configured');
            return false;
        }

        // Check throttling
        if ($this->shouldThrottle($alert)) {
            return false;
        }

        $payload = $this->buildPayload($alert);

        try {
            $response = $this->client->post($webhookUrl, [
                'json' => $payload,
                'timeout' => 5,
            ]);

            return $response->getStatusCode() === 200;
        } catch (\Exception $e) {
            \Log::error('Failed to send Slack notification: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Build Slack message payload.
     */
    protected function buildPayload(Alert $alert): array
    {
        $color = match ($alert->severity) {
            'critical' => 'danger',
            'error' => 'danger',
            'warning' => 'warning',
            default => 'good',
        };

        $fields = [];

        // Add context fields
        if ($alert->context) {
            foreach ($alert->context as $key => $value) {
                if (is_scalar($value)) {
                    $fields[] = [
                        'title' => ucfirst(str_replace('_', ' ', $key)),
                        'value' => $value,
                        'short' => true,
                    ];
                }
            }
        }

        return [
            'channel' => config('observability.notifications.slack.channel'),
            'username' => config('observability.notifications.slack.username'),
            'icon_emoji' => config('observability.notifications.slack.icon_emoji'),
            'attachments' => [
                [
                    'color' => $color,
                    'title' => $alert->title,
                    'text' => $alert->description,
                    'fields' => $fields,
                    'footer' => 'Laravel Observability',
                    'footer_icon' => 'https://laravel.com/img/logomark.min.svg',
                    'ts' => $alert->created_at->timestamp,
                ],
            ],
        ];
    }

    /**
     * Check if notification should be throttled.
     */
    protected function shouldThrottle(Alert $alert): bool
    {
        if (!config('observability.notifications.throttle.enabled')) {
            return false;
        }

        $window = config('observability.notifications.throttle.window_minutes', 15);
        $maxAlerts = config('observability.notifications.throttle.max_alerts_per_window', 1);

        $recentAlerts = Alert::where('fingerprint', $alert->fingerprint)
            ->where('notified', true)
            ->where('notified_at', '>=', now()->subMinutes($window))
            ->count();

        return $recentAlerts >= $maxAlerts;
    }
}

<?php

namespace Rylxes\Observability\Notifications;

use GuzzleHttp\Client;
use Rylxes\Observability\Models\Alert;

class TelegramNotifier
{
    protected Client $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    /**
     * Send notification to Telegram.
     */
    public function notify(Alert $alert): bool
    {
        if (!config('observability.notifications.telegram.enabled')) {
            return false;
        }

        $botToken = config('observability.notifications.telegram.bot_token');
        $chatId = config('observability.notifications.telegram.chat_id');

        if (!$botToken || !$chatId) {
            \Log::error('Telegram bot token or chat ID not configured');
            return false;
        }

        $message = $this->buildMessage($alert);

        try {
            $response = $this->client->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                'json' => [
                    'chat_id' => $chatId,
                    'text' => $message,
                    'parse_mode' => 'Markdown',
                ],
                'timeout' => 5,
            ]);

            return $response->getStatusCode() === 200;
        } catch (\Exception $e) {
            \Log::error('Failed to send Telegram notification: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Build Telegram message.
     */
    protected function buildMessage(Alert $alert): string
    {
        $emoji = match ($alert->severity) {
            'critical' => '🚨',
            'error' => '❌',
            'warning' => '⚠️',
            default => 'ℹ️',
        };

        $message = "{$emoji} *{$alert->title}*\n\n";
        $message .= "{$alert->description}\n\n";

        if ($alert->source) {
            $message .= "*Source:* {$alert->source}\n";
        }

        $message .= "*Severity:* " . ucfirst($alert->severity) . "\n";
        $message .= "*Time:* {$alert->created_at->format('Y-m-d H:i:s')}\n";

        // Add context
        if ($alert->context && is_array($alert->context)) {
            $message .= "\n*Details:*\n";
            foreach ($alert->context as $key => $value) {
                if (is_scalar($value)) {
                    $message .= "• " . ucfirst(str_replace('_', ' ', $key)) . ": {$value}\n";
                }
            }
        }

        return $message;
    }
}

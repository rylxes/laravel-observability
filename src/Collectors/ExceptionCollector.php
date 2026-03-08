<?php

namespace Rylxes\Observability\Collectors;

use Throwable;
use Rylxes\Observability\Models\ExceptionLog;
use Rylxes\Observability\Models\Alert;
use Rylxes\Observability\Events\AlertTriggered;

class ExceptionCollector
{
    /**
     * Capture an exception and store it.
     */
    public function capture(Throwable $exception, ?string $traceId = null, array $context = []): ?ExceptionLog
    {
        if (!config('observability.enabled') || !config('observability.exceptions.enabled', true)) {
            return null;
        }

        // Check if this exception class should be ignored
        if ($this->shouldIgnore($exception)) {
            return null;
        }

        $class = get_class($exception);
        $file = $exception->getFile();
        $line = $exception->getLine();
        $groupHash = ExceptionLog::generateGroupHash($class, $file, $line);

        // Check for existing group within the last 24 hours
        $existing = ExceptionLog::where('group_hash', $groupHash)
            ->where('resolved', false)
            ->where('created_at', '>=', now()->subDay())
            ->latest()
            ->first();

        if ($existing) {
            return $this->incrementExisting($existing);
        }

        return $this->createNew($exception, $traceId, $groupHash, $context);
    }

    /**
     * Increment occurrence count on an existing exception group.
     */
    protected function incrementExisting(ExceptionLog $existing): ExceptionLog
    {
        $existing->update([
            'occurrence_count' => $existing->occurrence_count + 1,
            'last_seen_at' => now(),
        ]);

        // Check if frequency threshold exceeded for alert
        $threshold = config('observability.exceptions.alert_frequency_threshold', 10);
        if ($existing->occurrence_count === $threshold) {
            $this->createFrequencyAlert($existing);
        }

        return $existing;
    }

    /**
     * Create a new exception log entry.
     */
    protected function createNew(Throwable $exception, ?string $traceId, string $groupHash, array $context): ExceptionLog
    {
        $stackTrace = null;
        if (config('observability.exceptions.capture_stack_trace', true)) {
            $stackTrace = $this->formatStackTrace($exception);
        }

        $severity = $this->determineSeverity($exception);

        $exceptionLog = ExceptionLog::create([
            'trace_id' => $traceId,
            'exception_class' => get_class($exception),
            'message' => mb_substr($exception->getMessage(), 0, 65535),
            'code' => $exception->getCode() ? (string) $exception->getCode() : null,
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'stack_trace' => $stackTrace,
            'group_hash' => $groupHash,
            'occurrence_count' => 1,
            'first_seen_at' => now(),
            'last_seen_at' => now(),
            'context' => $this->sanitizeContext($context),
            'severity' => $severity,
        ]);

        // Alert on new exception if configured
        if (config('observability.exceptions.alert_on_new', true)) {
            $this->createNewExceptionAlert($exceptionLog);
        }

        return $exceptionLog;
    }

    /**
     * Check if exception should be ignored.
     */
    protected function shouldIgnore(Throwable $exception): bool
    {
        $ignoredClasses = config('observability.exceptions.ignored_exceptions', []);

        foreach ($ignoredClasses as $ignoredClass) {
            if ($exception instanceof $ignoredClass) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine severity based on exception type.
     */
    protected function determineSeverity(Throwable $exception): string
    {
        if ($exception instanceof \Error) {
            return 'critical';
        }

        if ($exception instanceof \RuntimeException) {
            return 'error';
        }

        return 'error';
    }

    /**
     * Format stack trace with filtering.
     */
    protected function formatStackTrace(Throwable $exception): string
    {
        $maxDepth = config('observability.exceptions.max_stack_trace_depth', 20);
        $frames = $exception->getTrace();

        // Filter vendor/framework frames and limit depth
        $filteredFrames = [];
        foreach ($frames as $frame) {
            if (count($filteredFrames) >= $maxDepth) {
                break;
            }

            $file = $frame['file'] ?? '';

            // Skip vendor internals for readability (keep the frame but mark it)
            $filteredFrames[] = [
                'file' => $file,
                'line' => $frame['line'] ?? null,
                'class' => $frame['class'] ?? null,
                'function' => $frame['function'] ?? null,
                'type' => $frame['type'] ?? null,
            ];
        }

        return json_encode($filteredFrames);
    }

    /**
     * Sanitize context data to remove sensitive information.
     */
    protected function sanitizeContext(array $context): array
    {
        $sensitiveFields = ['password', 'password_confirmation', 'token', 'secret', 'api_key', 'authorization'];

        array_walk_recursive($context, function (&$value, $key) use ($sensitiveFields) {
            if (in_array(strtolower($key), $sensitiveFields, true)) {
                $value = '***REDACTED***';
            }
        });

        return $context;
    }

    /**
     * Create alert for a new exception.
     */
    protected function createNewExceptionAlert(ExceptionLog $exceptionLog): void
    {
        $fingerprint = 'exception:' . $exceptionLog->group_hash;

        // Check for existing alert with same fingerprint in throttle window
        $throttleWindow = config('observability.notifications.throttle.window_minutes', 15);
        $existingAlert = Alert::where('fingerprint', $fingerprint)
            ->where('created_at', '>=', now()->subMinutes($throttleWindow))
            ->exists();

        if ($existingAlert) {
            return;
        }

        $alert = Alert::create([
            'alert_type' => 'exception',
            'severity' => $exceptionLog->severity,
            'title' => 'New Exception: ' . class_basename($exceptionLog->exception_class),
            'description' => $exceptionLog->message,
            'source' => $exceptionLog->file . ':' . $exceptionLog->line,
            'context' => [
                'exception_class' => $exceptionLog->exception_class,
                'file' => $exceptionLog->file,
                'line' => $exceptionLog->line,
                'trace_id' => $exceptionLog->trace_id,
                'group_hash' => $exceptionLog->group_hash,
            ],
            'fingerprint' => $fingerprint,
        ]);

        if (config('observability.broadcasting.enabled')) {
            event(new AlertTriggered(
                alertId: $alert->id,
                alertType: $alert->alert_type,
                severity: $alert->severity,
                title: $alert->title,
                description: $alert->description,
                source: $alert->source,
                context: $alert->context,
            ));
        }
    }

    /**
     * Create alert for exception frequency spike.
     */
    protected function createFrequencyAlert(ExceptionLog $exceptionLog): void
    {
        $fingerprint = 'exception_frequency:' . $exceptionLog->group_hash;

        $throttleWindow = config('observability.notifications.throttle.window_minutes', 15);
        $existingAlert = Alert::where('fingerprint', $fingerprint)
            ->where('created_at', '>=', now()->subMinutes($throttleWindow))
            ->exists();

        if ($existingAlert) {
            return;
        }

        $threshold = config('observability.exceptions.alert_frequency_threshold', 10);

        $alert = Alert::create([
            'alert_type' => 'exception_frequency',
            'severity' => 'warning',
            'title' => 'Exception Frequency Spike: ' . class_basename($exceptionLog->exception_class),
            'description' => "Exception occurred {$exceptionLog->occurrence_count} times (threshold: {$threshold})",
            'source' => $exceptionLog->file . ':' . $exceptionLog->line,
            'context' => [
                'exception_class' => $exceptionLog->exception_class,
                'occurrence_count' => $exceptionLog->occurrence_count,
                'group_hash' => $exceptionLog->group_hash,
            ],
            'fingerprint' => $fingerprint,
        ]);

        if (config('observability.broadcasting.enabled')) {
            event(new AlertTriggered(
                alertId: $alert->id,
                alertType: $alert->alert_type,
                severity: $alert->severity,
                title: $alert->title,
                description: $alert->description,
                source: $alert->source,
                context: $alert->context,
            ));
        }
    }
}

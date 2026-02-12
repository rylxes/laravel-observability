<?php

namespace Rylxes\Observability\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Rylxes\Observability\Models\RequestTrace;
use Rylxes\Observability\Collectors\DatabaseQueryCollector;
use Rylxes\Observability\Jobs\StoreTraceJob;
use Rylxes\Observability\Events\NewTraceRecorded;

class RequestTracingMiddleware
{
    protected float $startTime;
    protected int $startMemory;
    protected string $traceId;

    public function __construct(
        protected DatabaseQueryCollector $queryCollector
    ) {
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if observability is enabled
        if (!config('observability.enabled') || !config('observability.tracing.enabled')) {
            return $next($request);
        }

        // Check if route should be excluded
        if ($this->shouldExclude($request)) {
            return $next($request);
        }

        // Check sample rate
        if (!$this->shouldSample()) {
            return $next($request);
        }

        // Initialize tracing
        $this->initializeTrace($request);

        // Start query collection
        $this->queryCollector->start($this->traceId);

        $response = $next($request);

        // Record the trace
        $this->recordTrace($request, $response);

        return $response;
    }

    /**
     * Initialize trace with unique ID and timing.
     */
    protected function initializeTrace(Request $request): void
    {
        $this->traceId = $this->generateTraceId();
        $this->startTime = microtime(true);
        $this->startMemory = memory_get_usage(true);

        // Store trace ID in request for downstream use
        $request->attributes->set('trace_id', $this->traceId);

        // Check for parent trace (distributed tracing)
        $parentTraceId = $request->header('X-Trace-Parent-Id');
        if ($parentTraceId) {
            $request->attributes->set('parent_trace_id', $parentTraceId);
        }
    }

    /**
     * Record the complete trace.
     */
    protected function recordTrace(Request $request, Response $response): void
    {
        $duration = (microtime(true) - $this->startTime) * 1000; // Convert to ms
        $memoryUsage = memory_get_usage(true) - $this->startMemory;

        // Get query statistics
        $queryStats = $this->queryCollector->stop($this->traceId);

        $trace = [
            'trace_id' => $this->traceId,
            'parent_trace_id' => $request->attributes->get('parent_trace_id'),
            'route_name' => $request->route()?->getName(),
            'route_action' => $request->route()?->getActionName(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'status_code' => $response->getStatusCode(),
            'duration_ms' => round($duration, 2),
            'memory_usage' => $memoryUsage,
            'query_count' => $queryStats['count'] ?? 0,
            'query_time_ms' => $queryStats['time_ms'] ?? 0,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user_id' => auth()->id(),
        ];

        // Capture headers if enabled
        if (config('observability.tracing.capture_headers')) {
            $trace['headers'] = $this->sanitizeHeaders($request->headers->all());
        }

        // Capture payload if enabled
        if (config('observability.tracing.capture_payload')) {
            $trace['request_payload'] = $this->capturePayload($request);
        }

        // Add metadata
        $trace['metadata'] = [
            'session_id' => $request->session()?->getId(),
            'referer' => $request->header('referer'),
            'is_ajax' => $request->ajax(),
            'is_json' => $request->expectsJson(),
        ];

        // Save to database or queue
        if (config('observability.queue.enabled')) {
            StoreTraceJob::dispatch($trace);
        } else {
            RequestTrace::create($trace);
        }

        // Broadcast real-time event
        if (config('observability.broadcasting.enabled')) {
            event(new NewTraceRecorded(
                traceId: $trace['trace_id'],
                method: $trace['method'],
                url: $trace['url'],
                statusCode: $trace['status_code'],
                durationMs: $trace['duration_ms'],
                queryCount: $trace['query_count'],
                routeName: $trace['route_name'] ?? null,
            ));
        }
    }

    /**
     * Generate unique trace ID.
     */
    protected function generateTraceId(): string
    {
        // OpenTelemetry compatible 32-character hex string
        return Str::uuid()->toString();
    }

    /**
     * Check if request should be excluded from tracing.
     */
    protected function shouldExclude(Request $request): bool
    {
        $routeName = $request->route()?->getName();
        $path = $request->path();

        // Check excluded routes
        foreach (config('observability.tracing.excluded_routes', []) as $pattern) {
            if ($routeName && Str::is($pattern, $routeName)) {
                return true;
            }
        }

        // Check excluded paths
        foreach (config('observability.tracing.excluded_paths', []) as $pattern) {
            if (Str::is($pattern, '/' . $path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if request should be sampled based on sample rate.
     */
    protected function shouldSample(): bool
    {
        $sampleRate = config('observability.tracing.sample_rate', 1.0);

        if ($sampleRate >= 1.0) {
            return true;
        }

        if ($sampleRate <= 0.0) {
            return false;
        }

        return (mt_rand() / mt_getrandmax()) < $sampleRate;
    }

    /**
     * Sanitize headers to remove sensitive information.
     */
    protected function sanitizeHeaders(array $headers): array
    {
        $sensitiveHeaders = ['authorization', 'cookie', 'x-api-key', 'x-auth-token'];

        foreach ($sensitiveHeaders as $header) {
            if (isset($headers[$header])) {
                $headers[$header] = ['***REDACTED***'];
            }
        }

        return $headers;
    }

    /**
     * Capture request payload with size limit.
     */
    protected function capturePayload(Request $request): ?array
    {
        $payload = $request->all();
        $json = json_encode($payload);

        $maxSize = config('observability.tracing.max_payload_size', 10000);

        if (strlen($json) > $maxSize) {
            return [
                '_truncated' => true,
                '_size' => strlen($json),
                '_message' => 'Payload too large to capture',
            ];
        }

        // Sanitize sensitive fields
        $sensitiveFields = ['password', 'password_confirmation', 'token', 'secret', 'api_key'];

        foreach ($sensitiveFields as $field) {
            if (isset($payload[$field])) {
                $payload[$field] = '***REDACTED***';
            }
        }

        return $payload;
    }
}

<?php

namespace Rylxes\Observability\Collectors;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Rylxes\Observability\Models\QueryLog;
use Rylxes\Observability\Jobs\StoreQueryLogJob;

class DatabaseQueryCollector
{
    protected array $queries = [];
    protected ?string $currentTraceId = null;
    protected bool $listening = false;

    /**
     * Start collecting queries for a trace.
     */
    public function start(string $traceId): void
    {
        $this->currentTraceId = $traceId;
        $this->queries = [];

        if (!$this->listening) {
            $this->startListening();
        }
    }

    /**
     * Stop collecting and return statistics.
     */
    public function stop(string $traceId): array
    {
        $queries = $this->queries[$traceId] ?? [];

        $stats = [
            'count' => count($queries),
            'time_ms' => array_sum(array_column($queries, 'time')),
        ];

        // Save queries to database
        $this->saveQueries($traceId, $queries);

        // Clean up
        unset($this->queries[$traceId]);

        return $stats;
    }

    /**
     * Start listening to database query events.
     */
    protected function startListening(): void
    {
        Event::listen(QueryExecuted::class, function (QueryExecuted $query) {
            if (!$this->currentTraceId) {
                return;
            }

            if (!config('observability.queries.enabled')) {
                return;
            }

            $this->recordQuery($query);
        });

        $this->listening = true;
    }

    /**
     * Record a single query.
     */
    protected function recordQuery(QueryExecuted $query): void
    {
        if (!$this->currentTraceId) {
            return;
        }

        // Check if we should log all queries or only slow ones
        $logAll = config('observability.queries.log_all', false);
        $slowThreshold = config('observability.queries.slow_threshold_ms', 1000);
        $isSlow = $query->time >= $slowThreshold;

        if (!$logAll && !$isSlow) {
            return;
        }

        // Check max queries limit
        if (isset($this->queries[$this->currentTraceId])) {
            $maxQueries = config('observability.queries.max_queries_per_request', 500);
            if (count($this->queries[$this->currentTraceId]) >= $maxQueries) {
                return;
            }
        }

        $this->queries[$this->currentTraceId][] = [
            'sql' => $query->sql,
            'bindings' => $query->bindings,
            'time' => $query->time,
            'connection' => $query->connectionName,
            'is_slow' => $isSlow,
        ];
    }

    /**
     * Save queries to database.
     */
    protected function saveQueries(string $traceId, array $queries): void
    {
        if (empty($queries)) {
            return;
        }

        // Detect duplicates (N+1 queries)
        $duplicates = $this->detectDuplicates($queries);

        foreach ($queries as $index => $query) {
            $queryType = QueryLog::extractQueryType($query['sql']);
            $tableName = QueryLog::extractTableName($query['sql']);

            $data = [
                'trace_id' => $traceId,
                'sql' => $query['sql'],
                'bindings' => $query['bindings'],
                'duration_ms' => round($query['time'], 2),
                'connection_name' => $query['connection'],
                'is_slow' => $query['is_slow'],
                'is_duplicate' => in_array($index, $duplicates),
                'query_type' => $queryType,
                'table_name' => $tableName,
            ];

            // Capture stack trace for slow queries
            if ($query['is_slow'] && config('observability.queries.capture_stack_trace')) {
                $data['stack_trace'] = $this->captureStackTrace();
            }

            // Save to database or queue
            if (config('observability.queue.enabled')) {
                StoreQueryLogJob::dispatch($data);
            } else {
                QueryLog::create($data);
            }
        }
    }

    /**
     * Detect duplicate queries (N+1 problem).
     */
    protected function detectDuplicates(array $queries): array
    {
        if (!config('observability.queries.detect_duplicates', true)) {
            return [];
        }

        $duplicateIndexes = [];
        $seenQueries = [];

        foreach ($queries as $index => $query) {
            // Normalize query for comparison (remove bindings)
            $normalizedSql = preg_replace('/\s+/', ' ', trim($query['sql']));

            if (isset($seenQueries[$normalizedSql])) {
                $duplicateIndexes[] = $index;
                // Also mark the first occurrence
                if (!in_array($seenQueries[$normalizedSql], $duplicateIndexes)) {
                    $duplicateIndexes[] = $seenQueries[$normalizedSql];
                }
            } else {
                $seenQueries[$normalizedSql] = $index;
            }
        }

        return $duplicateIndexes;
    }

    /**
     * Capture stack trace for debugging.
     */
    protected function captureStackTrace(): string
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);

        // Filter out framework internals
        $relevantTrace = array_filter($trace, function ($frame) {
            $file = $frame['file'] ?? '';
            return !str_contains($file, '/vendor/laravel/') &&
                   !str_contains($file, '/vendor/yourvendor/observability/');
        });

        return json_encode(array_values($relevantTrace));
    }

    /**
     * Get current trace queries (for real-time analysis).
     */
    public function getCurrentQueries(): array
    {
        if (!$this->currentTraceId) {
            return [];
        }

        return $this->queries[$this->currentTraceId] ?? [];
    }
}

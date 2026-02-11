<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Observability Enabled
    |--------------------------------------------------------------------------
    |
    | Enable or disable the entire observability system. Useful for disabling
    | in specific environments or during testing.
    |
    */
    'enabled' => env('OBSERVABILITY_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Database Connection
    |--------------------------------------------------------------------------
    |
    | Specify which database connection to use for storing observability data.
    | If null, the default application database connection will be used.
    | This makes the package database-agnostic.
    |
    */
    'database_connection' => env('OBSERVABILITY_DB_CONNECTION', null),

    /*
    |--------------------------------------------------------------------------
    | Table Prefix
    |--------------------------------------------------------------------------
    |
    | Prefix for all observability tables to avoid naming conflicts.
    |
    */
    'table_prefix' => env('OBSERVABILITY_TABLE_PREFIX', 'observability_'),

    /*
    |--------------------------------------------------------------------------
    | Request Tracing
    |--------------------------------------------------------------------------
    |
    | Configure request tracing behavior.
    |
    */
    'tracing' => [
        'enabled' => env('OBSERVABILITY_TRACING_ENABLED', true),

        // Capture request headers
        'capture_headers' => env('OBSERVABILITY_CAPTURE_HEADERS', true),

        // Capture request payload (be careful with sensitive data)
        'capture_payload' => env('OBSERVABILITY_CAPTURE_PAYLOAD', false),

        // Maximum payload size to capture (in bytes)
        'max_payload_size' => env('OBSERVABILITY_MAX_PAYLOAD_SIZE', 10000),

        // Exclude specific routes from tracing
        'excluded_routes' => [
            'observability.*',
            'horizon.*',
            'telescope.*',
            '_debugbar*',
        ],

        // Exclude specific paths (supports wildcards)
        'excluded_paths' => [
            '/health',
            '/ping',
            '/_ignition/*',
        ],

        // Sample rate (0.0 to 1.0) - 1.0 = trace all requests
        'sample_rate' => env('OBSERVABILITY_SAMPLE_RATE', 1.0),
    ],

    /*
    |--------------------------------------------------------------------------
    | Query Logging
    |--------------------------------------------------------------------------
    |
    | Configure database query logging and analysis.
    |
    */
    'queries' => [
        'enabled' => env('OBSERVABILITY_QUERIES_ENABLED', true),

        // Log all queries or only slow ones
        'log_all' => env('OBSERVABILITY_LOG_ALL_QUERIES', false),

        // Threshold for slow query detection (in milliseconds)
        'slow_threshold_ms' => env('OBSERVABILITY_SLOW_QUERY_THRESHOLD', 1000),

        // Capture stack trace for slow queries
        'capture_stack_trace' => env('OBSERVABILITY_CAPTURE_STACK_TRACE', true),

        // Detect duplicate queries (N+1 detection)
        'detect_duplicates' => env('OBSERVABILITY_DETECT_DUPLICATES', true),

        // Maximum number of queries to log per request
        'max_queries_per_request' => env('OBSERVABILITY_MAX_QUERIES_PER_REQUEST', 500),
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Monitoring
    |--------------------------------------------------------------------------
    |
    | Configure performance monitoring and thresholds.
    |
    */
    'performance' => [
        'enabled' => env('OBSERVABILITY_PERFORMANCE_ENABLED', true),

        // Thresholds for alerts
        'thresholds' => [
            'response_time_ms' => env('OBSERVABILITY_THRESHOLD_RESPONSE_TIME', 3000),
            'memory_usage_mb' => env('OBSERVABILITY_THRESHOLD_MEMORY_MB', 256),
            'query_count' => env('OBSERVABILITY_THRESHOLD_QUERY_COUNT', 50),
            'error_rate_percent' => env('OBSERVABILITY_THRESHOLD_ERROR_RATE', 5),
        ],

        // Aggregation periods for metrics
        'aggregation_periods' => ['1h', '1d', '7d', '30d'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Anomaly Detection (AI)
    |--------------------------------------------------------------------------
    |
    | Configure AI-based anomaly detection system.
    |
    */
    'anomaly_detection' => [
        'enabled' => env('OBSERVABILITY_ANOMALY_DETECTION_ENABLED', true),

        // Statistical threshold (number of standard deviations)
        'z_score_threshold' => env('OBSERVABILITY_Z_SCORE_THRESHOLD', 3.0),

        // Minimum data points required before detection
        'min_data_points' => env('OBSERVABILITY_MIN_DATA_POINTS', 100),

        // Baseline calculation window (in days)
        'baseline_window_days' => env('OBSERVABILITY_BASELINE_WINDOW_DAYS', 7),

        // Metrics to monitor for anomalies
        'monitored_metrics' => [
            'response_time',
            'memory_usage',
            'error_rate',
            'query_time',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Exporters
    |--------------------------------------------------------------------------
    |
    | Configure external observability platform integrations.
    |
    */
    'exporters' => [

        // OpenTelemetry
        'opentelemetry' => [
            'enabled' => env('OBSERVABILITY_OTEL_ENABLED', false),
            'endpoint' => env('OBSERVABILITY_OTEL_ENDPOINT', 'http://localhost:4318'),
            'protocol' => env('OBSERVABILITY_OTEL_PROTOCOL', 'http/protobuf'), // or grpc
            'headers' => [],
        ],

        // Prometheus
        'prometheus' => [
            'enabled' => env('OBSERVABILITY_PROMETHEUS_ENABLED', false),
            'namespace' => env('OBSERVABILITY_PROMETHEUS_NAMESPACE', 'laravel'),
            'storage_adapter' => env('OBSERVABILITY_PROMETHEUS_STORAGE', 'memory'), // memory, redis, apc
            'redis_prefix' => env('OBSERVABILITY_PROMETHEUS_REDIS_PREFIX', 'PROMETHEUS_'),
        ],

        // Jaeger
        'jaeger' => [
            'enabled' => env('OBSERVABILITY_JAEGER_ENABLED', false),
            'host' => env('OBSERVABILITY_JAEGER_HOST', 'localhost'),
            'port' => env('OBSERVABILITY_JAEGER_PORT', 6831),
            'service_name' => env('OBSERVABILITY_JAEGER_SERVICE_NAME', config('app.name')),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    |
    | Configure alert notification channels.
    |
    */
    'notifications' => [

        // Slack
        'slack' => [
            'enabled' => env('OBSERVABILITY_SLACK_ENABLED', false),
            'webhook_url' => env('OBSERVABILITY_SLACK_WEBHOOK_URL'),
            'channel' => env('OBSERVABILITY_SLACK_CHANNEL', '#alerts'),
            'username' => env('OBSERVABILITY_SLACK_USERNAME', 'Observability Bot'),
            'icon_emoji' => env('OBSERVABILITY_SLACK_ICON', ':chart_with_upwards_trend:'),
        ],

        // Telegram
        'telegram' => [
            'enabled' => env('OBSERVABILITY_TELEGRAM_ENABLED', false),
            'bot_token' => env('OBSERVABILITY_TELEGRAM_BOT_TOKEN'),
            'chat_id' => env('OBSERVABILITY_TELEGRAM_CHAT_ID'),
        ],

        // Email (uses Laravel's mail configuration)
        'email' => [
            'enabled' => env('OBSERVABILITY_EMAIL_ENABLED', false),
            'recipients' => env('OBSERVABILITY_EMAIL_RECIPIENTS', ''),
        ],

        // Throttling to prevent alert spam
        'throttle' => [
            'enabled' => env('OBSERVABILITY_THROTTLE_ENABLED', true),
            'window_minutes' => env('OBSERVABILITY_THROTTLE_WINDOW', 15),
            'max_alerts_per_window' => env('OBSERVABILITY_THROTTLE_MAX_ALERTS', 1),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Retention
    |--------------------------------------------------------------------------
    |
    | Configure how long to keep observability data.
    |
    */
    'retention' => [
        'traces_days' => env('OBSERVABILITY_RETENTION_TRACES_DAYS', 7),
        'queries_days' => env('OBSERVABILITY_RETENTION_QUERIES_DAYS', 7),
        'metrics_days' => env('OBSERVABILITY_RETENTION_METRICS_DAYS', 30),
        'alerts_days' => env('OBSERVABILITY_RETENTION_ALERTS_DAYS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | Process metrics and exports in the background using queues.
    |
    */
    'queue' => [
        'enabled' => env('OBSERVABILITY_QUEUE_ENABLED', false),
        'connection' => env('OBSERVABILITY_QUEUE_CONNECTION', null),
        'queue_name' => env('OBSERVABILITY_QUEUE_NAME', 'observability'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configure caching for aggregated metrics.
    |
    */
    'cache' => [
        'enabled' => env('OBSERVABILITY_CACHE_ENABLED', true),
        'driver' => env('OBSERVABILITY_CACHE_DRIVER', null), // null = use default
        'ttl_seconds' => env('OBSERVABILITY_CACHE_TTL', 300), // 5 minutes
        'prefix' => env('OBSERVABILITY_CACHE_PREFIX', 'observability:'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    |
    | Configure the built-in dashboard (requires Filament).
    |
    */
    'dashboard' => [
        'enabled' => env('OBSERVABILITY_DASHBOARD_ENABLED', true),
        'route_prefix' => env('OBSERVABILITY_DASHBOARD_PREFIX', 'observability'),
        'middleware' => ['web', 'auth'],
    ],

];

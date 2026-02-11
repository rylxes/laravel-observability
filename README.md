# Laravel Observability Plugin

🔍 **Production-Grade Observability & APM for Laravel Applications**

A lightweight, database-agnostic alternative to full APM tools, optimized specifically for Laravel apps. Features request tracing, performance profiling, slow query detection, OpenTelemetry/Prometheus integration, AI-based anomaly detection, and smart alerting.

[![Latest Version](https://img.shields.io/badge/version-1.0.0-blue.svg)](https://github.com/rylxes/laravel-observability)
[![PHP Version](https://img.shields.io/badge/php-%5E8.1%7C%5E8.2%7C%5E8.3-blue.svg)](https://php.net)
[![Laravel Version](https://img.shields.io/badge/laravel-%5E10.0%7C%5E11.0-red.svg)](https://laravel.com)

---

## ✨ Features

### Core Capabilities
- 📊 **Request Tracing** - Capture HTTP requests with route, controller, duration, memory usage, headers
- 🗄️ **Database Query Monitoring** - Track all queries, detect N+1 problems, identify slow queries
- ⚡ **Performance Profiling** - Per-route metrics, P50/P95/P99 latency, bottleneck identification
- 🐌 **Slow Query Detector** - Automatic detection with stack traces and optimization recommendations
- 🤖 **AI Anomaly Detection** - Statistical analysis (Z-score) to detect unusual patterns
- 📈 **OpenTelemetry Export** - OTLP protocol support for distributed tracing
- 📊 **Prometheus Metrics** - `/metrics` endpoint for Prometheus scraping
- 🔔 **Smart Alerting** - Slack/Telegram notifications with throttling and deduplication
- 🎨 **Dashboard Ready** - API endpoints for building custom dashboards (Filament support planned)
- 🗃️ **Database Agnostic** - Works with MySQL, PostgreSQL, SQLite, SQL Server

---

## 📦 Installation

### 1. Install via Composer
```bash
composer require rylxes/laravel-observability
```

### 2. Run Installation Command
```bash
php artisan observability:install
```

This will:
- Publish configuration file to `config/observability.php`
- Run migrations (creates observability tables)
- Display middleware setup instructions

### 3. Register Middleware
Add to `app/Http/Kernel.php` (Laravel 10) or `bootstrap/app.php` (Laravel 11):

**Laravel 10:**
```php
protected $middleware = [
    // ... other middleware
    \Rylxes\Observability\Middleware\RequestTracingMiddleware::class,
];
```

**Laravel 11:**
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->append(\Rylxes\Observability\Middleware\RequestTracingMiddleware::class);
})
```

### 4. Configure Environment
Add to `.env`:
```env
OBSERVABILITY_ENABLED=true
OBSERVABILITY_TRACING_ENABLED=true
OBSERVABILITY_SLOW_QUERY_THRESHOLD=1000

# Optional: Slack Notifications
OBSERVABILITY_SLACK_ENABLED=true
OBSERVABILITY_SLACK_WEBHOOK_URL=https://hooks.slack.com/services/YOUR/WEBHOOK/URL

# Optional: Telegram Notifications
OBSERVABILITY_TELEGRAM_ENABLED=true
OBSERVABILITY_TELEGRAM_BOT_TOKEN=your_bot_token
OBSERVABILITY_TELEGRAM_CHAT_ID=your_chat_id

# Optional: Prometheus
OBSERVABILITY_PROMETHEUS_ENABLED=true
```

---

## 🚀 Usage

### Using the Facade
```php
use Rylxes\Observability\Facades\Observability;

// Get performance analysis
$analysis = Observability::analyze(days: 7);

// Detect slow queries
$slowQueries = Observability::slowQueries()->analyze();

// Detect anomalies
$anomalies = Observability::anomalies()->detectAnomalies('response_time');

// Export Prometheus metrics
$metrics = Observability::exportMetrics();
```

### Artisan Commands

**Analyze Performance:**
```bash
php artisan observability:analyze --days=7 --notify
```

**Prune Old Data:**
```bash
php artisan observability:prune --force
```

### API Endpoints

All endpoints are prefixed with `/api/observability`:

| Endpoint | Description |
|----------|-------------|
| `GET /metrics` | Prometheus metrics (text format) |
| `GET /dashboard` | Performance dashboard data (JSON) |
| `GET /traces` | Recent request traces |
| `GET /traces/{traceId}` | Single trace detail |
| `GET /alerts` | Recent alerts |
| `POST /alerts/{id}/resolve` | Resolve an alert |
| `GET /health` | Health check |

**Example:**
```bash
curl http://yourapp.test/api/observability/metrics
curl http://yourapp.test/api/observability/dashboard | jq
```

---

## ⚙️ Configuration

### Database Connection
By default, uses your app's default database connection. To use a separate database:

```env
OBSERVABILITY_DB_CONNECTION=observability_db
```

Then configure the connection in `config/database.php`.

### Request Tracing
```php
'tracing' => [
    'enabled' => true,
    'capture_headers' => true,
    'capture_payload' => false, // Be careful with sensitive data
    'excluded_routes' => ['telescope.*', 'horizon.*'],
    'sample_rate' => 1.0, // 0.0 to 1.0 (1.0 = trace all)
],
```

### Slow Query Detection
```php
'queries' => [
    'enabled' => true,
    'log_all' => false, // Only log slow queries
    'slow_threshold_ms' => 1000,
    'detect_duplicates' => true, // N+1 detection
],
```

### Anomaly Detection (AI)
```php
'anomaly_detection' => [
    'enabled' => true,
    'z_score_threshold' => 3.0, // Standard deviations
    'min_data_points' => 100,
    'baseline_window_days' => 7,
],
```

### Notifications
```php
'notifications' => [
    'slack' => ['enabled' => true, 'webhook_url' => env('...')],
    'telegram' => ['enabled' => true, 'bot_token' => env('...')],
    'throttle' => [
        'enabled' => true,
        'window_minutes' => 15,
        'max_alerts_per_window' => 1,
    ],
],
```

### Data Retention
```php
'retention' => [
    'traces_days' => 7,
    'queries_days' => 7,
    'metrics_days' => 30,
    'alerts_days' => 30,
],
```

---

## 📊 Integrations

### Prometheus
Enable in `.env`:
```env
OBSERVABILITY_PROMETHEUS_ENABLED=true
OBSERVABILITY_PROMETHEUS_STORAGE=redis # or 'memory', 'apc'
```

Configure Prometheus to scrape:
```yaml
scrape_configs:
  - job_name: 'laravel-app'
    static_configs:
      - targets: ['yourapp.test']
    metrics_path: '/api/observability/metrics'
```

### OpenTelemetry
```env
OBSERVABILITY_OTEL_ENABLED=true
OBSERVABILITY_OTEL_ENDPOINT=http://localhost:4318
```

### Grafana Dashboard
Import the included Grafana dashboard template:
```bash
# Coming soon - dashboard JSON in /docs folder
```

---

## 🤖 AI Anomaly Detection

The plugin uses statistical analysis (Z-score method) to detect anomalies:

1. **Baseline Calculation**: Analyzes last 7 days (configurable)
2. **Statistical Analysis**: Calculates mean and standard deviation
3. **Anomaly Detection**: Flags values > 3σ from baseline
4. **Auto-Alerting**: Creates alerts for critical anomalies

**Monitored Metrics:**
- Response time
- Memory usage
- Error rate
- Query execution time

**Example:**
```php
$result = Observability::anomalies()->detectAnomalies('response_time');

if ($result['status'] === 'success') {
    foreach ($result['anomalies'] as $anomaly) {
        echo "Anomaly: {$anomaly['metric_name']} - ";
        echo "Value: {$anomaly['value']} (Baseline: {$anomaly['baseline']})";
        echo "Deviation: {$anomaly['deviation_percent']}%";
    }
}
```

---

## 📈 Performance Insights

### Dashboard Data Structure
```json
{
  "overall_metrics": {
    "total_requests": 12500,
    "avg_response_time_ms": 145.3,
    "p95_response_time_ms": 450,
    "p99_response_time_ms": 890,
    "avg_memory_mb": 28.5,
    "error_rate": 1.2
  },
  "route_performance": [
    {
      "route": "api.users.index",
      "requests": 3400,
      "avg_duration_ms": 89,
      "error_rate": 0.5
    }
  ],
  "bottlenecks": [
    {
      "type": "slow_routes",
      "severity": "warning",
      "data": ["api.reports.generate"]
    }
  ]
}
```

---

## 🔒 Security Considerations

- **Sensitive Data**: Disable `capture_payload` and `capture_headers` in production
- **Authentication**: All API endpoints require authentication by default
- **Data Sanitization**: Passwords, tokens automatically redacted
- **Rate Limiting**: Consider adding rate limits to metrics endpoints

---

## 🧪 Testing

Run tests:
```bash
composer test
```

With coverage:
```bash
composer test-coverage
```

---

## 📊 Database Schema

The plugin creates 4 tables (with configurable prefix):

| Table | Purpose |
|-------|---------|
| `observability_traces` | HTTP request traces |
| `observability_queries` | Database query logs |
| `observability_metrics` | Aggregated performance metrics |
| `observability_alerts` | Generated alerts |

**All tables use your app's database connection** (MySQL, PostgreSQL, SQLite, SQL Server).

---

## 💰 Monetization (SaaS Model)

### Free Tier (Open Source)
- Core tracing & monitoring
- Local dashboard
- 7-day data retention

### Pro Tier ($29-99/mo)
- Cloud storage (unlimited retention)
- Advanced AI anomaly detection
- Multi-project support
- Email reports

### Enterprise ($499+/mo)
- On-premise deployment
- Custom integrations
- SLA monitoring
- Dedicated support

---

## 🤝 Contributing

Contributions welcome! Please submit PRs to the `develop` branch.

1. Fork the repository
2. Create feature branch (`git checkout -b feature/amazing-feature`)
3. Commit changes (`git commit -m 'Add amazing feature'`)
4. Push to branch (`git push origin feature/amazing-feature`)
5. Open Pull Request

---

## 📝 License

MIT License. See [LICENSE](LICENSE) file.

---

## 🙏 Credits

Built with ❤️ for the Laravel community.

- OpenTelemetry PHP: https://github.com/open-telemetry/opentelemetry-php
- Prometheus PHP: https://github.com/PromPHP/prometheus_client_php

---

## 📞 Support

- **Issues**: [GitHub Issues](https://github.com/rylxes/laravel-observability/issues)
- **Discussions**: [GitHub Discussions](https://github.com/rylxes/laravel-observability/discussions)
- **Email**: support@rylxes

---

## 🗺️ Roadmap

- [ ] Filament dashboard widgets
- [ ] Jaeger exporter implementation
- [ ] Grafana dashboard templates
- [ ] Real-time WebSocket updates
- [ ] Custom metric tracking API
- [ ] Multi-tenancy support
- [ ] Cloud SaaS platform

---

**⭐ If you find this useful, please star the repository!**

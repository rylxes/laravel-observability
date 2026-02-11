# Changelog

## [1.0.1] - 2026-02-11nn### Addedn- re-arranged project filesnn

All notable changes to `laravel-observability` will be documented in this file.

## [1.0.0] - 2024-01-01

### Added
- Initial release
- Request tracing middleware with configurable sampling
- Database query collector with N+1 detection
- Slow query detector with optimization recommendations
- Performance analyzer with P50/P95/P99 metrics
- AI-based anomaly detection using Z-score analysis
- Prometheus metrics exporter
- Slack notification support
- Telegram notification support
- Database-agnostic design (MySQL, PostgreSQL, SQLite, SQL Server)
- Artisan commands for installation, analysis, and pruning
- RESTful API endpoints for metrics and dashboard data
- Configurable data retention policies
- Alert throttling and deduplication
- Comprehensive documentation

### Features
- ✅ Request tracing with trace IDs
- ✅ Query logging and analysis
- ✅ Performance profiling per route
- ✅ Anomaly detection
- ✅ Multi-platform exporters (Prometheus, OpenTelemetry ready)
- ✅ Smart alerting (Slack, Telegram)
- ✅ Database agnostic storage
- ✅ Configurable sampling and exclusions

### Coming Soon
- Filament dashboard widgets
- Jaeger exporter
- Grafana dashboard templates
- Real-time WebSocket updates
- SaaS cloud platform

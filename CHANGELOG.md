# Changelog

## [1.1.3] - 2026-02-16nn### Addedn- different authentication methods acceptednn

## [1.1.1] - 2026-02-16nn### Addedn- added compactibility to more laravel versionsnn

## [1.1.0] - 2026-02-12nn### Changedn- Queue-based background processing (StoreTraceJob, StoreQueryLogJob), Real-time WebSocket broadcasting events, Filament dashboard with 5 widgets and 3 resources, Comprehensive unit tests (153 tests)nn### Fixedn- Wired queue dispatch and broadcast events into existing collectors and analyzersnn

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

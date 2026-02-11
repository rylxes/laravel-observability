<?php

namespace Rylxes\Observability\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RequestTrace extends Model
{
    protected $guarded = [];

    protected $casts = [
        'metadata' => 'array',
        'headers' => 'array',
        'request_payload' => 'array',
        'duration_ms' => 'integer',
        'memory_usage' => 'integer',
        'query_count' => 'integer',
        'query_time_ms' => 'integer',
        'status_code' => 'integer',
    ];

    /**
     * Get the table associated with the model.
     */
    public function getTable(): string
    {
        return config('observability.table_prefix', 'observability_') . 'traces';
    }

    /**
     * Get the current connection name for the model.
     * This makes the model database-agnostic by using the app's configured connection.
     */
    public function getConnectionName(): ?string
    {
        return config('observability.database_connection') ?? config('database.default');
    }

    /**
     * Get all queries associated with this trace.
     */
    public function queries(): HasMany
    {
        return $this->hasMany(QueryLog::class, 'trace_id', 'trace_id');
    }

    /**
     * Get slow queries for this trace.
     */
    public function slowQueries(): HasMany
    {
        return $this->queries()->where('is_slow', true);
    }

    /**
     * Get child traces (for distributed tracing).
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_trace_id', 'trace_id');
    }

    /**
     * Scope to filter by route name.
     */
    public function scopeRoute($query, string $routeName)
    {
        return $query->where('route_name', $routeName);
    }

    /**
     * Scope to filter slow requests.
     */
    public function scopeSlow($query, int $thresholdMs = 1000)
    {
        return $query->where('duration_ms', '>=', $thresholdMs);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope to filter by status code.
     */
    public function scopeStatus($query, int $statusCode)
    {
        return $query->where('status_code', $statusCode);
    }

    /**
     * Scope to filter errors (4xx and 5xx).
     */
    public function scopeErrors($query)
    {
        return $query->where('status_code', '>=', 400);
    }
}

<?php

namespace Rylxes\Observability\Models;

use Illuminate\Database\Eloquent\Model;

class PerformanceMetric extends Model
{
    protected $guarded = [];

    protected $casts = [
        'value' => 'float',
        'baseline' => 'float',
        'z_score' => 'float',
        'is_anomaly' => 'boolean',
        'period_start' => 'datetime',
        'period_end' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the table associated with the model.
     */
    public function getTable(): string
    {
        return config('observability.table_prefix', 'observability_') . 'metrics';
    }

    /**
     * Get the current connection name for the model.
     */
    public function getConnectionName(): ?string
    {
        return config('observability.database_connection') ?? config('database.default');
    }

    /**
     * Scope to filter by metric type.
     */
    public function scopeType($query, string $type)
    {
        return $query->where('metric_type', $type);
    }

    /**
     * Scope to filter anomalies.
     */
    public function scopeAnomalies($query)
    {
        return $query->where('is_anomaly', true);
    }

    /**
     * Scope to filter by aggregation period.
     */
    public function scopePeriod($query, string $period)
    {
        return $query->where('aggregation_period', $period);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope to filter by metric name.
     */
    public function scopeName($query, string $name)
    {
        return $query->where('metric_name', $name);
    }
}

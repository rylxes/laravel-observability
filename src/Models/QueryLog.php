<?php

namespace Rylxes\Observability\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QueryLog extends Model
{
    protected $guarded = [];

    protected $casts = [
        'bindings' => 'array',
        'duration_ms' => 'integer',
        'is_slow' => 'boolean',
        'is_duplicate' => 'boolean',
    ];

    /**
     * Get the table associated with the model.
     */
    public function getTable(): string
    {
        return config('observability.table_prefix', 'observability_') . 'queries';
    }

    /**
     * Get the current connection name for the model.
     */
    public function getConnectionName(): ?string
    {
        return config('observability.database_connection') ?? config('database.default');
    }

    /**
     * Get the trace this query belongs to.
     */
    public function trace(): BelongsTo
    {
        return $this->belongsTo(RequestTrace::class, 'trace_id', 'trace_id');
    }

    /**
     * Scope to filter slow queries.
     */
    public function scopeSlow($query, int $thresholdMs = 1000)
    {
        return $query->where('is_slow', true)
            ->orWhere('duration_ms', '>=', $thresholdMs);
    }

    /**
     * Scope to filter duplicate queries.
     */
    public function scopeDuplicates($query)
    {
        return $query->where('is_duplicate', true);
    }

    /**
     * Scope to filter by query type.
     */
    public function scopeType($query, string $type)
    {
        return $query->where('query_type', strtoupper($type));
    }

    /**
     * Scope to filter by table name.
     */
    public function scopeTable($query, string $tableName)
    {
        return $query->where('table_name', $tableName);
    }

    /**
     * Get the query type from SQL.
     */
    public static function extractQueryType(string $sql): string
    {
        $sql = trim(strtoupper($sql));

        if (str_starts_with($sql, 'SELECT')) return 'SELECT';
        if (str_starts_with($sql, 'INSERT')) return 'INSERT';
        if (str_starts_with($sql, 'UPDATE')) return 'UPDATE';
        if (str_starts_with($sql, 'DELETE')) return 'DELETE';
        if (str_starts_with($sql, 'CREATE')) return 'CREATE';
        if (str_starts_with($sql, 'ALTER')) return 'ALTER';
        if (str_starts_with($sql, 'DROP')) return 'DROP';

        return 'OTHER';
    }

    /**
     * Extract table name from SQL (basic extraction).
     */
    public static function extractTableName(string $sql): ?string
    {
        // Simple regex to extract table name - can be enhanced
        if (preg_match('/(?:FROM|INTO|UPDATE|TABLE)\s+[`"]?(\w+)[`"]?/i', $sql, $matches)) {
            return $matches[1];
        }

        return null;
    }
}

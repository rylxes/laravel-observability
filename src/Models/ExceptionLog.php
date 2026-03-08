<?php

namespace Rylxes\Observability\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExceptionLog extends Model
{
    protected $guarded = [];

    protected $casts = [
        'context' => 'array',
        'metadata' => 'array',
        'resolved' => 'boolean',
        'occurrence_count' => 'integer',
        'line' => 'integer',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    /**
     * Get the table associated with the model.
     */
    public function getTable(): string
    {
        return config('observability.table_prefix', 'observability_') . 'exceptions';
    }

    /**
     * Get the current connection name for the model.
     */
    public function getConnectionName(): ?string
    {
        return config('observability.database_connection') ?? config('database.default');
    }

    /**
     * Get the trace this exception belongs to.
     */
    public function trace(): BelongsTo
    {
        return $this->belongsTo(RequestTrace::class, 'trace_id', 'trace_id');
    }

    /**
     * Generate a group hash for exception deduplication.
     */
    public static function generateGroupHash(string $class, string $file, int $line): string
    {
        return md5($class . '|' . $file . '|' . $line);
    }

    /**
     * Scope to filter unresolved exceptions.
     */
    public function scopeUnresolved($query)
    {
        return $query->where('resolved', false);
    }

    /**
     * Scope to filter by exception class.
     */
    public function scopeByClass($query, string $class)
    {
        return $query->where('exception_class', $class);
    }

    /**
     * Scope to get grouped exceptions (latest per group_hash).
     */
    public function scopeGrouped($query)
    {
        return $query->select('*')
            ->whereIn('id', function ($sub) {
                $sub->selectRaw('MAX(id)')
                    ->from($this->getTable())
                    ->groupBy('group_hash');
            });
    }

    /**
     * Scope to filter recent exceptions.
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    /**
     * Scope to filter by severity.
     */
    public function scopeSeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Mark exception group as resolved.
     */
    public function markResolved(): bool
    {
        return $this->update([
            'resolved' => true,
            'resolved_at' => now(),
        ]);
    }
}

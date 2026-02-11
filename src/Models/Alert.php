<?php

namespace Rylxes\Observability\Models;

use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    protected $guarded = [];

    protected $casts = [
        'context' => 'array',
        'notified' => 'boolean',
        'resolved' => 'boolean',
        'notified_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    /**
     * Get the table associated with the model.
     */
    public function getTable(): string
    {
        return config('observability.table_prefix', 'observability_') . 'alerts';
    }

    /**
     * Get the current connection name for the model.
     */
    public function getConnectionName(): ?string
    {
        return config('observability.database_connection') ?? config('database.default');
    }

    /**
     * Scope to filter by alert type.
     */
    public function scopeType($query, string $type)
    {
        return $query->where('alert_type', $type);
    }

    /**
     * Scope to filter by severity.
     */
    public function scopeSeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope to filter unresolved alerts.
     */
    public function scopeUnresolved($query)
    {
        return $query->where('resolved', false);
    }

    /**
     * Scope to filter notified alerts.
     */
    public function scopeNotified($query)
    {
        return $query->where('notified', true);
    }

    /**
     * Scope to filter pending notifications.
     */
    public function scopePendingNotification($query)
    {
        return $query->where('notified', false);
    }

    /**
     * Mark alert as resolved.
     */
    public function markResolved(): bool
    {
        return $this->update([
            'resolved' => true,
            'resolved_at' => now(),
        ]);
    }

    /**
     * Mark alert as notified.
     */
    public function markNotified(array $channels = []): bool
    {
        return $this->update([
            'notified' => true,
            'notified_at' => now(),
            'notification_channels' => implode(',', $channels),
        ]);
    }
}

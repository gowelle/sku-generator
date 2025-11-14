<?php

namespace Gowelle\SkuGenerator\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * SKU History Model
 *
 * Tracks all SKU lifecycle events including creation, regeneration, modification, and deletion.
 *
 * @property int $id
 * @property string|null $old_sku
 * @property string|null $new_sku
 * @property string $model_type
 * @property int $model_id
 * @property string $event_type
 * @property int|null $user_id
 * @property string|null $user_type
 * @property array|null $metadata
 * @property string|null $reason
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class SkuHistory extends Model
{
    /**
     * Event types
     */
    public const EVENT_CREATED = 'created';
    public const EVENT_REGENERATED = 'regenerated';
    public const EVENT_MODIFIED = 'modified';
    public const EVENT_DELETED = 'deleted';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'old_sku',
        'new_sku',
        'model_type',
        'model_id',
        'event_type',
        'user_id',
        'user_type',
        'metadata',
        'reason',
        'ip_address',
        'user_agent',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Get the table associated with the model.
     *
     * @return string
     */
    public function getTable()
    {
        return config('sku-generator.history.table_name', 'sku_histories');
    }

    /**
     * Get the model that owns the SKU history.
     */
    public function model(): MorphTo
    {
        return $this->morphTo('model');
    }

    /**
     * Get the user that performed the action (polymorphic).
     */
    public function user(): MorphTo
    {
        return $this->morphTo('user');
    }

    /**
     * Scope to get history for a specific model instance.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param Model $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForModel($query, Model $model)
    {
        return $query->where('model_type', get_class($model))
            ->where('model_id', $model->getKey());
    }

    /**
     * Scope to get history for a specific SKU value.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $sku
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForSku($query, string $sku)
    {
        return $query->where(function ($q) use ($sku) {
            $q->where('old_sku', $sku)
                ->orWhere('new_sku', $sku);
        });
    }

    /**
     * Scope to filter by event type.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $eventType
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByEventType($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    /**
     * Scope to filter by user.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $userId
     * @param string|null $userType
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByUser($query, int $userId, ?string $userType = null)
    {
        $query->where('user_id', $userId);

        if ($userType) {
            $query->where('user_type', $userType);
        }

        return $query;
    }

    /**
     * Scope to get recent changes within a number of days.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $days
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecentChanges($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope to filter by date range.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|\DateTimeInterface $startDate
     * @param string|\DateTimeInterface $endDate
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [
            Carbon::parse($startDate),
            Carbon::parse($endDate),
        ]);
    }

    /**
     * Scope to get changes before a specific date.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|\DateTimeInterface $date
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBeforeDate($query, $date)
    {
        return $query->where('created_at', '<', Carbon::parse($date));
    }

    /**
     * Scope to order by most recent first.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc')->orderBy('id', 'desc');
    }

    /**
     * Scope to order by oldest first.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOldest($query)
    {
        return $query->orderBy('created_at', 'asc');
    }

    /**
     * Get formatted event type for display.
     *
     * @return string
     */
    public function getFormattedEventTypeAttribute(): string
    {
        return ucfirst($this->event_type);
    }

    /**
     * Get the SKU change summary.
     *
     * @return string
     */
    public function getChangeSummaryAttribute(): string
    {
        return match ($this->event_type) {
            self::EVENT_CREATED => "Created: {$this->new_sku}",
            self::EVENT_REGENERATED => "Regenerated: {$this->old_sku} → {$this->new_sku}",
            self::EVENT_MODIFIED => "Modified: {$this->old_sku} → {$this->new_sku}",
            self::EVENT_DELETED => "Deleted: {$this->old_sku}",
            default => "Unknown event",
        };
    }

    /**
     * Check if history entry represents a creation event.
     *
     * @return bool
     */
    public function isCreation(): bool
    {
        return $this->event_type === self::EVENT_CREATED;
    }

    /**
     * Check if history entry represents a regeneration event.
     *
     * @return bool
     */
    public function isRegeneration(): bool
    {
        return $this->event_type === self::EVENT_REGENERATED;
    }

    /**
     * Check if history entry represents a modification event.
     *
     * @return bool
     */
    public function isModification(): bool
    {
        return $this->event_type === self::EVENT_MODIFIED;
    }

    /**
     * Check if history entry represents a deletion event.
     *
     * @return bool
     */
    public function isDeletion(): bool
    {
        return $this->event_type === self::EVENT_DELETED;
    }
}

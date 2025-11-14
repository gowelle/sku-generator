<?php

namespace Gowelle\SkuGenerator\Services;

use Gowelle\SkuGenerator\Models\SkuHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Service class for logging SKU history events.
 *
 * Handles the creation of history records with optional user tracking,
 * IP address logging, and metadata collection.
 */
class SkuHistoryLogger
{
    /**
     * Log a SKU creation event.
     *
     * @param Model $model The model for which the SKU was created
     * @param string $sku The generated SKU
     * @param string|null $reason Optional reason for creation
     * @param array $metadata Additional metadata
     * @return SkuHistory|null The created history record, or null if logging is disabled
     */
    public function logCreation(
        Model $model,
        string $sku,
        ?string $reason = null,
        array $metadata = []
    ): ?SkuHistory {
        return $this->createHistoryRecord([
            'model_type' => get_class($model),
            'model_id' => $model->getKey(),
            'old_sku' => null,
            'new_sku' => $sku,
            'event_type' => SkuHistory::EVENT_CREATED,
            'reason' => $reason,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Log a SKU regeneration event.
     *
     * @param Model $model The model for which the SKU was regenerated
     * @param string $oldSku The previous SKU
     * @param string $newSku The new SKU
     * @param string|null $reason Optional reason for regeneration
     * @param array $metadata Additional metadata
     * @return SkuHistory|null The created history record, or null if logging is disabled
     */
    public function logRegeneration(
        Model $model,
        string $oldSku,
        string $newSku,
        ?string $reason = null,
        array $metadata = []
    ): ?SkuHistory {
        return $this->createHistoryRecord([
            'model_type' => get_class($model),
            'model_id' => $model->getKey(),
            'old_sku' => $oldSku,
            'new_sku' => $newSku,
            'event_type' => SkuHistory::EVENT_REGENERATED,
            'reason' => $reason,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Log a SKU modification event.
     *
     * @param Model $model The model for which the SKU was modified
     * @param string $oldSku The previous SKU
     * @param string $newSku The new SKU
     * @param string|null $reason Optional reason for modification
     * @param array $metadata Additional metadata
     * @return SkuHistory|null The created history record, or null if logging is disabled
     */
    public function logModification(
        Model $model,
        string $oldSku,
        string $newSku,
        ?string $reason = null,
        array $metadata = []
    ): ?SkuHistory {
        return $this->createHistoryRecord([
            'model_type' => get_class($model),
            'model_id' => $model->getKey(),
            'old_sku' => $oldSku,
            'new_sku' => $newSku,
            'event_type' => SkuHistory::EVENT_MODIFIED,
            'reason' => $reason,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Log a SKU deletion event.
     *
     * @param Model $model The model that was deleted
     * @param string $sku The SKU of the deleted model
     * @param string|null $reason Optional reason for deletion
     * @param array $metadata Additional metadata
     * @return SkuHistory|null The created history record, or null if logging is disabled
     */
    public function logDeletion(
        Model $model,
        string $sku,
        ?string $reason = null,
        array $metadata = []
    ): ?SkuHistory {
        return $this->createHistoryRecord([
            'model_type' => get_class($model),
            'model_id' => $model->getKey(),
            'old_sku' => $sku,
            'new_sku' => null,
            'event_type' => SkuHistory::EVENT_DELETED,
            'reason' => $reason,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Create a history record with optional user and request tracking.
     *
     * @param array $data The history data
     * @return SkuHistory|null The created history record, or null if logging is disabled
     */
    protected function createHistoryRecord(array $data): ?SkuHistory
    {
        // Check if history logging is enabled
        if (!config('sku-generator.history.enabled', true)) {
            return null;
        }

        // Add user tracking if enabled
        if (config('sku-generator.history.track_user', true) && Auth::check()) {
            $data['user_id'] = Auth::id();
            $data['user_type'] = get_class(Auth::user());
        }

        // Add IP address tracking if enabled
        if (config('sku-generator.history.track_ip', false) && request()) {
            $data['ip_address'] = request()->ip();
        }

        // Add user agent tracking if enabled
        if (config('sku-generator.history.track_user_agent', false) && request()) {
            $data['user_agent'] = request()->userAgent();
        }

        // Create the history record
        return SkuHistory::create($data);
    }

    /**
     * Check if history logging is enabled.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return config('sku-generator.history.enabled', true);
    }

    /**
     * Get history for a specific model.
     *
     * @param Model $model
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getHistory(Model $model)
    {
        return SkuHistory::forModel($model)
            ->latest()
            ->get();
    }

    /**
     * Get the most recent history entry for a model.
     *
     * @param Model $model
     * @return SkuHistory|null
     */
    public function getLatestHistory(Model $model): ?SkuHistory
    {
        return SkuHistory::forModel($model)
            ->latest()
            ->first();
    }

    /**
     * Cleanup old history records based on retention policy.
     *
     * @return int Number of records deleted
     */
    public function cleanup(): int
    {
        $retentionDays = config('sku-generator.history.retention_days');

        if ($retentionDays === null) {
            return 0;
        }

        return SkuHistory::where('created_at', '<', now()->subDays($retentionDays))
            ->delete();
    }
}

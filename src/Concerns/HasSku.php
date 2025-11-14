<?php

namespace Gowelle\SkuGenerator\Concerns;

use Gowelle\SkuGenerator\Events\SkuCreated;
use Gowelle\SkuGenerator\Events\SkuDeleted;
use Gowelle\SkuGenerator\Events\SkuModified;
use Gowelle\SkuGenerator\Events\SkuRegenerated;
use Gowelle\SkuGenerator\Models\SkuHistory;
use Gowelle\SkuGenerator\Services\SkuHistoryLogger;
use Gowelle\SkuGenerator\SkuGenerator;

/**
 * Provides SKU generation functionality for Laravel models.
 *
 * This trait adds automatic SKU generation on model creation and
 * prevents SKU modifications after creation unless explicitly forced.
 */
trait HasSku
{
    /** @var bool Flag to allow SKU regeneration */
    protected $forceSkuRegeneration = false;

    /**
     * Boot the trait.
     *
     * Registers model event listeners for SKU generation and protection.
     */
    protected static function bootHasSku()
    {
        static::creating(function ($model) {
            if (empty($model->sku)) {
                $model->sku = SkuGenerator::generate($model);
            }
        });

        static::created(function ($model) {
            if (!empty($model->sku)) {
                event(new SkuCreated($model, $model->sku));
                app(SkuHistoryLogger::class)->logCreation($model, $model->sku);
            }
        });

        static::updating(function ($model) {
            if ($model->isDirty('sku')) {
                if (!$model->forceSkuRegeneration) {
                    // Prevent SKU modification unless forced
                    $model->sku = $model->getOriginal('sku');
                } else {
                    // Track manual modification
                    $oldSku = $model->getOriginal('sku');
                    $newSku = $model->sku;

                    if ($oldSku !== $newSku) {
                        event(new SkuModified($model, $oldSku, $newSku));
                    }
                }
            }
        });

        static::updated(function ($model) {
            if ($model->wasChanged('sku') && $model->forceSkuRegeneration) {
                $oldSku = $model->getOriginal('sku');
                $newSku = $model->sku;

                if ($oldSku !== $newSku) {
                    app(SkuHistoryLogger::class)->logModification($model, $oldSku, $newSku);
                }
            }
        });

        static::deleting(function ($model) {
            if (!empty($model->sku)) {
                event(new SkuDeleted($model, $model->sku));
                app(SkuHistoryLogger::class)->logDeletion($model, $model->sku);
            }
        });
    }

    /**
     * Generate a new SKU for this model.
     *
     * @return string The generated SKU
     */
    public function generateSku(): string
    {
        return SkuGenerator::generate($this);
    }

    /**
     * Force regenerate and save a new SKU for this model.
     *
     * This method temporarily allows SKU modification and generates
     * a new SKU even if one already exists.
     *
     * @param string|null $reason Optional reason for regeneration
     * @return bool Whether the save operation was successful
     */
    public function forceRegenerateSku(?string $reason = null): bool
    {
        $oldSku = $this->sku;
        $this->forceSkuRegeneration = true;
        $this->sku = $this->generateSku();
        $saved = $this->save();
        $this->forceSkuRegeneration = false;

        if ($saved && $oldSku !== $this->sku) {
            event(new SkuRegenerated($this, $oldSku, $this->sku, $reason));
            app(SkuHistoryLogger::class)->logRegeneration($this, $oldSku, $this->sku, $reason);
        }

        return $saved;
    }

    /**
     * Get the SKU history for this model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function skuHistory()
    {
        return $this->morphMany(SkuHistory::class, 'model');
    }

    /**
     * Get all SKU history entries for this model.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getSkuHistory()
    {
        return $this->skuHistory()->latest()->get();
    }

    /**
     * Get the most recent SKU history entry.
     *
     * @return SkuHistory|null
     */
    public function getLatestSkuHistory(): ?SkuHistory
    {
        return $this->skuHistory()->latest()->first();
    }
}

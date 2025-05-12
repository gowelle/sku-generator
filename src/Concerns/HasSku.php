<?php

namespace Gowelle\SkuGenerator\Concerns;

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

        static::updating(function ($model) {
            if ($model->isDirty('sku') && !$model->forceSkuRegeneration) {
                $model->sku = $model->getOriginal('sku');
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
     * @return bool Whether the save operation was successful
     */
    public function forceRegenerateSku(): bool
    {
        $this->forceSkuRegeneration = true;
        $this->sku = $this->generateSku();
        $saved = $this->save();
        $this->forceSkuRegeneration = false;
        
        return $saved;
    }
}

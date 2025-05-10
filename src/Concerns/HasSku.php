<?php

namespace Gowelle\SkuGenerator\Concerns;

use Gowelle\SkuGenerator\SkuGenerator;

trait HasSku
{
    protected static function bootHasSku()
    {
        static::creating(function ($model) {
            if (empty($model->sku)) {
                $model->sku = SkuGenerator::generate($model);
            }
        });

        static::updating(function ($model) {
            if ($model->isDirty('sku')) {
                $model->sku = $model->getOriginal('sku');
            }
        });
    }

    public function generateSku()
    {
        return SkuGenerator::generate($this);
    }
}

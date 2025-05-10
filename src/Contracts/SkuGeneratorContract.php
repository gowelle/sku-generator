<?php

namespace Gowelle\SkuGenerator\Contracts;

use Illuminate\Database\Eloquent\Model;

interface SkuGeneratorContract
{
    /**
     * Generate SKU for the given model.
     *
     * @return string
     *
     * @throws \Exception
     */
    public static function generate(Model $model);

    /**
     * Generate SKU for a product.
     *
     * @return string
     */
    public static function generateProductSku(Model $product);

    /**
     * Generate SKU for a variant.
     *
     * @return string
     */
    public static function generateVariantSku(Model $variant);
}

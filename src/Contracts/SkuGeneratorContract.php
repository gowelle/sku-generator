<?php

namespace Gowelle\SkuGenerator\Contracts;

use Illuminate\Database\Eloquent\Model;

interface SkuGeneratorContract
{
    /**
     * Generate SKU for the given model.
     *
     * @param Model $model
     * @return string
     * @throws \Exception
     */
    public static function generate(Model $model);

    /**
     * Generate SKU for a product.
     *
     * @param Model $product
     * @return string
     */
    public static function generateProductSku(Model $product);

    /**
     * Generate SKU for a variant.
     *
     * @param Model $variant
     * @return string
     */
    public static function generateVariantSku(Model $variant);
}
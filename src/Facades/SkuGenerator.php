<?php

namespace Gowelle\SkuGenerator\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * SKU Generator Facade
 *
 * Provides a static interface to the SKU Generator service.
 * 
 * @method static string generate(\Illuminate\Database\Eloquent\Model $model)
 * @method static string generateProductSku(\Illuminate\Database\Eloquent\Model $product)
 * @method static string generateVariantSku(\Illuminate\Database\Eloquent\Model $variant)
 *
 * @see \Gowelle\SkuGenerator\SkuGenerator
 * 
 * @example
 * // Generate SKU for a product
 * $sku = SkuGenerator::generate($product);
 * 
 * // Generate SKU for a variant
 * $variantSku = SkuGenerator::generate($variant);
 */
class SkuGenerator extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'gowelle.sku-generator';
    }
}

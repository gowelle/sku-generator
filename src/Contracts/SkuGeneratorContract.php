<?php

namespace Gowelle\SkuGenerator\Contracts;

use Illuminate\Database\Eloquent\Model;
use Gowelle\SkuGenerator\Exceptions\InvalidSkuMappingException;
use Gowelle\SkuGenerator\Exceptions\InvalidConfigurationException;

/**
 * Contract for SKU generation in Laravel applications.
 *
 * This interface defines the methods required for generating Stock Keeping Units (SKUs)
 * for products and their variants. It supports hierarchical SKU generation where variant
 * SKUs are based on their parent product's SKU.
 *
 * Example SKU formats:
 * - Product: TM-TSH-ABC12345 (prefix-category-unique)
 * - Variant: TM-TSH-ABC12345-RED-LRG (prefix-category-unique-properties)
 */
interface SkuGeneratorContract
{
    /**
     * Generate SKU for the given model.
     *
     * This is the main entry point for SKU generation. It determines the model type
     * and delegates to the appropriate specific generator method.
     *
     * @param  Model  $model  The model requiring a SKU (Product or Variant)
     * @throws InvalidSkuMappingException  When model type is not configured
     * @throws InvalidConfigurationException  When required config is missing
     * @return string  The generated unique SKU
     */
    public static function generate(Model $model): string;

    /**
     * Generate SKU for a product model.
     *
     * Creates a SKU using:
     * - Configured prefix
     * - Category code (from product's category name)
     * - Unique identifier
     *
     * @param  Model  $product  The product model with category relationship
     * @throws InvalidConfigurationException  When category access fails
     * @return string  The generated product SKU
     */
    public static function generateProductSku(Model $product): string;

    /**
     * Generate SKU for a variant model.
     *
     * Creates a hierarchical SKU using:
     * - Parent product's SKU
     * - Property value codes (e.g., color, size)
     *
     * @param  Model  $variant  The variant model with product and property values
     * @throws InvalidSkuMappingException  When parent product is missing
     * @throws InvalidConfigurationException  When property values are invalid
     * @return string  The generated variant SKU
     */
    public static function generateVariantSku(Model $variant): string;
}

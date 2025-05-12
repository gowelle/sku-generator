<?php

namespace Gowelle\SkuGenerator;

use Gowelle\SkuGenerator\Contracts\SkuGeneratorContract;
use Gowelle\SkuGenerator\Exceptions\InvalidSkuMappingException;
use Gowelle\SkuGenerator\Support\ConfigValidator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * SKU Generator for Laravel models.
 *
 * Generates unique SKUs for products and their variants based on:
 * - Product categories
 * - Property values (for variants)
 * - Unique identifiers
 * - Custom suffixes (optional)
 *
 * @implements SkuGeneratorContract
 */
class SkuGenerator implements SkuGeneratorContract
{
    /**
     * @var string Type identifier for product SKUs
     */
    private const SKU_TYPE_PRODUCT = 'product';

    /**
     * @var string Type identifier for variant SKUs
     */
    private const SKU_TYPE_VARIANT = 'variant';

    /**
     * @var string Default code for uncategorized products
     */
    private const UNCATEGORIZED = 'UNC';

    /**
     * Create a new SKU Generator instance.
     *
     * @throws InvalidConfigurationException When config is invalid
     */
    public function __construct()
    {
        ConfigValidator::validate(config('sku-generator'));
    }

    /**
     * Generate a SKU for the given model.
     *
     * @param Model $model The model requiring a SKU
     * @throws InvalidSkuMappingException When model is not configured
     * @return string The generated SKU
     */
    public static function generate(Model $model): string
    {
        $mapping = config('sku-generator.models');
        $modelClass = get_class($model);

        if (! array_key_exists($modelClass, $mapping)) {
            throw InvalidSkuMappingException::forModel($modelClass);
        }

        return match ($mapping[$modelClass]) {
            self::SKU_TYPE_PRODUCT => self::generateProductSku($model),
            self::SKU_TYPE_VARIANT => self::generateVariantSku($model),
            default => throw InvalidSkuMappingException::forType($mapping[$modelClass], $modelClass),
        };
    }

    /**
     * Generate a SKU for a product.
     *
     * @param Model $product The product model
     * @return string The generated product SKU
     */
    public static function generateProductSku(Model $product): string
    {
        $config = self::getConfig();
        $category = self::getProductCategory($product);

        $categoryCode = $category 
            ? Str::upper(Str::substr($category, 0, $config['category_length']))
            : self::UNCATEGORIZED;

        $uniqueId = Str::upper(Str::substr(uniqid(), 0, $config['ulid_length']));

        $sku = collect([$config['prefix'], $categoryCode, $uniqueId])
            ->filter()
            ->implode($config['separator']);

        return self::ensureUniqueSku($sku, $product);
    }

    /**
     * Generate a SKU for a product variant.
     *
     * @param Model $variant The variant model
     * @throws InvalidSkuMappingException When parent product or properties are missing
     * @return string The generated variant SKU
     */
    public static function generateVariantSku(Model $variant): string
    {
        self::validateVariant($variant);

        $config = self::getConfig();
        $productSku = $variant->product->sku;
        
        if (empty($productSku)) {
            throw new InvalidSkuMappingException("Product SKU is empty for variant #{$variant->id}");
        }

        $propertyCodes = self::getPropertyCodes($variant, $config);
        $sku = $propertyCodes->isEmpty() 
            ? $productSku 
            : "{$productSku}{$config['separator']}{$propertyCodes->implode($config['separator'])}";

        return self::ensureUniqueSku(
            self::applyCustomSuffix($sku, $variant),
            $variant
        );
    }

    /**
     * Get the category name from a product model.
     *
     * @param Model $product The product model
     * @return string|null The category name or null if not found
     */
    protected static function getProductCategory(Model $product): ?string
    {
        $config = self::getConfig();
        $accessor = $config['category_accessor'];

        if ($config['category_has_many']) {
            return $product->{$accessor}()->first()?->{$config['category_field']};
        }

        if (method_exists($product, $accessor)) {
            return $product->{$accessor}()?->{$config['category_field']};
        }

        if (property_exists($product, $accessor)) {
            return $product->{$accessor}?->{$config['category_field']};
        }

        return null;
    }

    /**
     * Get property codes for a variant.
     *
     * @param Model $variant The variant model
     * @param array $config The configuration array
     * @return Collection Collection of property codes
     */
    private static function getPropertyCodes(Model $variant, array $config): Collection
    {
        return $variant->{$config['property_values_accessor']}
            ->map(fn ($value) => Str::upper(
                Str::substr($value->{$config['property_values_field']}, 0, $config['property_values_length'])
            ));
    }

    /**
     * Validate a variant model has required relationships.
     *
     * @param Model $variant The variant model to validate
     * @throws InvalidSkuMappingException When validation fails
     */
    private static function validateVariant(Model $variant): void
    {
        if (!$variant->product) {
            throw new InvalidSkuMappingException("Variant must belong to a product");
        }

        if (!$variant->{config('sku-generator.property_values_accessor')}) {
            throw new InvalidSkuMappingException("Variant must have property values");
        }
    }

    /**
     * Get the configuration array for SKU generation.
     *
     * @return array The configuration array
     */
    private static function getConfig(): array
    {
        return [
            'prefix' => config('sku-generator.prefix'),
            'category_length' => config('sku-generator.category.length'),
            'category_field' => config('sku-generator.category.field'),
            'category_accessor' => config('sku-generator.category.accessor'),
            'category_has_many' => config('sku-generator.category.has_many'),
            'ulid_length' => config('sku-generator.ulid_length'),
            'separator' => config('sku-generator.separator'),
            'property_values_accessor' => config('sku-generator.property_values.accessor'),
            'property_values_field' => config('sku-generator.property_values.field'),
            'property_values_length' => config('sku-generator.property_values.length'),
        ];
    }

    /**
     * Ensure a SKU is unique within its model table.
     *
     * @param string $sku The SKU to check
     * @param Model $model The model being processed
     * @return string The unique SKU (may have counter suffix)
     */
    private static function ensureUniqueSku(string $sku, Model $model): string
    {
        static $existingSkus = [];
        
        $table = $model->getTable();
        if (!isset($existingSkus[$table])) {
            $existingSkus[$table] = DB::table($table)
                ->where('id', '!=', $model->id)
                ->pluck('sku')
                ->toArray();
        }

        $originalSku = $sku;
        $counter = 1;

        while (in_array($sku, $existingSkus[$table])) {
            $sku = $originalSku . '-' . $counter++;
        }

        $existingSkus[$table][] = $sku;
        return $sku;
    }

    /**
     * Apply custom suffix to SKU if configured.
     *
     * @param string $sku The base SKU
     * @param Model $model The model being processed
     * @return string The SKU with optional suffix
     */
    protected static function applyCustomSuffix(string $sku, Model $model): string
    {
        $suffixCallback = config('sku-generator.custom_suffix');

        if (is_callable($suffixCallback)) {
            $suffix = call_user_func($suffixCallback, $model);
            if (! empty($suffix)) {
                $sku .= config('sku-generator.separator').Str::upper($suffix);
            }
        }

        return $sku;
    }
}

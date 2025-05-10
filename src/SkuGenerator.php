<?php

namespace Gowelle\SkuGenerator;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SkuGenerator implements \Gowelle\SkuGenerator\Contracts\SkuGeneratorContract
{
    public static function generate(Model $model)
    {
        $mapping = config('sku-generator.models');

        $modelClass = get_class($model);

        if (!array_key_exists($modelClass, $mapping)) {
            throw new \Exception("No SKU generator mapping defined for {$modelClass}");
        }

        $type = $mapping[$modelClass];

        return match ($type) {
            'product' => self::generateProductSku($model),
            'variant' => self::generateVariantSku($model),
            default => throw new \Exception("Unsupported SKU type '{$type}' for {$modelClass}"),
        };
    }

    public static function generateProductSku(Model $product)
    {
        $prefix = config('sku-generator.prefix');
        $catLen = config('sku-generator.product_category_length');
        $ulidLen = config('sku-generator.ulid_length');

        $categoryCode = $product->category 
            ? strtoupper(substr($product->category->name, 0, $catLen))
            : 'UNC';

        // Generate random alphanumeric string for uniqueness
        $uniqueId = strtoupper(substr(uniqid(), 0, $ulidLen));

        $sku = "{$prefix}-{$categoryCode}-{$uniqueId}";

        return self::applyCustomSuffix(
            self::ensureUniqueSku($sku, $product->getTable()),
            $product
        );
    }

    public static function generateVariantSku(Model $variant)
    {
        $propLen = config('sku-generator.property_value_length');
        $productSku = $variant?->product->sku;

        if (empty($productSku)) {
            throw new \Exception("Product SKU is empty for variant ID {$variant->id}");
        }

        $propertyCodes = $variant->values
            ->map(fn($pv) => strtoupper(substr($pv->name, 0, $propLen)))
            ->implode('-');

        $sku = $propertyCodes ? "{$productSku}-{$propertyCodes}" : $productSku;

        return self::applyCustomSuffix(
            self::ensureUniqueSku($sku, $variant->getTable()),
            $variant
        );
    }

    protected static function applyCustomSuffix($sku, $model)
    {
        $suffixCallback = config('sku-generator.custom_suffix');

        if (is_callable($suffixCallback)) {
            $suffix = call_user_func($suffixCallback, $model);
            if (!empty($suffix)) {
                $sku .= '-' . strtoupper($suffix);
            }
        }

        return $sku;
    }

    protected static function ensureUniqueSku($baseSku, $table)
    {
        $sku = $baseSku;
        $suffix = 1;

        while (DB::table($table)->where('sku', $sku)->exists()) {
            $sku = "{$baseSku}-{$suffix}";
            $suffix++;
        }

        return $sku;
    }
}

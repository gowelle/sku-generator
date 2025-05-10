<?php

namespace Gowelle\SkuGenerator;

use Gowelle\SkuGenerator\Contracts\SkuGeneratorContract;
use Gowelle\SkuGenerator\Exceptions\InvalidSkuMappingException;
use Gowelle\SkuGenerator\Support\ConfigValidator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SkuGenerator implements SkuGeneratorContract
{
    public function __construct()
    {
        ConfigValidator::validate(config('sku-generator'));
    }

    public static function generate(Model $model): string
    {
        $mapping = config('sku-generator.models');
        $modelClass = get_class($model);

        if (! array_key_exists($modelClass, $mapping)) {
            throw InvalidSkuMappingException::forModel($modelClass);
        }

        return match ($mapping[$modelClass]) {
            'product' => self::generateProductSku($model),
            'variant' => self::generateVariantSku($model),
            default => throw InvalidSkuMappingException::forType($mapping[$modelClass], $modelClass),
        };
    }

    public static function generateProductSku(Model $product): string
    {
        $prefix = config('sku-generator.prefix');
        $catLen = config('sku-generator.product_category_length');
        $ulidLen = config('sku-generator.ulid_length');
        $separator = config('sku-generator.separator', '-');

        $categoryCode = $product->category 
            ? strtoupper(substr($product->category->name, 0, $catLen))
            : 'UNC';

        $uniqueId = strtoupper(substr(uniqid(), 0, $ulidLen));

        $sku = implode($separator, [
            $prefix,
            $categoryCode,
            $uniqueId
        ]);

        return self::ensureUniqueSku($sku, $product);
    }

    public static function generateVariantSku(Model $variant)
    {
        $propLen = config('sku-generator.property_value_length');
        $productSku = $variant?->product->sku;

        if (empty($productSku)) {
            throw new \Exception("Product SKU is empty for variant ID {$variant->id}");
        }

        $propertyCodes = $variant->values
            ->map(fn ($pv) => strtoupper(substr($pv->title, 0, $propLen)))
            ->implode(config('sku-generator.separator'));

        $sku = $propertyCodes ? "{$productSku}-{$propertyCodes}" : $productSku;

        return self::applyCustomSuffix(
            self::ensureUniqueSku($sku, $variant),
            $variant
        );
    }

    protected static function applyCustomSuffix($sku, $model)
    {
        $suffixCallback = config('sku-generator.custom_suffix');

        if (is_callable($suffixCallback)) {
            $suffix = call_user_func($suffixCallback, $model);
            if (! empty($suffix)) {
                $sku .= config('sku-generator.separator').strtoupper($suffix);
            }
        }

        return $sku;
    }

    private static function ensureUniqueSku(string $sku, Model $model): string
    {
        $originalSku = $sku;
        $counter = 1;

        while (DB::table($model->getTable())
            ->where('sku', $sku)
            ->where('id', '!=', $model->id)
            ->exists()
        ) {
            $sku = $originalSku . '-' . $counter++;
        }

        return $sku;
    }
}

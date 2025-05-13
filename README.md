# Laravel SKU Generator

[![Latest Version on Packagist](https://img.shields.io/packagist/v/gowelle/sku-generator.svg?style=flat-square)](https://packagist.org/packages/gowelle/sku-generator)
[![Total Downloads](https://img.shields.io/packagist/dt/gowelle/sku-generator.svg?style=flat-square)](https://packagist.org/packages/gowelle/sku-generator)
[![Tests](https://github.com/gowelle/sku-generator/actions/workflows/tests.yml/badge.svg)](https://github.com/gowelle/sku-generator/actions/workflows/tests.yml)

Generate meaningful SKUs for Laravel e-commerce products and variants.

## Requirements

- PHP ^8.2
- Laravel ^10.0|^11.0|^12.0

## Installation

You can install the package via composer:

```bash
composer require gowelle/sku-generator
```

Publish the configuration:

```bash
php artisan vendor:publish --tag="sku-generator-config"
```

## Usage

Add the `HasSku` trait to your models:

```php
use Gowelle\SkuGenerator\Concerns\HasSku;

class Product extends Model
{
    use HasSku;
}
```

SKUs will be automatically generated when models are created:

```php
$product = Product::create(['name' => 'T-Shirt']);
echo $product->sku; // Output: TM-TSH-ABC12345

$variant = $product->variants()->create([/* ... */]);
echo $variant->sku; // Output: TM-TSH-ABC12345-RED-LRG
```

## Configuration

```php
return [
    'prefix' => 'TM',
    'ulid_length' => 8,
    'separator' => '-',

    'models' => [
        \App\Models\Product::class => 'product',
        \App\Models\ProductVariant::class => 'variant',
    ],

    'category' => [
        'accessor' => 'category',
        'field' => 'name',
        'length' => 3,
        'has_many' => false,
    ],
];
```

## SKU Format

### Products
- Format: `{prefix}-{category}-{unique}`
- Example: `TM-TSH-ABC12345`

### Variants
- Format: `{prefix}-{category}-{unique}-{properties}`
- Example: `TM-TSH-ABC12345-RED-LRG`

## Regenerating SKUs

Use the artisan command to regenerate SKUs:

```bash
# Interactive mode
php artisan sku:regenerate

# Direct model specification
php artisan sku:regenerate "App\Models\Product"

# Skip confirmation
php artisan sku:regenerate --force
```

Features:
- Interactive model selection
- Progress reporting
- Chunked processing
- Failure logging
- Unique constraint preservation

## Testing

```bash
composer test
```

Format code:

```bash
composer format
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [John Gowelle](https://github.com/gowelle)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
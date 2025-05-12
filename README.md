# Laravel SKU Generator

[![Tests](https://github.com/gowelle/sku-generator/actions/workflows/tests.yml/badge.svg)](https://github.com/gowelle/sku-generator/actions/workflows/tests.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/gowelle/sku-generator.svg?style=flat-square)](https://packagist.org/packages/gowelle/sku-generator)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/gowelle/sku-generator/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/gowelle/sku-generator/actions?query=workflow%3ATests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/gowelle/sku-generator.svg?style=flat-square)](https://packagist.org/packages/gowelle/sku-generator)

Generate meaningful SKUs for your Laravel e-commerce products and variants.

## 🎯 Features

- **Automatic Generation**: SKUs are generated on model creation
- **Meaningful Format**: Uses category codes and property values
- **Hierarchical**: Variants inherit parent product's SKU
- **Configurable**: Customize prefixes, lengths, and separators
- **Lockable**: SKUs cannot be changed after creation
- **Well-Tested**: Comprehensive Pest test suite
- **Command Line**: Regenerate SKUs via artisan command
- **Type Safe**: Full PHP 8.2 type hints and return types

## 📦 Installation

```bash
composer require gowelle/sku-generator
```

Publish the configuration:

```bash
php artisan vendor:publish --tag="sku-generator-config"
```

## 🚀 Quick Start

```php
use Gowelle\SkuGenerator\Concerns\HasSku;

class Product extends Model
{
    use HasSku;
}

$product = Product::create(['name' => 'T-Shirt']);
echo $product->sku; // TM-TSH-ABC12345
```

## 📋 Requirements

- PHP 8.2 or higher
- Laravel 10.0 or higher
- Models with:
  - Category relationship (with `name` field)
  - Optional variants relationship
  - Optional property values (for variants)

## 🏗 Model Setup

### Product Model
```php
class Product extends Model
{
    use HasSku;

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }
}
```

### Variant Model
```php
class ProductVariant extends Model
{
    use HasSku;

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function propertyValues()
    {
        return $this->hasMany(PropertyValue::class);
    }
}
```

## ⚙️ Configuration

```php
// config/sku-generator.php
return [
    'prefix' => 'TM',
    'ulid_length' => 8,
    'separator' => '-',

    'category' => [
        'accessor' => 'category',
        'field' => 'name',
        'length' => 3,
        'has_many' => false,
    ],

    'property_values' => [
        'accessor' => 'propertyValues',
        'field' => 'name',
        'length' => 3,
    ],

    'models' => [
        \App\Models\Product::class => 'product',
        \App\Models\ProductVariant::class => 'variant',
    ],
];
```

## 🧩 Examples

### Basic Product
```php
$product = Product::create([
    'name' => 'Classic T-Shirt',
    'category_id' => $category->id
]);

echo $product->sku; // TM-TSH-ABC12345
```

### Product with Variant
```php
$variant = $product->variants()->create();
$variant->propertyValues()->createMany([
    ['name' => 'Red'],
    ['name' => 'Large']
]);

echo $variant->sku; // TM-TSH-ABC12345-RED-LRG
```

## 🔄 SKU Regeneration

Regenerate SKUs using the artisan command:

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

## ✅ Testing

```bash
composer test
```

## 🤝 Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## 🔒 Security

Please review our [Security Policy](SECURITY.md) for reporting vulnerabilities.

## 👥 Credits

- [John Gowelle](https://github.com/gowelle)
- [All Contributors](../../contributors)

## 📄 License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
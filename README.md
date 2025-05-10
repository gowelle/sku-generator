# Laravel SKU Generator

[![Tests](https://github.com/gowelle/sku-generator/actions/workflows/tests.yml/badge.svg)](https://github.com/gowelle/sku-generator/actions/workflows/tests.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/gowelle/sku-generator.svg)](https://packagist.org/packages/gowelle/sku-generator)
[![Total Downloads](https://img.shields.io/packagist/dt/gowelle/sku-generator.svg)](https://packagist.org/packages/gowelle/sku-generator)

Generate meaningful SKUs for your Laravel e-commerce products and variants.

## 📦 Quick Start

```bash
composer require gowelle/sku-generator
```

```php
use Gowelle\SkuGenerator\Concerns\HasSku;

class Product extends Model
{
    use HasSku;
}

$product = Product::create(['name' => 'T-Shirt']);
echo $product->sku; // TM-TSH-ABC12345
```

## 🎯 Features

- **Automatic Generation**: SKUs are generated on model creation
- **Meaningful Format**: Uses category codes and property values
- **Hierarchical**: Variants inherit parent product's SKU
- **Configurable**: Customize prefixes, lengths, and separators
- **Lockable**: SKUs cannot be changed after creation
- **Well-Tested**: Comprehensive Pest test suite

## 📋 Requirements

- PHP 8.2 or higher
- Laravel 10.0 or higher
- Models with:
  - Category relationship (with `name` field)
  - Optional variants relationship
  - Optional property values (for variants)

## 🏗 Model Structure

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

class Category extends Model
{
    protected $fillable = ['name'];
}

class ProductVariant extends Model
{
    use HasSku;

    public function values()
    {
        return $this->hasMany(PropertyValue::class);
    }
}
```

## ⚙️ Configuration

```bash
php artisan vendor:publish --tag="sku-generator-config"
```

```php
// config/sku-generator.php
return [
    'prefix' => 'TM',
    'product_category_length' => 3,
    'ulid_length' => 8,
    'property_value_length' => 3,
    'separator' => '-',
    
    'models' => [
        \App\Models\Product::class => 'product',
        \App\Models\ProductVariant::class => 'variant',
    ]
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

### Regenerating SKUs

You can regenerate SKUs for existing models using the artisan command:

```bash
# Regenerate SKUs for all products
php artisan sku:regenerate "App\Models\Product"

# Regenerate SKUs for all variants
php artisan sku:regenerate "App\Models\ProductVariant"
```

The command will:
- Preserve unique constraints
- Show progress with old → new SKU mapping
- Handle large datasets using chunking
- Skip invalid models

## ✅ Testing

```bash
composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [John Gowelle](https://github.com/gowelle)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
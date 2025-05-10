## gowelle/sku-generator

[![Latest Version on Packagist](https://img.shields.io/packagist/v/gowelle/sku-generator.svg?style=flat-square)](https://packagist.org/packages/gowelle/sku-generator)
[![Total Downloads](https://img.shields.io/packagist/dt/gowelle/sku-generator.svg?style=flat-square)](https://packagist.org/packages/gowelle/sku-generator)
[![License](https://img.shields.io/packagist/l/gowelle/sku-generator.svg?style=flat-square)](https://packagist.org/packages/gowelle/sku-generator)

🎯 **SKU Generator for Laravel**

Automatic SKU generation for Laravel e-commerce applications, using product categories and property values to create meaningful, hierarchical identifiers.

---

## ✨ Features

✅ Automatic and unique SKU generation  
✅ Works with products, variants, or any model  
✅ Prevents SKU changes after creation (SKU locking)  
✅ Configurable prefixes, suffixes, and model mappings  
✅ Easy integration via `HasSku` trait  
✅ Facade + command to regenerate SKUs for the given model
✅ Pest test suite included  

---

## 🏗 Required Structure

```php
class Product extends Model
{
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
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function values()
    {
        return $this->hasMany(PropertyValue::class);
    }
}

class PropertyValue extends Model
{
    protected $fillable = ['title'];
}
```

---

## 📦 Installation

```bash
composer require gowelle/sku-generator
```

---

## ⚙️ Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag=sku-generator-config
```

This creates `config/sku-generator.php`:

```php
return [
    'prefix' => 'TM',
    'product_category_length' => 3,
    'ulid_length' => 8,
    'property_value_length' => 3,

    'separator' => '-',

    'models' => [
        // \App\Models\Product::class => 'product',
        // \App\Models\ProductVariant::class => 'variant',
    ],

    'custom_suffix' => null, // function ($model) {
        // Example: add country code suffix if present
        // return property_exists($model, 'country_code') ? $model->country_code : null;
    // },
];
```

---

## 🧩 Usage

### Product SKUs

```php
$product = Product::create([
    'name' => 'Classic T-Shirt',
    'category_id' => Category::whereName('T-Shirts')->first()->id
]);
echo $product->sku; // TM-TSH-ABC12345

$variant = $product->variants()->create();
$variant->propertyValues()->createMany([
    ['name' => 'Red'],
    ['name' => 'Large']
]);
echo $variant->sku; // TM-TSH-ABC12345-RED-LRG
```

### 1. Add the `HasSku` trait to your models

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Gowelle\SkuGenerator\Traits\HasSku;

class Product extends Model
{
    use HasSku;

    protected $fillable = ['name', 'sku'];
}
```

### 2. Create a product

```php
$product = Product::create(['name' => 'Cool Shirt']);
echo $product->sku; // e.g., TM-CLT-IOUB9ATG
```

### 3. Use the Facade

```php
use SkuGenerator;

$sku = SkuGenerator::generate($product);
```

---

## 🔑 Config Options

| Option      | Description                                    |
|-------------|------------------------------------------------|
| `prefix`   | Prefix for SKUs (default: `TM`)              |
| `suffix`   | Optional suffix                               |
| `separator`| Separator between parts (default: `-`)        |
| `models`   | Model-to-name mapping for SKU generation      |

---

## ✅ Testing

Run the tests:

```bash
composer test
```

Or directly:

```bash
./vendor/bin/pest
```

## 🛡 Example Test (PEST)

```php
it('generates a unique sku', function () {
    $product = Product::create(['name' => 'Test Product']);
    expect($product->sku)->not->toBeEmpty();
});
```

---

## 🤝 Contributing

1. Fork this repo  
2. Create your feature branch  
3. Commit your changes  
4. Push to the branch  
5. Open a pull request

See [`CONTRIBUTING.md`](CONTRIBUTING.md) for details.

---

## 📄 License

MIT © Gowelle

---

## 📣 Stay in Touch

Follow updates and releases:

- [GitHub](https://github.com/gowelle/sku-generator)
- [Packagist](https://packagist.org/packages/gowelle/sku-generator)
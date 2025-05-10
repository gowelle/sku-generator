## gowelle/sku-generator

🎯 **SKU Generator for Laravel**

This package provides automatic, unique, and customizable SKU generation for your Laravel models — perfect for products, variants, or any entity that needs a unique SKU.

---

## ✨ Features

✅ Automatic and unique SKU generation  
✅ Works with products, variants, or any model
✅ Prevents SKU changes after creation (SKU locking)  
✅ Configurable prefixes, suffixes, and model mappings  
✅ Easy integration via `HasSku` trait  
✅ Facade + helper function available  
✅ Artisan command to regenerate SKUs  
✅ Pest test suite included

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

---

### 2. Create a product

```php
$product = Product::create(['name' => 'Cool Shirt']);
echo $product->sku; // e.g., TM-CLT-IOUB9ATG
```

---

### 3. Use the Facade

```php
use SkuGenerator;

$sku = SkuGenerator::generate($product);
```

---

---

### 4. Run the Artisan command

Regenerate SKUs for a model:

```bash
php artisan sku:regenerate "App\Models\Product"
```

---

## 🔑 Config Options

| Option      | Description                                    |
|-------------|------------------------------------------------|
| `prefix`   | Prefix for SKUs (default: `TMK`)              |
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

---

## 🛡 Example Test (PEST)

```php
it('generates a unique sku', function () {
    $product = Product::create(['name' => 'Test Product']);
    expect($product->sku)->not->toBeEmpty();
});
```

---

## 🚀 Roadmap

- Helper function: `sku_regenerate($product)`

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

## 🏷 Example

```php
$product = Product::create(['name' => 'Winter Jacket']);
echo $product->sku; // TM-CLT-IOUB9ATG
```

---

## 📣 Stay in Touch

Follow updates and releases:

- [GitHub](https://github.com/gowelle/sku-generator)
- [Packagist](https://packagist.org/packages/gowelle/sku-generator)
```
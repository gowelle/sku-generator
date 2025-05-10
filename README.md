Here’s the **final `README.md` file** — fully prepared, with badges included at the top for GitHub Actions, Packagist, and License:

---

```markdown
# gowelle/sku-generator

[![Tests](https://github.com/gowelle/sku-generator/actions/workflows/ci.yml/badge.svg)](https://github.com/gowelle/sku-generator/actions)
[![Packagist](https://img.shields.io/packagist/v/gowelle/sku-generator.svg)](https://packagist.org/packages/gowelle/sku-generator)
[![License](https://img.shields.io/github/license/gowelle/sku-generator.svg)](LICENSE)

🎯 **SKU Generator for Laravel**

This package provides automatic, unique, and customizable SKU generation for your Laravel models — perfect for products, variants, or any entity that needs a unique SKU.

---

## ✨ Features

✅ Automatic and unique SKU generation  
✅ Works with products, variants, or any model  
✅ Supports ULIDs and morph relationships  
✅ Prevents SKU changes after creation (SKU locking)  
✅ Configurable prefixes, suffixes, separators, and model mappings  
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
    'prefix' => 'TMK',
    'suffix' => '',
    'separator' => '-',
    'models' => [
        'Product' => \App\Models\Product::class,
        'Variant' => \App\Models\Variant::class,
    ],
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

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->sku = $model->generateSku();
        });
    }
}
```

---

### 2. Create a product

```php
$product = Product::create(['name' => 'Cool Shirt']);
echo $product->sku; // e.g., TMK-COOLSHIRT-8X4LP
```

---

### 3. Use the Facade

```php
use SkuGenerator;

$sku = SkuGenerator::generate($product);
```

---

### 4. Use the helper function

```php
$sku = sku_generate($product);
```

---

### 5. Run the Artisan command

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

- Artisan command: `sku:regenerate`  
- Custom Pest expectations (`toBeValidSku()`)  
- Snapshot + dataset tests  
- Laravel Nova / Filament field integration

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
echo $product->sku; // TMK-WINTERJACKET-AB12CD
```

---

## 📣 Stay in Touch

Follow updates and releases:

- [GitHub](https://github.com/gowelle/sku-generator)
- [Packagist](https://packagist.org/packages/gowelle/sku-generator)
```

---
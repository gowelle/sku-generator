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

## SKU History & Audit Trail

The package includes a comprehensive audit trail system to track all SKU lifecycle events.

### Setup

Publish and run the migration:

```bash
php artisan vendor:publish --tag="sku-generator-migrations"
php artisan migrate
```

#### Customizing for Non-Integer Primary Keys

If your models or users use primary key types other than integer (e.g., UUID, string), you'll need to modify the published migration before running it.

After publishing the migration, edit the `model_id` and `user_id` columns in the migration file to match your primary key types:

**For UUID primary keys:**

```php
// Change from:
$table->unsignedBigInteger('model_id')->index();
$table->unsignedBigInteger('user_id')->nullable();

// To:
$table->uuid('model_id')->index();
$table->uuid('user_id')->nullable();
```

**For string primary keys:**

```php
// Change from:
$table->unsignedBigInteger('model_id')->index();
$table->unsignedBigInteger('user_id')->nullable();

// To:
$table->string('model_id')->index();
$table->string('user_id')->nullable();
```

**For different types on model_id and user_id:**

```php
// Example: UUID for models, integer for users
$table->uuid('model_id')->index();
$table->unsignedBigInteger('user_id')->nullable();
```

The migration file will be published to `database/migrations/YYYY_MM_DD_HHMMSS_create_sku_histories_table.php`.

### Configuration

Configure history tracking in `config/sku-generator.php`:

```php
'history' => [
    'enabled' => env('SKU_HISTORY_ENABLED', true),
    'track_user' => true,
    'track_ip' => false,
    'track_user_agent' => false,
    'retention_days' => null, // null = keep forever
    'table_name' => 'sku_histories',
],
```

### Tracked Events

The following events are automatically tracked:

- **Created**: When a new SKU is generated
- **Regenerated**: When `forceRegenerateSku()` is called
- **Modified**: When a SKU is manually modified
- **Deleted**: When a model with a SKU is deleted

### Viewing History

#### Via Model Relationship

```php
// Get all history for a model
$history = $product->skuHistory;

// Get latest history entry
$latest = $product->getLatestSkuHistory();

// Get all history entries
$allHistory = $product->getSkuHistory();
```

#### Via Artisan Command

```bash
# View history for a specific model
php artisan sku:history "App\Models\Product" --id=123

# View history for a specific SKU
php artisan sku:history --sku="TM-TSH-ABC12345"

# View recent changes
php artisan sku:history --recent --days=7

# Filter by event type
php artisan sku:history --event=regenerated --limit=100
```

### Query Interface

Use the `SkuHistory` model to query history:

```php
use Gowelle\SkuGenerator\Models\SkuHistory;

// Get history for a specific model
SkuHistory::forModel($product)->get();

// Find history for a specific SKU
SkuHistory::forSku('TM-TSH-ABC12345')->get();

// Filter by event type
SkuHistory::byEventType('regenerated')->get();

// Get recent changes
SkuHistory::recentChanges(7)->get();

// Filter by date range
SkuHistory::between('2024-01-01', '2024-12-31')->get();

// Filter by user
SkuHistory::byUser($userId)->get();
```

### Events

The package dispatches Laravel events for all SKU changes:

```php
use Gowelle\SkuGenerator\Events\{SkuCreated, SkuRegenerated, SkuModified, SkuDeleted};

// Listen to events in your EventServiceProvider
Event::listen(SkuRegenerated::class, function ($event) {
    // $event->model - The model that was changed
    // $event->oldSku - The previous SKU
    // $event->newSku - The new SKU
    // $event->reason - Optional reason for change
});
```

### Cleanup Old History

Clean up old history records:

```bash
# Cleanup based on configured retention policy
php artisan sku:history:cleanup

# Delete records older than 365 days
php artisan sku:history:cleanup --days=365

# Delete records before a specific date
php artisan sku:history:cleanup --before="2024-01-01"

# Preview what would be deleted
php artisan sku:history:cleanup --days=365 --dry-run

# Skip confirmation
php artisan sku:history:cleanup --days=365 --force
```

### Disabling History

To disable history tracking:

```php
// In config/sku-generator.php
'history' => [
    'enabled' => false,
    // ...
],

// Or via environment variable
SKU_HISTORY_ENABLED=false
```

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

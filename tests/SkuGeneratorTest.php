<?php

namespace Gowelle\SkuGenerator\Tests;

use Gowelle\SkuGenerator\SkuGeneratorServiceProvider;
use Laravel\Prompts\Prompt;
use Orchestra\Testbench\TestCase;
use Illuminate\Database\Eloquent\Model;
use Gowelle\SkuGenerator\Concerns\HasSku;

beforeEach(function () {
    // Register service provider
    $this->app->register(SkuGeneratorServiceProvider::class);

    // Set up database
    $this->app['db']->connection()->getSchemaBuilder()->dropIfExists('test_products');
    $this->app['db']->connection()->getSchemaBuilder()->create('test_products', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('sku')->nullable()->unique();
        $table->unsignedBigInteger('category_id')->nullable();
        $table->timestamps();
    });

    $this->app['db']->connection()->getSchemaBuilder()->dropIfExists('test_variants');
    $this->app['db']->connection()->getSchemaBuilder()->create('test_variants', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('sku')->nullable()->unique();
        $table->unsignedBigInteger('product_id');
        $table->timestamps();
    });

    $this->app['db']->connection()->getSchemaBuilder()->dropIfExists('test_property_values');
    $this->app['db']->connection()->getSchemaBuilder()->create('test_property_values', function ($table) {
        $table->id();
        $table->string('name');
        $table->unsignedBigInteger('variant_id');
        $table->timestamps();
    });

    // Configure package
    $this->app['config']->set('sku-generator', [
        'prefix' => 'TM',
        'ulid_length' => 8,

        'separator' => '-',

        'category' => [
            'accessor' => 'category',
            'field' => 'name',
            'length' => 3,
            'has_many' => false,
        ],
        'property_values' => [  // Changed from property_value to property_values
            'accessor' => 'propertyValues',
            'field' => 'name',
            'length' => 3,
        ],
        'models' => [
            TestProduct::class => 'product',
            TestVariant::class => 'variant',
        ],

        'custom_suffix' => null, // function ($model) {
        // Example: add country code suffix if present
        // return property_exists($model, 'country_code') ? $model->country_code : null;
        // },
    ]);

    Prompt::fake();
});

test('generates a unique sku for new products', function () {
    $product = TestProduct::create([
        'name' => 'Test Product',
    ]);

    expect($product->sku)
        ->not->toBeEmpty()
        ->toMatch('/^TM-UNC-[A-Z0-9]+$/');
})->uses(TestCase::class);

test('keeps the original sku when updating products', function () {
    $product = TestProduct::create([
        'name' => 'Initial Product',
    ]);

    $originalSku = $product->sku;

    $product->refresh();
    $product->update(['name' => 'Updated Product']);

    expect($product->sku)->toBe($originalSku);
})->uses(TestCase::class);

test('it regenerates skus for valid models', function () {
    // Set up test config
    config()->set('sku-generator.models', [
        TestProduct::class => 'product'
    ]);

    // Create test products
    TestProduct::create(['name' => 'Product 1', 'sku' => 'OLD-SKU-1']);
    TestProduct::create(['name' => 'Product 2', 'sku' => 'OLD-SKU-2']);

    // Run command with --force to skip confirmation
    $this->artisan('sku:regenerate', [
        'model' => TestProduct::class,
        '--force' => true,
    ])->assertSuccessful();

    // Verify SKUs were updated
    $products = TestProduct::all();
    expect($products)->toHaveCount(2)
        ->and($products[0]->sku)->not->toBe('OLD-SKU-1')
        ->and($products[1]->sku)->not->toBe('OLD-SKU-2')
        ->and($products[0]->sku)->toMatch('/^TM-UNC-[A-Z0-9]+(?:-\d+)?$/')
        ->and($products[1]->sku)->toMatch('/^TM-UNC-[A-Z0-9]+(?:-\d+)?$/');
});

test('it fails for non-existent model class', function () {
    $this->artisan('sku:regenerate', [
        'model' => 'App\\NonExistentModel',
    ])->assertFailed();
});

test('it fails for model without HasSku trait', function () {
    $this->artisan('sku:regenerate', [
        'model' => InvalidProduct::class,
    ])->assertFailed();
});

test('it handles variants without property values', function () {
    $product = TestProduct::create(['name' => 'Test Product']);
    $variant = TestVariant::create([
        'name' => 'Test Variant',
        'product_id' => $product->id,
    ]);

    $this->artisan('sku:regenerate', [
        'model' => TestVariant::class,
        '--force' => true,
    ])->assertSuccessful();

    // Update pattern to allow for numeric suffixes
    expect($variant->refresh()->sku)
        ->not->toBeNull()
        ->toMatch('/^TM-UNC-[A-Z0-9]+(?:-\d+)*$/');
});

test('it handles variants with property values', function () {
    // Suppress deprecation warnings
    error_reporting(E_ALL & ~E_DEPRECATED);
    
    // Create test models
    $product = TestProduct::create(['name' => 'Test Product']);
    $variant = TestVariant::create([
        'name' => 'Test Variant',
        'product_id' => $product->id,
    ]);

    // Create and verify property values
    $variant->propertyValues()->createMany([
        ['name' => 'Red'],
        ['name' => 'Large']
    ]);

    // Force refresh to ensure relations are loaded
    $variant->load(['product', 'propertyValues']);

    // Run command
    $this->artisan('sku:regenerate', [
        'model' => TestVariant::class,
        '--force' => true,
    ])->assertSuccessful();

    // Verify results
    $variant->refresh();

    expect($variant->propertyValues)->toHaveCount(2)
        ->and($variant->sku)->toMatch('/^TM-UNC-[A-Z0-9]+-RED-LAR$/');
})->group('debug');

class InvalidProduct extends Model
{
    protected $fillable = ['name', 'sku'];
}

class TestProduct extends Model
{
    use HasSku;

    protected $fillable = ['name', 'sku'];
    protected $table = 'test_products';
}

class TestVariant extends Model
{
    use HasSku;

    protected $fillable = ['name', 'sku', 'product_id'];
    protected $table = 'test_variants';
    protected $with = ['propertyValues']; // Eager load property values

    public function product()
    {
        return $this->belongsTo(TestProduct::class);
    }

    public function propertyValues()
    {
        return $this->hasMany(TestPropertyValue::class, 'variant_id');
    }
}

class TestPropertyValue extends Model
{
    protected $fillable = ['name', 'variant_id'];
    protected $table = 'test_property_values';

    public function variant()
    {
        return $this->belongsTo(TestVariant::class);
    }
}

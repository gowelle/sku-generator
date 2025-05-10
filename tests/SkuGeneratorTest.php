<?php

namespace Tests;

use Gowelle\SkuGenerator\Concerns\HasSku;
use Gowelle\SkuGenerator\SkuGeneratorServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Orchestra\Testbench\TestCase;

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

    // Configure package
    $this->app['config']->set('sku-generator', [
        'prefix' => 'TM',
        'product_category_length' => 3,
        'ulid_length' => 8,
        'property_value_length' => 3,

        'separator' => '-',

        'models' => [
            TestProduct::class => 'product',
        ],

        'custom_suffix' => null, // function ($model) {
        // Example: add country code suffix if present
        // return property_exists($model, 'country_code') ? $model->country_code : null;
        // },
    ]);
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
    // Create test products
    TestProduct::create(['name' => 'Product 1', 'sku' => 'OLD-SKU-1']);
    TestProduct::create(['name' => 'Product 2', 'sku' => 'OLD-SKU-2']);

    // Run command
    $this->artisan('sku:regenerate', [
        'model' => TestProduct::class,
    ])->assertSuccessful();

    // Verify SKUs were updated
    $products = TestProduct::all();
    expect($products)->toHaveCount(2)
        ->and($products[0]->sku)->toBe('OLD-SKU-1')
        ->and($products[1]->sku)->toBe('OLD-SKU-2');
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

class TestProduct extends Model
{
    use HasSku;

    protected $fillable = ['name', 'sku'];

    protected $table = 'test_products';
}

class InvalidProduct extends Model
{
    protected $fillable = ['name', 'sku'];
}

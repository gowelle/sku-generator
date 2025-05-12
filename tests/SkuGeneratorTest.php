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
        'property_value' => [
            'accessor' => 'values',
            'field' => 'title',
            'length' => 3,
        ],
        'models' => [
            TestProduct::class => 'product',
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

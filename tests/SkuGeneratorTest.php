<?php

namespace Tests;

use Orchestra\Testbench\TestCase;
use Gowelle\SkuGenerator\Concerns\HasSku;
use Gowelle\SkuGenerator\Facades\SkuGenerator;
use Gowelle\SkuGenerator\SkuGeneratorServiceProvider;
use Illuminate\Database\Eloquent\Model;

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
        'custom_suffix' => null,
        'models' => [
            TestProduct::class => 'product',
        ]
    ]);
});

test('generates a unique sku for new products', function () {
    $product = TestProduct::create([
        'name' => 'Test Product'
    ]);

    expect($product->sku)
        ->not->toBeEmpty()
        ->toMatch('/^TM-UNC-[A-Z0-9]+$/');
})->uses(TestCase::class);

test('keeps the original sku when updating products', function () {
    $product = TestProduct::create([
        'name' => 'Initial Product'
    ]);

    $originalSku = $product->sku;
    
    $product->refresh();
    $product->update(['name' => 'Updated Product']);

    expect($product->sku)->toBe($originalSku);
})->uses(TestCase::class);

class TestProduct extends Model
{
    use HasSku;

    protected $fillable = ['name', 'sku'];
    protected $table = 'test_products';
}

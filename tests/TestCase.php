<?php

namespace Gowelle\SkuGenerator\Tests;

use Gowelle\SkuGenerator\Concerns\HasSku;
use Gowelle\SkuGenerator\SkuGeneratorServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->setupTables();
        
        config()->set('sku-generator', [
            'prefix' => 'TM',
            'ulid_length' => 8,
            'separator' => '-',
            'models' => [
                TestProduct::class => 'product',
                TestVariant::class => 'variant',
            ],
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
                'required' => false,
            ],
        ]);
    }

    protected function getPackageProviders($app): array
    {
        return [
            SkuGeneratorServiceProvider::class,
        ];
    }

    private function setupTables(): void
    {
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
            $table->unsignedBigInteger('product_id')->nullable();
            $table->timestamps();
        });

        $this->app['db']->connection()->getSchemaBuilder()->dropIfExists('test_property_values');
        $this->app['db']->connection()->getSchemaBuilder()->create('test_property_values', function ($table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('variant_id');
            $table->timestamps();
        });
    }
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

    protected $fillable = ['name', 'sku'];
    protected $table = 'test_variants';
}

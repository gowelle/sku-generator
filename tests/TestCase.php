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

        // Set up database
        $this->setupDatabase();
        
        // Configure package
        $this->setupConfig();
    }

    protected function getPackageProviders($app): array
    {
        return [
            SkuGeneratorServiceProvider::class,
        ];
    }

    private function setupDatabase(): void
    {
        $this->app['db']->connection()->getSchemaBuilder()->dropIfExists('test_products');
        $this->app['db']->connection()->getSchemaBuilder()->create('test_products', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('sku')->nullable()->unique();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->timestamps();
        });
    }

    private function setupConfig(): void
    {
        $this->app['config']->set('sku-generator', [
            'prefix' => 'TM',
            'ulid_length' => 8,
            'separator' => '-',
            'models' => [
                TestProduct::class => 'product',
            ],
            'category' => [
                'accessor' => 'category',
                'field' => 'name',
                'length' => 3,
                'has_many' => false,
            ],
        ]);
    }
}

class TestProduct extends Model
{
    use HasSku;

    protected $fillable = ['name', 'sku'];
    protected $table = 'test_products';
}

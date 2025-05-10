<?php

namespace Tests;

use Gowelle\SkuGenerator\SkuGeneratorServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createTestDatabase();
    }

    protected function getPackageProviders($app)
    {
        return [
            SkuGeneratorServiceProvider::class,
        ];
    }

    private function createTestDatabase()
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

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}

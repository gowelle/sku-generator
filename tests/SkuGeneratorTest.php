<?php

namespace Gowelle\SkuGenerator\Tests;

use Illuminate\Database\Eloquent\Model;
use Orchestra\Testbench\TestCase;
use Gowelle\SkuGenerator\SkuGeneratorServiceProvider;
use Gowelle\SkuGenerator\Concerns\HasSku;

class SkuGeneratorTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [SkuGeneratorServiceProvider::class];
    }

    /** @test */
    public function it_generates_a_unique_sku()
    {
        $model = new TestProduct();
        $model->name = 'Test Product';
        $model->save();

        $this->assertNotEmpty($model->sku);
        $this->assertMatchesRegularExpression('/^PRD-[A-Z0-9]+$/', $model->sku);
    }

    /** @test */
    public function it_locks_the_sku_after_creation()
    {
        $model = new TestProduct();
        $model->name = 'Initial Product';
        $model->save();

        $originalSku = $model->sku;

        $model->name = 'Updated Product';
        $model->save();

        $this->assertEquals($originalSku, $model->sku);
    }
}

class TestProduct extends Model
{
    use HasSku;

    public $name;
    public $sku;
    public $timestamps = false;

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->sku = $model->generateSku();
        });
    }
}

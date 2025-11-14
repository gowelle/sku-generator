<?php

namespace Gowelle\SkuGenerator\Tests;

use Gowelle\SkuGenerator\Events\SkuCreated;
use Gowelle\SkuGenerator\Events\SkuDeleted;
use Gowelle\SkuGenerator\Events\SkuRegenerated;
use Gowelle\SkuGenerator\Models\SkuHistory;
use Gowelle\SkuGenerator\Services\SkuHistoryLogger;
use Gowelle\SkuGenerator\SkuGeneratorServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Orchestra\Testbench\TestCase;
use Gowelle\SkuGenerator\Concerns\HasSku;

beforeEach(function () {
    // Register service provider
    $this->app->register(SkuGeneratorServiceProvider::class);

    // Set up test_products table
    $this->app['db']->connection()->getSchemaBuilder()->dropIfExists('test_products');
    $this->app['db']->connection()->getSchemaBuilder()->create('test_products', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('sku')->nullable()->unique();
        $table->timestamps();
    });

    // Set up sku_histories table
    $this->app['db']->connection()->getSchemaBuilder()->dropIfExists('sku_histories');
    $this->app['db']->connection()->getSchemaBuilder()->create('sku_histories', function ($table) {
        $table->id();
        $table->string('old_sku')->nullable()->index();
        $table->string('new_sku')->nullable()->index();
        $table->string('model_type')->index();
        $table->unsignedBigInteger('model_id')->index();
        $table->string('event_type');
        $table->unsignedBigInteger('user_id')->nullable();
        $table->string('user_type')->nullable();
        $table->text('metadata')->nullable();
        $table->string('reason')->nullable();
        $table->string('ip_address', 45)->nullable();
        $table->string('user_agent')->nullable();
        $table->timestamps();

        $table->index(['model_type', 'model_id']);
        $table->index('created_at');
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
        'models' => [
            HistoryTestProduct::class => 'product',
        ],
        'history' => [
            'enabled' => true,
            'track_user' => false,
            'track_ip' => false,
            'track_user_agent' => false,
            'retention_days' => null,
            'table_name' => 'sku_histories',
        ],
    ]);
});

test('creates history record when SKU is generated', function () {
    $product = HistoryTestProduct::create(['name' => 'Test Product']);

    expect(SkuHistory::count())->toBe(1);

    $history = SkuHistory::first();
    expect($history->event_type)->toBe(SkuHistory::EVENT_CREATED)
        ->and($history->old_sku)->toBeNull()
        ->and($history->new_sku)->toBe($product->sku)
        ->and($history->model_type)->toBe(HistoryTestProduct::class)
        ->and($history->model_id)->toBe($product->id);
})->uses(TestCase::class);

test('creates history record when SKU is regenerated', function () {
    $product = HistoryTestProduct::create(['name' => 'Test Product']);
    $oldSku = $product->sku;

    // Clear existing history from creation
    SkuHistory::truncate();

    $product->forceRegenerateSku('Testing regeneration');

    expect(SkuHistory::count())->toBe(1);

    $history = SkuHistory::first();
    expect($history->event_type)->toBe(SkuHistory::EVENT_REGENERATED)
        ->and($history->old_sku)->toBe($oldSku)
        ->and($history->new_sku)->toBe($product->sku)
        ->and($history->reason)->toBe('Testing regeneration');
})->uses(TestCase::class);

test('creates history record when model is deleted', function () {
    $product = HistoryTestProduct::create(['name' => 'Test Product']);
    $sku = $product->sku;

    // Clear existing history from creation
    SkuHistory::truncate();

    $product->delete();

    expect(SkuHistory::count())->toBe(1);

    $history = SkuHistory::first();
    expect($history->event_type)->toBe(SkuHistory::EVENT_DELETED)
        ->and($history->old_sku)->toBe($sku)
        ->and($history->new_sku)->toBeNull();
})->uses(TestCase::class);

test('fires SkuCreated event', function () {
    Event::fake();

    $product = HistoryTestProduct::create(['name' => 'Test Product']);

    Event::assertDispatched(SkuCreated::class, function ($event) use ($product) {
        return $event->model->id === $product->id
            && $event->sku === $product->sku;
    });
})->uses(TestCase::class);

test('fires SkuRegenerated event', function () {
    Event::fake();

    $product = HistoryTestProduct::create(['name' => 'Test Product']);
    $oldSku = $product->sku;

    $product->forceRegenerateSku();

    Event::assertDispatched(SkuRegenerated::class, function ($event) use ($product, $oldSku) {
        return $event->model->id === $product->id
            && $event->oldSku === $oldSku
            && $event->newSku === $product->sku;
    });
})->uses(TestCase::class);

test('fires SkuDeleted event', function () {
    Event::fake();

    $product = HistoryTestProduct::create(['name' => 'Test Product']);
    $sku = $product->sku;

    $product->delete();

    Event::assertDispatched(SkuDeleted::class, function ($event) use ($sku) {
        return $event->sku === $sku;
    });
})->uses(TestCase::class);

test('can query history by model', function () {
    $product1 = HistoryTestProduct::create(['name' => 'Product 1']);
    $product2 = HistoryTestProduct::create(['name' => 'Product 2']);

    $history = SkuHistory::forModel($product1)->get();

    expect($history->count())->toBe(1)
        ->and($history->first()->model_id)->toBe($product1->id);
})->uses(TestCase::class);

test('can query history by SKU', function () {
    $product = HistoryTestProduct::create(['name' => 'Test Product']);
    $sku = $product->sku;

    $history = SkuHistory::forSku($sku)->get();

    expect($history->count())->toBe(1)
        ->and($history->first()->new_sku)->toBe($sku);
})->uses(TestCase::class);

test('can query history by event type', function () {
    $product1 = HistoryTestProduct::create(['name' => 'Product 1']);
    $product2 = HistoryTestProduct::create(['name' => 'Product 2']);
    $product1->forceRegenerateSku();

    $createdHistory = SkuHistory::byEventType(SkuHistory::EVENT_CREATED)->get();
    $regeneratedHistory = SkuHistory::byEventType(SkuHistory::EVENT_REGENERATED)->get();

    expect($createdHistory->count())->toBe(2)
        ->and($regeneratedHistory->count())->toBe(1);
})->uses(TestCase::class);

test('can query recent changes', function () {
    $product = HistoryTestProduct::create(['name' => 'Test Product']);

    // Create an old record
    SkuHistory::create([
        'model_type' => HistoryTestProduct::class,
        'model_id' => $product->id,
        'new_sku' => 'OLD-SKU',
        'event_type' => SkuHistory::EVENT_CREATED,
        'created_at' => now()->subDays(10),
    ]);

    $recentHistory = SkuHistory::recentChanges(7)->get();

    expect($recentHistory->count())->toBe(1);
})->uses(TestCase::class);

test('model has skuHistory relationship', function () {
    $product = HistoryTestProduct::create(['name' => 'Test Product']);

    $history = $product->skuHistory;

    expect($history)->toHaveCount(1)
        ->and($history->first())->toBeInstanceOf(SkuHistory::class);
})->uses(TestCase::class);

test('can get latest SKU history', function () {
    $product = HistoryTestProduct::create(['name' => 'Test Product']);
    $product->forceRegenerateSku();

    $latest = $product->getLatestSkuHistory();

    expect($latest)->toBeInstanceOf(SkuHistory::class)
        ->and($latest->event_type)->toBe(SkuHistory::EVENT_REGENERATED);
})->uses(TestCase::class);

test('respects history enabled configuration', function () {
    config(['sku-generator.history.enabled' => false]);

    $product = HistoryTestProduct::create(['name' => 'Test Product']);

    expect(SkuHistory::count())->toBe(0);
})->uses(TestCase::class);

test('SkuHistoryLogger service works correctly', function () {
    $logger = app(SkuHistoryLogger::class);
    $product = HistoryTestProduct::create(['name' => 'Test Product']);

    SkuHistory::truncate();

    $logger->logCreation($product, 'TEST-SKU');

    expect(SkuHistory::count())->toBe(1)
        ->and(SkuHistory::first()->new_sku)->toBe('TEST-SKU');
})->uses(TestCase::class);

test('cleanup removes old records', function () {
    $product = HistoryTestProduct::create(['name' => 'Test Product']);

    // Create old records
    SkuHistory::create([
        'model_type' => HistoryTestProduct::class,
        'model_id' => $product->id,
        'new_sku' => 'OLD-SKU-1',
        'event_type' => SkuHistory::EVENT_CREATED,
        'created_at' => now()->subDays(400),
    ]);

    SkuHistory::create([
        'model_type' => HistoryTestProduct::class,
        'model_id' => $product->id,
        'new_sku' => 'OLD-SKU-2',
        'event_type' => SkuHistory::EVENT_CREATED,
        'created_at' => now()->subDays(200),
    ]);

    expect(SkuHistory::count())->toBe(3);

    // Cleanup records older than 365 days
    $deleted = SkuHistory::where('created_at', '<', now()->subDays(365))->delete();

    expect($deleted)->toBe(1)
        ->and(SkuHistory::count())->toBe(2);
})->uses(TestCase::class);

test('sku:history command displays history', function () {
    $product = HistoryTestProduct::create(['name' => 'Test Product']);

    $this->artisan('sku:history', [
        'model' => HistoryTestProduct::class,
        '--id' => $product->id,
    ])
        ->assertSuccessful()
        ->expectsOutput("Showing history for " . HistoryTestProduct::class . " ID: {$product->id}");
})->uses(TestCase::class);

test('sku:history:cleanup command works', function () {
    $product = HistoryTestProduct::create(['name' => 'Test Product']);

    // Create old record
    SkuHistory::create([
        'model_type' => HistoryTestProduct::class,
        'model_id' => $product->id,
        'new_sku' => 'OLD-SKU',
        'event_type' => SkuHistory::EVENT_CREATED,
        'created_at' => now()->subDays(400),
    ]);

    $this->artisan('sku:history:cleanup', [
        '--days' => 365,
        '--force' => true,
    ])
        ->assertSuccessful();

    expect(SkuHistory::count())->toBe(1); // Only the recent one remains
})->uses(TestCase::class);

class HistoryTestProduct extends Model
{
    use HasSku;

    protected $fillable = ['name', 'sku'];
    protected $table = 'test_products';
}

<?php

namespace Gowelle\SkuGenerator;

use Gowelle\SkuGenerator\Contracts\SkuGeneratorContract;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

/**
 * Service Provider for the SKU Generator package.
 * 
 * This provider handles:
 * - Package configuration
 * - Command registration
 * - Service container bindings
 * - Facade registration
 * 
 * @extends PackageServiceProvider
 */
class SkuGeneratorServiceProvider extends PackageServiceProvider
{
    /**
     * Configure the SKU Generator package.
     * 
     * Sets up:
     * - Package name and config
     * - Artisan commands
     * - Installation command
     * 
     * @param Package $package The package configuration object
     */
    public function configurePackage(Package $package): void
    {
        $package
            ->name('sku-generator')
            ->hasConfigFile('sku-generator')
            ->hasMigration('create_sku_histories_table')
            ->hasCommands([
                \Gowelle\SkuGenerator\Console\SkuRegenerateCommand::class,
                \Gowelle\SkuGenerator\Console\SkuHistoryCommand::class,
                \Gowelle\SkuGenerator\Console\SkuHistoryCleanupCommand::class,
            ])
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile('sku-generator')
                    ->publishMigrations()
                    ->copyAndRegisterServiceProviderInApp();
            });
    }

    /**
     * Register package bindings in the container.
     * 
     * Binds:
     * - Contract to implementation
     * - Facade singleton
     */
    public function registeringPackage()
    {
        $this->app->bind(SkuGeneratorContract::class, SkuGenerator::class);

        $this->app->singleton('gowelle.sku-generator', function ($app) {
            return new SkuGenerator;
        });
    }
}

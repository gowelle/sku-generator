<?php

namespace Gowelle\SkuGenerator;

use Gowelle\SkuGenerator\Contracts\SkuGeneratorContract;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class SkuGeneratorServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('sku-generator')
            ->hasConfigFile('sku-generator')
            ->hasCommand(\Gowelle\SkuGenerator\Console\SkuRegenerateCommand::class)
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile('sku-generator')
                    ->copyAndRegisterServiceProviderInApp();
            });
    }

    public function registeringPackage()
    {
        $this->app->bind(SkuGeneratorContract::class, SkuGenerator::class);

        $this->app->singleton('gowelle.sku-generator', function ($app) {
            return new SkuGenerator;
        });
    }
}

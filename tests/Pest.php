<?php

use Gowelle\SkuGenerator\SkuGeneratorServiceProvider;

uses(Orchestra\Testbench\TestCase::class)->in(__DIR__);

function getPackageProviders($app)
{
    return [SkuGeneratorServiceProvider::class];
}

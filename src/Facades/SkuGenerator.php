<?php

namespace Gowelle\SkuGenerator\Facades;

use Illuminate\Support\Facades\Facade;

class SkuGenerator extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'gowelle.sku-generator';
    }
}

<?php

namespace Gowelle\SkuGenerator\Exceptions;

class InvalidSkuMappingException extends \Exception
{
    public static function forModel(string $modelClass): self
    {
        return new self("No SKU generator mapping defined for {$modelClass}");
    }

    public static function forType(string $type, string $modelClass): self
    {
        return new self("Unsupported SKU type '{$type}' for {$modelClass}");
    }
}

<?php

namespace Gowelle\SkuGenerator\Exceptions;

/**
 * Exception thrown when SKU mapping configuration is invalid.
 *
 * This exception handles two main error cases:
 * 1. When a model class is not mapped in the configuration
 * 2. When an invalid SKU type is specified for a model
 */
class InvalidSkuMappingException extends \Exception
{
    /**
     * Create an exception for an unmapped model.
     *
     * Used when attempting to generate a SKU for a model that
     * is not defined in the configuration's model mapping.
     *
     * @param string $modelClass The fully qualified model class name
     * @return static
     */
    public static function forModel(string $modelClass): self
    {
        return new self("No SKU generator mapping defined for {$modelClass}");
    }

    /**
     * Create an exception for an invalid SKU type.
     *
     * Used when a model is mapped to an unsupported SKU type.
     * Valid types are 'product' and 'variant'.
     *
     * @param string $type The invalid SKU type that was provided
     * @param string $modelClass The model class that was being processed
     * @return static
     */
    public static function forType(string $type, string $modelClass): self
    {
        return new self("Unsupported SKU type '{$type}' for {$modelClass}");
    }
}

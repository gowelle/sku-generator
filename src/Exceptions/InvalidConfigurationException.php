<?php

namespace Gowelle\SkuGenerator\Exceptions;

/**
 * Exception thrown when SKU Generator configuration is invalid.
 *
 * This exception is used when required configuration keys are missing
 * or when configuration values are invalid.
 */
class InvalidConfigurationException extends \Exception
{
    /**
     * Create exception for missing configuration key.
     *
     * @param string $key The missing configuration key
     * @return static
     */
    public static function forMissingKey(string $key): self
    {
        return new self("Missing required configuration key: {$key}");
    }

    /**
     * Create exception for missing configuration section.
     *
     * @param string $section The missing configuration section name
     * @return static
     */
    public static function forMissingSection(string $section): self
    {
        return new self("Missing required configuration section: {$section}");
    }

    /**
     * Create exception for invalid model type.
     *
     * @param string $type The invalid model type provided
     * @return static
     */
    public static function forInvalidModelType(string $type): self
    {
        return new self("Invalid model type: {$type}. Must be 'product' or 'variant'");
    }

    /**
     * Create exception for empty models configuration.
     *
     * @return static
     */
    public static function forEmptyModels(): self
    {
        return new self('At least one model must be configured');
    }
}
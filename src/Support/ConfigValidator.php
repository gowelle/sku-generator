<?php

namespace Gowelle\SkuGenerator\Support;

use Gowelle\SkuGenerator\Exceptions\InvalidConfigurationException;

/**
 * Validates SKU Generator configuration.
 * 
 * This class ensures that all required configuration values are present
 * and valid before the SKU generator is used. It checks:
 * 
 * - Required top-level keys (prefix, length settings, etc.)
 * - Nested category configuration
 * - Model type mappings
 * 
 * @throws InvalidConfigurationException When configuration is invalid
 */
class ConfigValidator
{
    /**
     * Required configuration structure.
     * 
     * Format:
     * - String keys for simple required values
     * - Array keys for nested configuration sections
     */
    private const REQUIRED_CONFIG = [
        'prefix',
        'ulid_length',
        'separator',
        'models',
        'category' => [
            'accessor',
            'field',
            'length',
            'has_many'
        ]
    ];

    /**
     * Validate the provided configuration array.
     * 
     * Checks that:
     * 1. All required keys are present
     * 2. Nested configuration sections are complete
     * 3. Model mappings are valid
     * 4. At least one model is configured
     * 
     * @param array $config The configuration array to validate
     * @throws InvalidConfigurationException When any validation fails
     */
    public static function validate(array $config): void
    {
        foreach (self::REQUIRED_CONFIG as $key => $value) {
            if (is_array($value)) {
                if (!isset($config[$key]) || !is_array($config[$key])) {
                    throw InvalidConfigurationException::forMissingSection($key);
                }
                foreach ($value as $subKey) {
                    if (!isset($config[$key][$subKey])) {
                        throw InvalidConfigurationException::forMissingKey("{$key}.{$subKey}");
                    }
                }
            } elseif (!isset($config[$value])) {
                throw InvalidConfigurationException::forMissingKey($value);
            }
        }

        if (empty($config['models'])) {
            throw InvalidConfigurationException::forEmptyModels();
        }

        foreach ($config['models'] as $model => $type) {
            if (!in_array($type, ['product', 'variant'])) {
                throw InvalidConfigurationException::forInvalidModelType($type);
            }
        }
    }
}

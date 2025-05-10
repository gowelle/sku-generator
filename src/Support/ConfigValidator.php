<?php

namespace Gowelle\SkuGenerator\Support;

class ConfigValidator
{
    public static function validate(array $config): void
    {
        $required = ['prefix', 'product_category_length', 'ulid_length', 'models'];

        foreach ($required as $key) {
            if (! isset($config[$key])) {
                throw new \InvalidArgumentException("Missing required config key: {$key}");
            }
        }

        if (! is_array($config['models'])) {
            throw new \InvalidArgumentException("Config key 'models' must be an array");
        }
    }
}

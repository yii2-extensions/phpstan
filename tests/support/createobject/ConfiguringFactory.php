<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\createobject;

use yii\di\Container;

/**
 * Factory bound in the container of the analysis configuration that applies the configuration it receives.
 */
final class ConfiguringFactory
{
    /**
     * @param array<array-key, mixed> $params
     * @param array<string, mixed> $config
     */
    public static function create(Container $container, array $params, array $config): Target
    {
        return new Target($config);
    }
}

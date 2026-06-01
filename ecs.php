<?php

declare(strict_types=1);

/** @var \Symplify\EasyCodingStandard\Configuration\ECSConfigBuilder $builder */
$builder = require __DIR__ . '/vendor/php-forge/coding-standard/src/ecs-83.php';

return $builder->withPaths(
    [
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ],
);

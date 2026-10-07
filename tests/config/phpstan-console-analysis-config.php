<?php

declare(strict_types=1);

// Console configuration of this repository's own analysis ('phpstan-console.neon'). PHPStan validates
// 'stubs/yii.stub' here, since it lies outside the vendor directory, and reports the plain `array` that
// `Module::$params` resolves to without params at level max, so params are declared.

/** @var array<string, mixed> $config */
$config = require __DIR__ . '/phpstan-console-config.php';

$config['params'] = ['adminEmail' => 'admin@example.com'];

return $config;

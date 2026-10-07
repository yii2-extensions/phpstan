<?php

declare(strict_types=1);

/** @var array<string, array<string, mixed>> $config */
$config = require __DIR__ . '/base.php';

$config['params']['maxItems'] = '100';

return $config;

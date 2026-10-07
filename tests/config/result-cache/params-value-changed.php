<?php

declare(strict_types=1);

/** @var array<string, array<string, mixed>> $config */
$config = require __DIR__ . '/base.php';

$config['params']['adminEmail'] = 'other@example.com';
$config['params']['callback'] = static fn(): string => 'other';

return $config;

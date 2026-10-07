<?php

declare(strict_types=1);

/** @var array<string, array<string, mixed>> $config */
$config = require __DIR__ . '/base.php';

$config['components']['cache'] = ['class' => 'yii\\caching\\ApcCache'];

return $config;

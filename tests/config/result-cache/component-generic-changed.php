<?php

declare(strict_types=1);

/** @var array<string, array<string, mixed>> $config */
$config = require __DIR__ . '/base.php';

$config['components']['user'] = ['class' => 'yii\\web\\User', 'identityClass' => 'app\\models\\Admin'];

return $config;

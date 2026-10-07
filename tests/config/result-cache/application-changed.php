<?php

declare(strict_types=1);

/** @var array<string, array<string, mixed>> $config */
$config = require __DIR__ . '/base.php';

$config['phpstan']['application_type'] = 'yii\\console\\Application';

return $config;

<?php

declare(strict_types=1);

/** @var array<string, array<string, mixed>> $config */
$config = require __DIR__ . '/base.php';

$config['behaviors']['app\\models\\Post'] = ['app\\behaviors\\TimestampBehavior', 'app\\behaviors\\SlugBehavior'];

return $config;

<?php

declare(strict_types=1);

error_reporting(-1);

defined('YII_DEBUG') || define('YII_DEBUG', true);
define('YII_ENABLE_ERROR_HANDLER', false);
define('YII_ENV', 'test');

// require composer autoloader if available
require(dirname(__DIR__) . '/vendor/autoload.php');
require(dirname(__DIR__) . '/vendor/yiisoft/yii2/Yii.php');

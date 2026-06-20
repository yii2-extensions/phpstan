<?php

declare(strict_types=1);

error_reporting(-1);

// root directory of the project
$rootDir = dirname(__DIR__, 2);

// shared Yii environment constants (same definitions shipped to consumers via 'extension.neon')
require_once "{$rootDir}/bootstrap.php";

define('YII_ENABLE_ERROR_HANDLER', false);
define('YII_ENV', 'test');

// require composer autoloader if available
require "{$rootDir}/vendor/autoload.php";
require "{$rootDir}/vendor/yiisoft/yii2/Yii.php";

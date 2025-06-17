# Installation Guide

## System Requirements

- [`PHP`](https://www.php.net/downloads) 8.1 or higher.
- [`PHPStan`](https://github.com/phpstan/phpstan) 2.1 or higher.
- [`Yii2`](https://github.com/yiisoft/yii2) 2.0.52+(dev-master) or 22.x.

## Installation

### Method 1: Using [composer](https://getcomposer.org/download/) (Recommended)

Install the extension as a development dependency.

```bash
composer require --dev yii2-extensions/phpstan
```

### Method 2: Manual Installation

Add to your `composer.json`.

```json
{
    "require-dev": {
        "yii2-extensions/phpstan": "^0.2"
    }
}
```

Then run.

```bash
composer update
```

## Automatic Extension Installation

### Using PHPStan Extension Installer (Recommended)

The easiest way is to use the official PHPStan extension installer.

```bash
composer require --dev phpstan/extension-installer
```

Add the plugin configuration to your `composer.json`.

```json
{
    "require-dev": {
        "phpstan/extension-installer": "^1.4",
        "yii2-extensions/phpstan": "^0.2"
    },
    "config": {
        "allow-plugins": {
            "phpstan/extension-installer": true,
            "yiisoft/yii2-composer": true
        }
    }
}
```

With this setup, the extension will be automatically registered and you only need to configure the Yii2-specific settings.

### Manual Extension Registration

If you prefer manual control, include the extension in your `phpstan.neon`.

```neon
includes:
    - vendor/yii2-extensions/phpstan/extension.neon
```

## Basic Configuration

Create a `phpstan.neon` file in your project root.

```neon
includes:
    - vendor/yii2-extensions/phpstan/extension.neon

parameters:
    level: 5
    paths:
        - src
        - controllers
        - models
    
    yii2:
        config_path: config/phpstan.php
```

## Creating PHPStan Configuration File

Create a dedicated configuration file for PHPStan analysis. This should be separate from your main application configuration.

### Web Application Configuration

Create `config/phpstan.php`.

```php
<?php

declare(strict_types=1);

return [
    'phpstan' => [
        'application_type' => \yii\web\Application::class,
    ],
    'components' => [
        'db' => [
            'class' => \yii\db\Connection::class,
            'dsn' => 'sqlite::memory:',
        ],
        'user' => [
            'class' => \yii\web\User::class,
            'identityClass' => \app\models\User::class,
        ],
        'mailer' => [
            'class' => \yii\mail\MailerInterface::class,
        ],
        // Add your custom components here
        'customService' => [
            'class' => \app\services\CustomService::class,
        ],
    ],
    'container' => [
        'definitions' => [
            'logger' => \Psr\Log\LoggerInterface::class,
            'cache' => \yii\caching\CacheInterface::class,
        ],
        'singletons' => [
            'eventDispatcher' => \app\services\EventDispatcher::class,
        ],
    ],
];
```

### Console Application Configuration

For console applications, create `config/phpstan-console.php`.

```php
<?php

declare(strict_types=1);

return [
    'phpstan' => [
        'application_type' => \yii\console\Application::class,
    ],
    'components' => [
        'db' => [
            'class' => \yii\db\Connection::class,
            'dsn' => 'sqlite::memory:',
        ],
        // Console-specific components
    ],
];
```

And update your `phpstan.neon`.

```neon
parameters:
    yii2:
        config_path: config/phpstan-console.php
```

## Verification

Test your installation by running PHPStan:

```bash
vendor/bin/phpstan analyse
```

You should see output similar to.

```
PHPStan - PHP Static Analysis Tool
 [OK] No errors
```

### Test Type Inference

Create a simple test file to verify type inference is working.

```php
<?php
// test-phpstan.php

use yii\web\Application;

// This should be properly typed as Application
$app = \Yii::$app;

// This should show proper component types
$db = \Yii::$app->db;      // Connection
$user = \Yii::$app->user;  // User
```

Run PHPStan on this file.

```bash
vendor/bin/phpstan analyse test-phpstan.php --level=5
```

## Bootstrap Configuration

If your application requires custom bootstrap logic, create a bootstrap file:

```php
<?php
// tests/bootstrap.php
declare(strict_types=1);

error_reporting(-1);

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');

require(dirname(__DIR__) . '/vendor/yiisoft/yii2/Yii.php');
```

Reference it in your `phpstan.neon`.

```neon
parameters:
    bootstrapFiles:
        - tests/bootstrap.php
```

### Debugging Installation

Enable verbose output to see what's happening.

```bash
vendor/bin/phpstan --debug -vvv --error-format=table --memory-limit=1G
```

Check which extensions are loaded.

```bash
vendor/bin/phpstan --version
```

## Next Steps

Once installation is complete:

- 📖 Read the [Configuration Guide](configuration.md) for advanced settings

# Configuration Reference

## Overview

This guide covers all configuration options for the Yii2 PHPStan extension, from basic setup to advanced scenarios.

## Basic Configuration

### Minimal Setup

```neon
includes:
    - vendor/yii2-extensions/phpstan/extension.neon

parameters:
    level: 5

    paths:
        - src

    tmpDir: %currentWorkingDirectory%/tests/runtime        
    
    yii2:
        config_path: config/phpstan-config.php
```

### Standard Web Application

```neon
includes:
    - vendor/yii2-extensions/phpstan/extension.neon

parameters:
    bootstrapFiles:
        - tests/bootstrap.php

    excludePaths:
        - config/
        - runtime/
        - vendor/
        - web/assets/        

    level: 6

    paths:
        - controllers
        - models
        - widgets
        - components

    tmpDir: %currentWorkingDirectory%/tests/runtime

    yii2:
        config_path: config/phpstan-config.php
```

## Application Type Configuration

### Web Application (Default)

```php
<?php
// config/phpstan-config.php
return [
    'phpstan' => [
        'application_type' => \yii\web\Application::class,
    ],
    // ... other configuration
];
```

### Console Application

For console applications, you **must** explicitly specify the application type.

```php
<?php
// config/phpstan-console-config.php
return [
    'phpstan' => [
        'application_type' => \yii\console\Application::class,
    ],
    'components' => [
        'db' => [
            'class' => \yii\db\Connection::class,
            'dsn' => 'sqlite::memory:',
        ],
    ],
];
```

Use a separate PHPStan configuration for the console.

```neon
# phpstan-console.neon
includes:
    - vendor/yii2-extensions/phpstan/extension.neon

parameters:
    paths:
        - commands
        - console

    tmpDir: %currentWorkingDirectory%/tests/runtime        

    yii2:
        config_path: config/phpstan-console-config.php
```

## Dynamic Constants Configuration

### Default Constants

The extension automatically recognizes these Yii2 constants:

```neon
parameters:
    dynamicConstantNames:
        - YII_DEBUG
        - YII_ENV
        - YII_ENV_DEV
        - YII_ENV_PROD
        - YII_ENV_TEST
```

### Adding Custom Constants

⚠️ **Important**: When you define `dynamicConstantNames`, it **replaces** the defaults. Include Yii2 constants explicitly.

```neon
parameters:
    dynamicConstantNames:
        # Yii2 constants (must be included manually)
        - YII_DEBUG
        - YII_ENV
        - YII_ENV_DEV
        - YII_ENV_PROD
        - YII_ENV_TEST
        # Your custom constants
        - APP_VERSION
        - MAINTENANCE_MODE
        - FEATURE_FLAGS
```

## Service Map Configuration

### Component Configuration

Define your application components for proper type inference:

```php
<?php
// config/phpstan-config.php
return [
    'components' => [
        // Database
        'db' => [
            'class' => \yii\db\Connection::class,
            'dsn' => 'mysql:host=localhost;dbname=test',
        ],
        
        // User component with identity class
        'user' => [
            'class' => \yii\web\User::class,
            'identityClass' => \app\models\User::class,
            'loginUrl' => ['/site/login'],
        ],
        
        // Mailer
        'mailer' => [
            'class' => \yii\symfonymailer\Mailer::class,
            'transport' => [
                'scheme' => 'smtp',
                'host' => 'localhost',
            ],
        ],
        
        // Cache
        'cache' => [
            'class' => \yii\caching\FileCache::class,
            'cachePath' => '@runtime/cache',
        ],
        
        // Custom components
        'paymentService' => [
            'class' => \app\services\PaymentService::class,
            'apiKey' => 'test-key',
        ],
        
        // URL Manager
        'urlManager' => [
            'class' => \yii\web\UrlManager::class,
            'enablePrettyUrl' => true,
            'showScriptName' => false,
        ],
    ],
];
```

### Behavior Configuration

Configure behaviors for proper method and property reflection.

```php
<?php
return [
    'behaviors' => [
        \app\models\User::class => [
            \yii\behaviors\TimestampBehavior::class,
            \yii\behaviors\BlameableBehavior::class,
        ],
        \app\models\Post::class => [
            \yii\behaviors\SluggableBehavior::class,
            \app\behaviors\SeoOptimizedBehavior::class,
        ],
    ],
];
```

### Behavior PHPDoc Requirements

For accurate type inference, behaviors should define their properties using PHPDoc.

```php
<?php

use yii\base\Behavior;
use yii\db\ActiveRecord;

/**
 * @template T of ActiveRecord
 * @extends Behavior<T>
 *
 * @property int $depth
 * @property int $lft
 * @property int $rgt
 * @property int|false $tree
 */
class NestedSetsBehavior extends Behavior {}
```

### Container Configuration

Define DI container services.

```php
<?php
return [
    'container' => [
        'definitions' => [
            // Interface to implementation mapping
            \Psr\Log\LoggerInterface::class => \Monolog\Logger::class,
            \app\contracts\PaymentInterface::class => \app\services\StripePayment::class,
            
            // Service definitions
            'logger' => [
                'class' => \Monolog\Logger::class,
                ['name' => 'app'],
            ],
            
            // Closure definitions
            'eventDispatcher' => function() {
                return new \app\services\EventDispatcher();
            },
        ],
        
        'singletons' => [
            // Singleton services
            \app\services\CacheManager::class => \app\services\CacheManager::class,
            'metrics' => [
                'class' => \app\services\MetricsCollector::class,
                'enabled' => true,
            ],
        ],
    ],
];
```

## Advanced Configuration

### Strict Analysis Setup

```neon
includes:
    - phar://phpstan.phar/conf/bleedingEdge.neon
    - vendor/phpstan/phpstan-strict-rules/rules.neon
    - vendor/yii2-extensions/phpstan/extension.neon

parameters:
    excludePaths:
        - src/legacy/
        - tests/_support/
        - vendor/

    level: 8
    
    paths:
        - src
        - controllers
        - models
        - widgets
        - components

    tmpDir: %currentWorkingDirectory%/tests/runtime          

    yii2:
        config_path: config/phpstan-config.php

    # Strict checks
    checkImplicitMixed: true
    checkBenevolentUnionTypes: true
    checkUninitializedProperties: true
    checkMissingCallableSignature: true
    checkTooWideReturnTypesInProtectedAndPublicMethods: true
    reportAnyTypeWideningInVarTag: true
    reportPossiblyNonexistentConstantArrayOffset: true
    reportPossiblyNonexistentGeneralArrayOffset: true
    
    ignoreErrors:
        # Ignore specific errors
        - '#Call to an undefined method.*#'
        - '#Access to an undefined property.*#'
```

### Performance Optimization

```neon
parameters:   
    # Bootstrap optimization
    bootstrapFiles:
        - vendor/autoload.php
        - config/phpstan-bootstrap.php

    # Parallel processing
    parallel:
        jobSize: 20
        maximumNumberOfProcesses: 32
        minimumNumberOfJobsPerProcess: 2

    # Memory management
    tmpDir: %currentWorkingDirectory%/tests/runtime
```

Optimized bootstrap file.

```php
<?php
// config/phpstan-bootstrap.php
declare(strict_types=1);

error_reporting(-1);

// Define constants without full application bootstrap
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');

// Load only essential classes
require(dirname(__DIR__) . '/vendor/yiisoft/yii2/Yii.php');
```

## Configuration Without Config File

If you don't want to create a separate configuration file:

```neon
parameters:
    yii2:
        config_path: ''  # Disable config file
```

This will work with basic type inference but won't have custom component types.

## Multiple Application Types

For projects with both web and console applications:

### Project Structure
```text
phpstan-web.neon      # Web-specific configuration
phpstan-console.neon  # Console-specific configuration
phpstan.neon          # Base configuration
```

### Base Configuration
```neon
# phpstan.neon
includes:
    - vendor/yii2-extensions/phpstan/extension.neon

parameters:
    level: 6

    tmpDir: %currentWorkingDirectory%/tests/runtime
```

### Web Configuration
```neon
# phpstan-web.neon
includes:
    - phpstan.neon

parameters:
    paths:
        - controllers
        - models
        - widgets
        - web

    yii2:
        config_path: config/phpstan-config.php
```

### Console Configuration
```neon
# phpstan-console.neon
includes:
    - phpstan.neon

parameters:
    paths:
        - commands
        - console

    yii2:
        config_path: config/phpstan-console-config.php
```

### Usage
```bash
# Analyze web application
vendor/bin/phpstan analyse -c phpstan-web.neon

# Analyze console application  
vendor/bin/phpstan analyse -c phpstan-console.neon
```

### File-Level Suppression

```php
<?php
// In your PHP files
/** @phpstan-ignore-next-line */
$result = $someObject->unknownMethod();

/** @phpstan-ignore-line */
$property = $object->unknownProperty;
```

## Validation

Test your configuration:

```bash
# Check configuration syntax
vendor/bin/phpstan analyse --dry-run

# Validate service map
vendor/bin/phpstan analyse -v

# Generate baseline for existing errors
vendor/bin/phpstan analyse --generate-baseline
```

## Next Steps

- 💡 [Usage Examples](examples.md)

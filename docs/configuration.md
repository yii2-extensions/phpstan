# Configuration reference

## Overview

This guide covers all configuration options for the Yii PHPStan extension, from basic setup to advanced scenarios.

## Basic configuration

### Minimal setup

```neon
includes:
    - vendor/yii2-extensions/phpstan/extension.neon

parameters:
    level: 5

    paths:
        - src

    tmpDir: %currentWorkingDirectory%/runtime

    yii2:
        config_path: config/phpstan-config.php
```

### Standard web application

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

    tmpDir: %currentWorkingDirectory%/runtime

    yii2:
        config_path: config/phpstan-config.php
```

## Application type configuration

`Yii::$app`, also when read through `\yii\BaseYii::$app`, is typed as the class set in `phpstan.application_type`, or as
`yii\web\Application` when the key is omitted. The extension ships a static stub, `stubs/yii.stub`, registered through
`stubFiles` in `extension.neon`, that declares `yii\BaseYii::$app` and `yii\base\Module::$params` with placeholder PHPDoc
types; a PHPDoc type resolver of the extension (`ApplicationTypeNodeResolverExtension`) turns them into this class and
the array shape of the configured `params`, so nothing is generated at runtime.

### Web application (default)

```php
<?php

declare(strict_types=1);

// config/phpstan-config.php
return [
    'phpstan' => [
        'application_type' => \yii\web\Application::class,
    ],
    // ... other configuration
];
```

### Console application

For console applications, you **must** explicitly specify the application type.

```php
<?php

declare(strict_types=1);

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

    tmpDir: %currentWorkingDirectory%/runtime

    yii2:
        config_path: config/phpstan-console-config.php
```

## Dynamic constants configuration

### Default constants

The extension automatically recognizes these Yii constants:

```neon
parameters:
    dynamicConstantNames:
        - YII_DEBUG
        - YII_ENV
        - YII_ENV_DEV
        - YII_ENV_PROD
        - YII_ENV_TEST
```

> `dynamicConstantNames` controls how PHPStan _treats_ these constants during analysis (as non-constant values). It is
> independent of _defining_ them: the extension's shipped `bootstrap.php` defines `YII_DEBUG`, `YII_ENV_DEV`,
> `YII_ENV_PROD`, and `YII_ENV_TEST` with safe defaults before reading your configuration file, so configuration files
> that reference them load without a manual bootstrap.

### Adding custom constants

⚠️ **Important**: When you define `dynamicConstantNames`, it **replaces** the defaults. Include Yii constants explicitly.

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

## Service map configuration

### Component configuration

Define your application components for proper type inference. See [Definition forms](#definition-forms) for every
supported definition.

```php
<?php

declare(strict_types=1);

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

### Generic component configuration

You can configure which property in the component definition contains the generic type information.

#### Default generic components

The extension comes with pre-configured generic support for common Yii components:

```neon
parameters:
    yii2:
        component_generics:
            user: identityClass         # Built-in: User<IdentityClass>
```

#### Adding custom generic components

You can extend the generic configuration without overriding the defaults:

```neon
parameters:
    yii2:
        component_generics:
            userRepository: modelClass  # Add custom generic
            postCollection: elementType # Add another custom generic
```

#### Service configuration

```php
<?php

declare(strict_types=1);

// config/phpstan-config.php
return [
    'components' => [
        'user' => [
            'class' => \yii\web\User::class,
            'identityClass' => \app\models\User::class, // Generic type parameter
        ],
        'userRepository' => [
            'class' => \app\repositories\Repository::class,
            'modelClass' => \app\models\User::class,    // Generic type parameter
        ],
        'postCollection' => [
            'class' => \app\collections\Collection::class,
            'elementType' => \app\models\Post::class,   // Generic type parameter
        ],
    ],
];
```

#### Usage with proper type inference

```php
<?php

declare(strict_types=1);

use Yii;

class UserController
{
    public function actionProfile(): string
    {
        // ✅ PHPStan knows this is User<app\models\User>
        $user = Yii::$app->user;

        // ✅ PHPStan knows identity is app\models\User
        $identity = $user->identity;

        // ✅ PHPStan knows this is Repository<app\models\User>
        $repository = Yii::$app->userRepository;

        // ✅ PHPStan knows this is Collection<app\models\Post>
        $collection = Yii::$app->postCollection;

        return $this->render('profile', ['user' => $identity]);
    }
}
```

#### Creating generic-aware components

For your custom components to work with generics, define them using PHPDoc annotations:

```php
<?php

declare(strict_types=1);

namespace app\repositories;

use yii\base\Component;

/**
 * Generic repository component.
 *
 * @template T of \yii\db\ActiveRecord
 */
class Repository extends Component
{
    /**
     * @phpstan-var class-string<T>
     */
    public string $modelClass;

    /**
     * @phpstan-return T|null
     */
    public function findOne(int $id): \yii\db\ActiveRecord|null
    {
        return $this->modelClass::findOne($id);
    }

    /**
     * @phpstan-return T[]
     */
    public function findAll(): array
    {
        return $this->modelClass::find()->all();
    }
}
```

```php
<?php

declare(strict_types=1);

namespace app\collections;

use yii\base\Component;

/**
 * Generic collection component.
 *
 * @template T
 */
class Collection extends Component
{
    /**
     * @phpstan-var class-string<T>
     */
    public string $elementType;

    /**
     * @phpstan-var T[]
     */
    private array $items = [];

    /**
     * @phpstan-param T $item
     */
    public function add($item): void
    {
        $this->items[] = $item;
    }

    /**
     * @phpstan-return T[]
     */
    public function getAll(): array
    {
        return $this->items;
    }
}
```

### Behavior configuration

Configure behaviors for proper method and property reflection.

```php
<?php

declare(strict_types=1);

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

### Behavior PHPDoc requirements

For accurate type inference, behaviors should define their properties using PHPDoc.

```php
<?php

declare(strict_types=1);

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

### Container configuration

Define DI container services.

```php
<?php

declare(strict_types=1);

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

            // Closure definitions, typed from their class return type
            'eventDispatcher' => function(): \app\services\EventDispatcher {
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

### Definition forms

Components and container services accept every definition form Yii accepts, and each one is typed as its class when
that class can be determined from the configuration file:

- A class name string, with or without a leading backslash.
- An array with a `class` or `__class` key.
- A closure with a single class return type, such as `static fn(): View => new View()`. For container services, an
  object with an `__invoke()` method is resolved from that method's return type in the same way.
- Any other object except a `yii\di\Instance` reference, typed as its own class.
- For container services, a `[definition, params]` list, resolved from its first element, and an empty definition, or
  an array without a class key, under an ID that names an instantiable class, whether or not it extends
  `yii\base\BaseObject`, resolved to that ID. A list holding only a definition, such as `[['class' => Foo::class]]`,
  isn't unwrapped, as in Yii, which rejects it.
- A class name that is another container ID, as a string definition or as the `class` or `__class` of an array
  definition, resolved through that ID's definition, following chains, as `Yii::$container->get()` does.
  `container.definitions` and `container.singletons` share one ID space, so either can name an ID from the other, and a
  component definition can name a container ID too. The return type of a closure and the class of any other object
  are final and aren't resolved through the container, since Yii returns those values as they are. An ID on a cycle,
  or one that names an ID whose class can't be determined, is itself treated as a definition whose class can't be
  determined, so `get()` returns `object` for it.

When an ID appears in both `container.definitions` and `container.singletons`, the entry Yii applies last wins, as in
`Yii::$container`: the subsection listed later under `container` replaces the earlier one, even when its class can't be
determined.

A definition whose class can't be determined doesn't stop the analysis. This includes a closure without a return type or
with a union, nullable, or builtin return type, a `yii\di\Instance` reference, an array callable, and an array without a
class key. `get('id')` then returns `object`, also when the ID is a class name, since the definition decides what the
container returns, and for a component `Yii::$app->id` is `object` too, unless the application class declares that
property, as `yii\web\Application` does for `request`. Add a class return type to the closure to get the precise type.

```php
<?php

declare(strict_types=1);

// config/phpstan-config.php
return [
    'components' => [
        'stringView' => \yii\web\View::class,
        'closureView' => static fn(): \yii\web\View => new \yii\web\View(),
        'untypedClosure' => static fn() => new \yii\web\View(),
        'request' => [
            'cookieValidationKey' => 'secret',
        ],
    ],
    'container' => [
        'definitions' => [
            'mailer.real' => ['class' => \yii\web\View::class],
            'mailer.alias' => 'mailer.real',
        ],
    ],
];

// Usage
$stringView = Yii::$app->stringView;             // yii\web\View
$closureView = Yii::$app->get('closureView');    // yii\web\View
$untyped = Yii::$app->untypedClosure;            // object
$request = Yii::$app->request;                   // yii\web\Request
$requestById = Yii::$app->get('request');        // object
$aliased = Yii::$container->get('mailer.alias'); // yii\web\View
```

A definition that is an integer, float, or boolean still stops the analysis with `Unsupported definition for '<id>'.`,
and so do a non-string ID and a section that isn't an array. The exception
`Please provide return type for '<id>' service closure.` was removed in 0.5.0.

## Advanced configuration

### Strict analysis setup

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

    tmpDir: %currentWorkingDirectory%/runtime

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

### Performance optimization

PHPStan 2.3 ships its native Turbo engine inside the `phpstan/phpstan` package; it activates automatically on PHP 8.3 or
higher and needs no configuration from this extension. Run `vendor/bin/phpstan diagnose` to check its status.

You don't need to clear the result cache in `tmpDir` (`--clear-result-cache`) when the Yii configuration changes. The
extension declares through PHPStan's dependency tracking the configuration values that inferred types depend on, so
editing the configuration file, or a file it pulls in with `require` such as a params file, re-analyzes only the files
that depend on a changed value. A component or container service by id and the behaviors of a class are tracked for the
files that read them. The application class and the params type are tracked on `yii\BaseYii` and `yii\base\Module`,
which declare `Yii::$app` and `Module::$params`, so a change to either re-analyzes every file that depends on those
classes or on a subclass, including files that only override or inherit the properties. A change that leaves those
values as they were, such as a param value of the same type, re-analyzes nothing.

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
    tmpDir: %currentWorkingDirectory%/runtime
```

Optimized bootstrap file.

```php
<?php

declare(strict_types=1);

// config/phpstan-bootstrap.php
error_reporting(-1);

// Define constants without a full application bootstrap
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');

// Load only essential classes
require(dirname(__DIR__) . '/vendor/yiisoft/yii2/Yii.php');
```

## Configuration without a config file

If you don't want to create a separate configuration file:

```neon
parameters:
    yii2:
        config_path: ''  # Disable config file
```

This will work with basic type inference but won't have custom component types. `Yii::$app` is typed as
`yii\web\Application`, and `Module::$params` as `array`.

## Multiple application types

For projects with both web and console applications:

### Project structure

```text
phpstan-web.neon      # Web-specific configuration
phpstan-console.neon  # Console-specific configuration
phpstan.neon          # Base configuration
```

### Base configuration

```neon
# phpstan.neon
includes:
    - vendor/yii2-extensions/phpstan/extension.neon

parameters:
    level: 6

    tmpDir: %currentWorkingDirectory%/runtime
```

### Web configuration

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

### Console configuration

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

### File-Level suppression

```php
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

## Next steps

- 💡 [Usage Examples](examples.md)
- 🧪 [Testing Guide](testing.md)

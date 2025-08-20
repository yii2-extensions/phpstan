<p align="center">
    <a href="https://github.com/yii2-extensions/phpstan" target="_blank">
        <img src="https://www.yiiframework.com/image/yii_logo_light.svg" alt="Yii Framework">
    </a>
    <h1 align="center">Extension for PHPStan</h1>
</p>

<p align="center">
    <a href="https://www.php.net/releases/8.1/en.php" target="_blank">
        <img src="https://img.shields.io/badge/PHP-%3E%3D8.1-787CB5" alt="PHP Version">
    </a>
    <a href="https://github.com/yiisoft/yii2/tree/2.0.53" target="_blank">
        <img src="https://img.shields.io/badge/Yii2%20-2.0.53-blue" alt="Yii2 22.0.53">
    </a>
    <a href="https://github.com/yiisoft/yii2/tree/22.0" target="_blank">
        <img src="https://img.shields.io/badge/Yii2%20-22-blue" alt="Yii2 22.0">
    </a>    
    <a href="https://github.com/yii2-extensions/phpstan/actions/workflows/build.yml" target="_blank">
        <img src="https://github.com/yii2-extensions/phpstan/actions/workflows/build.yml/badge.svg" alt="PHPUnit">
    </a>
    <a href="https://github.com/yii2-extensions/phpstan/actions/workflows/static.yml" target="_blank">        
        <img src="https://github.com/yii2-extensions/phpstan/actions/workflows/static.yml/badge.svg" alt="Static Analysis">
    </a>          
</p>

A comprehensive PHPStan extension that provides enhanced static analysis for Yii2 applications with precise type 
inference, dynamic method resolution, and comprehensive property reflection.

## Features

✅ **ActiveRecord & ActiveQuery Analysis**
- Array/object result type inference based on `asArray()` usage.
- Dynamic return type inference for `find()`, `findOne()`, `findAll()` methods.
- Generic type support for `ActiveQuery<Model>` with proper chaining.
- Hierarchical type resolution: model properties take precedence over behavior properties.
- Precise type inference for `getAttribute()` method calls based on PHPDoc annotations.
- Property type resolution from both model classes and attached behaviors.
- Relation methods (`hasOne()`, `hasMany()`) with accurate return types.
- Support for behavior property definitions through ServiceMap integration.

✅ **Application Component Resolution**
- Automatic type inference for `Yii::$app->component` access.
- Behavior property and method reflection.
- Generic component support with configurable type parameters.
- Non-destructive generic configuration - extend without overriding defaults.
- Support for custom component configurations.
- User component with `identity`, `id`, `isGuest` property types.

✅ **Behavior Integration**
- Behavior configuration via ServiceMap (see the Behaviors section below).
- Hierarchical type resolution: model properties take precedence over behavior properties.
- Property and method resolution from attached behaviors.

✅ **Dependency Injection Container**
- Service map integration for custom services.
- Support for closures, singletons, and nested definitions.
- Type-safe `Container::get()` method resolution.

✅ **Framework Integration**
- Header collection dynamic method types.
- Stub files for different application types (web, console, base).
- Support for Yii2 constants (`YII_DEBUG`, `YII_ENV_*`).

✅ **Service Locator Component Resolution**
- Automatic fallback to mixed type for unknown component identifiers.
- Dynamic return type inference for `ServiceLocator::get()` calls.
- Priority-based resolution: ServiceMap components > ServiceMap services > Real classes > Mixed type.
- Support for all Service Locator subclasses (Application, Module, custom classes).
- Type inference with string variables and class name constants.

## Quick start

### Installation

```bash
composer require --dev yii2-extensions/phpstan
```

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

    tmpDir: %currentWorkingDirectory%/runtime        
    
    yii2:
        config_path: config/phpstan-config.php
        component_generics:
            user: identityClass      # Built-in (already configured)
            repository: modelClass   # Custom generic component        
```

Create a PHPStan-specific config file (`config/phpstan-config.php`).

```php
<?php

declare(strict_types=1);

return [
    // PHPStan only: used by this extension for behavior property/method type inference
    'behaviors' => [
        app\models\User::class => [
            app\behaviors\SoftDeleteBehavior::class,
            yii\behaviors\TimestampBehavior::class,
        ],
    ],    
    'components' => [
        'db' => [
            'class' => yii\db\Connection::class,
            'dsn' => 'sqlite::memory:',
        ],
        'user' => [
            'class' => yii\web\User::class,
            'identityClass' => app\models\User::class,
        ],
        // Add your custom components here
    ],
];
```

Run `PHPStan`.

```bash
vendor/bin/phpstan analyse
```

### Type inference examples

#### Active Record

```php
// ✅ Typed as User|null
$user = User::findOne(1);

// ✅ Typed as User[]
$users = User::findAll(['status' => 'active']);

// ✅ Generic ActiveQuery<User> with method chaining
$query = User::find()->where(['active' => 1])->orderBy('name');

// ✅ Array results typed as array{id: int, name: string}[]
$userData = User::find()->asArray()->all();

// ✅ Typed based on model property annotations string
$userName = $user->getAttribute('name');

// ✅ Behavior property resolution string
$slug = $user->getAttribute('slug');
```

#### Application components

```php
// ✅ Typed based on your configuration
$mailer = Yii::$app->mailer; // MailerInterface
$db = Yii::$app->db;         // Connection
$user = Yii::$app->user;     // User

// ✅ User identity with proper type inference
if (Yii::$app->user->isGuest === false) {
    $userId = Yii::$app->user->id;           // int|string|null
    $identity = Yii::$app->user->identity;   // YourUserClass
}
```

#### Behaviors

```php
// Behaviors are attached via the phpstan-config.php behaviors map (PHPStan only)

/**
 * @property string $slug
 * @property-read int $created_at
 * 
 * Note: `created_at` is provided by `TimestampBehavior`.
 */
class SoftDeleteBehavior extends \yii\base\Behavior
{
    public function softDelete(): bool { /* ... */ }
}

// ✅ Typed based on your configuration
$user = new User();

// ✅ Typed as string (inferred from behavior property)
$slug = $user->getAttribute('slug');

// ✅ Direct property access is also inferred (behavior property)
$slug2 = $user->slug;

// ✅ Typed as int (inferred from behavior property)
$createdAt = $user->getAttribute('created_at');

// ✅ Typed as bool (method defined in attached behavior)
$result = $user->softDelete();
```

#### Dependency injection

```php
$container = new Container();

// ✅ Type-safe service resolution
$service = $container->get(MyService::class); // MyService
$logger = $container->get('logger');          // LoggerInterface (if configured) or mixed
```

#### Header collection

```php
$headers = new HeaderCollection();

// ✅ Typed as string|null
$host = $headers->get('Host');

// ✅ Typed as array<int, string>
$forwardedFor = $headers->get('X-Forwarded-For', ['127.0.0.1'], false);

// ✅ Dynamic return type inference with mixed default
$default = 'default-value';
$requestId = $headers->get('X-Request-ID', $default, true); // string|null
$allRequestIds = $headers->get('X-Request-ID', $default, false); // array<int, string>|null
```

#### Service locator

```php
$serviceLocator = new ServiceLocator();

// ✅ Get component with type inference with class
$mailer = $serviceLocator->get(Mailer::class);  // MailerInterface

// ✅ Get component with string identifier and without configuration in ServiceMap
$mailer = $serviceLocator->get('mailer');  // MailerInterface (if configured) or mixed

// ✅ User component with proper type inference in Action or Controller
$user = $this->controller->module->get('user'); // UserInterface
```

## Documentation

For detailed configuration options and advanced usage.

- 📚 [Installation Guide](docs/installation.md)
- ⚙️ [Configuration Reference](docs/configuration.md)
- 💡 [Usage Examples](docs/examples.md)
- 🧪 [Testing Guide](docs/testing.md)

## Quality code

[![Latest Stable Version](https://poser.pugx.org/yii2-extensions/phpstan/v)](https://github.com/yii2-extensions/phpstan/releases)
[![Total Downloads](https://poser.pugx.org/yii2-extensions/phpstan/downloads)](https://packagist.org/packages/yii2-extensions/phpstan)
[![codecov](https://codecov.io/gh/yii2-extensions/phpstan/graph/badge.svg?token=DRcXAg3WGL)](https://codecov.io/gh/yii2-extensions/phpstan)
[![phpstan-level](https://img.shields.io/badge/PHPStan%20level-max-blue)](https://github.com/yii2-extensions/phpstan/actions/workflows/static.yml)
[![style-ci](https://github.styleci.io/repos/701347895/shield?branch=main)](https://github.styleci.io/repos/701347895?branch=main)

## Our social networks

[![X](https://img.shields.io/badge/follow-@terabytesoftw-1DA1F2?logo=x&logoColor=1DA1F2&labelColor=555555&style=flat)](https://x.com/Terabytesoftw)

## License

[![License](https://img.shields.io/github/license/yii2-extensions/phpstan)](LICENSE.md)

## Fork 

This package is a fork of [proget-hq/phpstan-yii2](https://github.com/proget-hq/phpstan-yii2) with some corrections.

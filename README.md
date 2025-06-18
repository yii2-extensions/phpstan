<p align="center">
    <a href="https://github.com/yii2-extensions/phpstan" target="_blank">
        <img src="https://www.yiiframework.com/image/yii_logo_light.svg" height="100px;">
    </a>
    <h1 align="center">Extension for PHPStan</h1>
</p>

<p align="center">
    <a href="https://www.php.net/releases/8.1/en.php" target="_blank">
        <img src="https://img.shields.io/badge/PHP-%3E%3D8.1-787CB5" alt="PHP-Version">
    </a>
    <a href="https://github.com/yiisoft/yii2/tree/2.0.52" target="_blank">
        <img src="https://img.shields.io/badge/Yii2%20-2.0.52-blue" alt="Yii-22.0.52">
    </a>
    <a href="https://github.com/yiisoft/yii2/tree/22.0" target="_blank">
        <img src="https://img.shields.io/badge/Yii2%20-22-blue" alt="Yii2-22">
    </a>    
    <a href="https://github.com/yii2-extensions/phpstan/actions/workflows/build.yml" target="_blank">
        <img src="https://github.com/yii2-extensions/phpstan/actions/workflows/build.yml/badge.svg" alt="PHPUnit">
    </a>
    <a href="https://github.com/yii2-extensions/phpstan/actions/workflows/static.yml" target="_blank">        
        <img src="https://github.com/yii2-extensions/phpstan/actions/workflows/static.yml/badge.svg" alt="Static-Analysis">
    </a>          
    <a href="https://codecov.io/gh/yii2-extensions/phpstan" target="_blank">
        <img src="https://codecov.io/gh/yii2-extensions/phpstan/branch/main/graph/badge.svg?token=MF0XUGVLYC" alt="Codecov">
    </a>  
</p>

A comprehensive PHPStan extension that provides enhanced static analysis for Yii2 applications with precise type 
inference, dynamic method resolution, and comprehensive property reflection.

## Installation

```bash
composer require --dev yii2-extensions/phpstan
```

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
- Support for custom component configurations.
- User component with `identity`, `id`, `isGuest` property types.

✅ **Dependency Injection Container**
- Service map integration for custom services.
- Support for closures, singletons, and nested definitions.
- Type-safe `Container::get()` method resolution.

✅ **Framework Integration**
- Header collection dynamic method types.
- Stub files for different application types (web, console, base).
- Support for Yii2 constants (`YII_DEBUG`, `YII_ENV_*`).

## Quick Start

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

    tmpDir: %currentWorkingDirectory%/tests/runtime        
    
    yii2:
        config_path: config/phpstan-config.php
```

Create a PHPStan-specific config file (`config/phpstan-config.php`).

```php
<?php
return [
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

## Type Inference Examples

### ActiveRecord

```php
// ✅ Properly typed as User|null
$user = User::findOne(1);

// ✅ Properly typed as User[]
$users = User::findAll(['status' => 'active']);

// ✅ Generic ActiveQuery<User> with method chaining
$query = User::find()->where(['active' => 1])->orderBy('name');

// ✅ Array results properly typed as array{id: int, name: string}[]
$userData = User::find()->asArray()->all();

// ✅ Properly typed based on model property annotations string
$userName = $user->getAttribute('name');

// ✅ Behavior property resolution string
$slug = $user->getAttribute('slug');
```

### Application Components

```php
// ✅ Properly typed based on your configuration
$mailer = Yii::$app->mailer; // MailerInterface
$db = Yii::$app->db;         // Connection
$user = Yii::$app->user;     // User

// ✅ User identity with proper type inference
if (Yii::$app->user->isGuest === false) {
    $userId = Yii::$app->user->id;           // int|string|null
    $identity = Yii::$app->user->identity;   // YourUserClass
}
```

### Dependency Injection

```php
$container = new Container();

// ✅ Type-safe service resolution
$service = $container->get(MyService::class); // MyService
$logger = $container->get('logger');          // LoggerInterface (if configured)
```

## Documentation

For detailed configuration options and advanced usage.

- 📚 [Installation Guide](docs/installation.md)
- ⚙️ [Configuration Reference](docs/configuration.md)
- 💡 [Usage Examples](docs/examples.md)

## Quality code

[![phpstan-level](https://img.shields.io/badge/PHPStan%20level-max-blue)](https://github.com/yii2-extensions/phpstan/actions/workflows/static.yml)
[![style-ci](https://github.styleci.io/repos/701347895/shield?branch=main)](https://github.styleci.io/repos/701347895?branch=main)

## Testing

[Check the documentation testing](docs/testing.md) to learn about testing.

## Our social networks

[![Twitter](https://img.shields.io/badge/twitter-follow-1DA1F2?logo=twitter&logoColor=1DA1F2&labelColor=555555?style=flat)](https://twitter.com/Terabytesoftw)

## License

BSD-3-Clause license. Please see [License File](LICENSE.md) for more information.

## Fork 

This package is a fork of [proget-hq/phpstan-yii2](https://github.com/proget-hq/phpstan-yii2) with some corrections.

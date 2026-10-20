# Installation guide

## Requirements

The package declares these constraints in `composer.json`:

| Dependency | Constraint               |
| ---------- | ------------------------ |
| PHP        | `>=8.3`                  |
| PHPStan    | `^2.3`                   |
| Yii        | `~2.0.55 \|\| ^22.0@dev` |
| PHP Parser | `^4.1 \|\| ^5.4`         |

The selected Yii release may require a newer PHP version.

## Choose a version

Install the extension as a development dependency:

```bash
composer require --dev yii2-extensions/phpstan:^0.5
```

See [migration](migration.md) when updating an existing integration.

## Register the extension

Choose one registration method. For manual registration, add this to the application's `phpstan.neon`:

```neon
includes:
    - vendor/yii2-extensions/phpstan/extension.neon

parameters:
    level: 5
    paths:
        - models
    yii2:
        config_path: config/phpstan.php
```

Alternatively, [PHPStan extension-installer](https://github.com/phpstan/extension-installer) reads this package's
`extra.phpstan.includes`. If it is installed and its Composer plugin is allowed, omit the manual include.

Use paths that exist, and run PHPStan from the application root. Unlike PHPStan's file parameters, this extension's
`yii2.config_path` is resolved against the process working directory. An absolute path is also accepted. It must
resolve to a readable regular file whose resolved extension is `.php`; a symlink to such a file is accepted.

## Describe the application

Create `config/phpstan.php`:

```php
<?php

declare(strict_types=1);

return [
    'params' => ['adminEmail' => 'admin@example.com'],
    'components' => [
        'db' => ['class' => yii\db\Connection::class],
    ],
];
```

This file is executed with `require` and must return an array. It describes types for analysis rather than configuring
a running application. Keep it free of database connections and other startup side effects. See
[configuration](configuration.md) for params, components, factories and behaviors.

For no configuration-derived types, omit `config_path` or set it to `''`. The defaults are a Web application and
unshaped params. See [source-checkout stubs](migration.md#upgrading-to-050) if including the extension outside `vendor/`.

## Bootstrap only what is needed

Composer autoloading discovers the package classes. An application using global `Yii` may also need a small
bootstrap file to load that class, aliases or additional application autoloading:

```php
<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/yiisoft/yii2/Yii.php';
```

Register it with `parameters.bootstrapFiles` in NEON. The extension itself defines `YII_DEBUG=true`,
`YII_ENV_DEV=false`, `YII_ENV_PROD=false` and `YII_ENV_TEST=true` before requiring the analysis configuration, unless
already defined. It does not define `YII_ENV`. If the analysis configuration reads additional constants or calls
`Yii` directly, load those prerequisites before that code executes.

## Verify

```bash
composer validate --strict
composer audit
vendor/bin/phpstan analyse -c phpstan.neon
vendor/bin/phpstan diagnose -c phpstan.neon
```

See the [usage examples](examples.md) for inferred types and their limitations.

[Back to documentation](../README.md#documentation)

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
`yii2.config_path` is resolved against the process working directory. An absolute path is also accepted. It must resolve
to a readable regular file whose resolved extension is `.php`; a symlink to such a file is accepted.

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

This file is executed with `require` and must return an array. It describes types for analysis rather than configuring a
running application. Keep it free of database connections and other startup side effects. See
[configuration](configuration.md) for params, components, factories and behaviors.

For no configuration-derived types, omit `config_path` or set it to `''`. The defaults are a Web application and
unshaped params. See [source-checkout stubs](migration.md#upgrading-to-050) if including the extension outside
`vendor/`.

## Bootstrap only what is needed

Composer autoloading discovers the package classes. The analyzed code of an application using global `Yii` may also need
a small bootstrap file, such as `config/phpstan-bootstrap.php`, to load that class, aliases or additional application
autoloading. Keep it safe to load more than once:

```php
<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/yiisoft/yii2/Yii.php';
```

Register it with `parameters.bootstrapFiles` in NEON:

```neon
parameters:
    bootstrapFiles:
        - config/phpstan-bootstrap.php
```

## Load what the analysis configuration needs

`bootstrapFiles` prepare the analyzed code, not the file named by `yii2.config_path`. The extension requires the
analysis configuration while PHPStan builds its services. With PHPStan 2.3.1, `bootstrapFiles` ran later in cold and
warm result cache runs and in a single-process `--debug` run. Do not rely on a bootstrap file running first: a constant,
alias or class provided only by it can be missing when the configuration runs, and a branch on it silently takes the
other path.

The extension itself defines `YII_DEBUG=true`, `YII_ENV_DEV=false`, `YII_ENV_PROD=false` and `YII_ENV_TEST=true` before
requiring the analysis configuration, unless already defined. It does not define `YII_ENV`. Load any other prerequisite
from the analysis configuration itself, for example by requiring the same bootstrap file:

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/phpstan-bootstrap.php';

return [
    'params' => ['adminEmail' => 'admin@example.com'],
];
```

PHPStan 2.3.1 also loads `bootstrapFiles` with `require_once`, so a file shared this way runs once per process. Keep its
contents idempotent anyway: use `require_once` and guard constants with `defined()`. Loading `Yii.php` with plain
`require` after `Yii` is already loaded redeclares its classes and stops the analysis. On Yii 22, Composer's classmap
autoloads `Yii` as soon as the configuration uses it, so a later plain `require` in a bootstrap file fails.

## Verify

```bash
composer validate --strict
composer audit
vendor/bin/phpstan analyse -c phpstan.neon
vendor/bin/phpstan diagnose -c phpstan.neon
```

See the [usage examples](examples.md) for inferred types and their limitations.

[Back to documentation](../README.md#documentation)

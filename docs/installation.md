# Installation guide

## Requirements

The 0.5 development line declares these constraints in `composer.json`:

| Dependency | Constraint               |
| ---------- | ------------------------ |
| PHP        | `>=8.1`                  |
| PHPStan    | `^2.3`                   |
| Yii        | `~2.0.55 \|\| ^22.0@dev` |
| PHP Parser | `^4.1 \|\| ^5.4`         |

Composer resolves the full dependency set; the selected Yii release can impose a higher PHP requirement. This
package supports Yii 2.0.55 or later compatible 2.0 releases, and Yii 22. The Yii 2.0 minimum is
[2.0.55](https://github.com/yiisoft/yii2/releases/tag/2.0.55); earlier 2.0 releases are no longer accepted. Keep
Composer advisory blocking and auditing enabled when installing or updating dependencies.

## Choose a version

Version 0.5.0 is under development. To use the branch documented here:

```bash
composer require --dev yii2-extensions/phpstan:0.5.x-dev
```

The branch alias maps `dev-main` to `0.5.x-dev`; this is a moving development dependency. An application lockfile
records the selected commit. After a 0.5 release is published, `^0.5` selects that release line. To select the
latest stable release instead, omit the constraint and read the documentation at its tag:

```bash
composer require --dev yii2-extensions/phpstan
```

A stable 0.4 installation does not contain all the behavior documented for 0.5. Review the [migration guide](migration.md)
before updating an existing integration. Inspect `composer show yii2-extensions/phpstan`, `composer show yiisoft/yii2`
and `vendor/bin/phpstan --version` to identify the versions actually installed.

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
`extra.phpstan.includes`. If it is installed and its Composer plugin is allowed, omit the manual include. Preserve
other plugin permissions and extension registrations in the application.

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

This file is executed with `require` and must return an array. It describes types; it is not passed to a running
Yii application. Keep it deterministic and independent of production credentials, database connections and network
services. The analysis-only `phpstan` and class-to-behavior map are not a normal Yii application configuration.
See [configuration](configuration.md) for factories, console applications, params, behaviors and generic components.

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
`Yii` directly, make its own prerequisites available before that code executes; prefer a self-contained description.
Do not bootstrap a full application just to obtain component type information.

## Verify

```bash
composer validate --strict
composer audit
vendor/bin/phpstan analyse -c phpstan.neon
vendor/bin/phpstan diagnose -c phpstan.neon
```

A clean analysis checks the selected application files. `--version` reports the PHPStan version, not a list of active
extensions. For the expected types and their limitations, see the [usage examples](examples.md).

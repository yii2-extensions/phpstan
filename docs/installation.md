# Installation guide

## System requirements

- [`PHP`](https://www.php.net/downloads) 8.1 or higher.
- [`Composer`](https://getcomposer.org/download/) for dependency management.
- [`PHPStan`](https://github.com/phpstan/phpstan) 2.3 or higher.
- [`Yii2`](https://github.com/yiisoft/yii2) 2.0.54+ or 22.x.

PHPStan 2.3 ships its native Turbo engine inside the `phpstan/phpstan` package. It activates automatically on PHP 8.3 or
higher and needs no configuration from this extension; run `vendor/bin/phpstan diagnose` to check its status.

## Installation

### Method 1: Using [composer](https://getcomposer.org/download/) (recommended)

Install the extension as a development dependency.

```bash
composer require --dev yii2-extensions/phpstan:^0.5
```

### Method 2: Manual installation

Add to your `composer.json`.

```json
{
    "require-dev": {
        "yii2-extensions/phpstan": "^0.5"
    }
}
```

Then run.

```bash
composer update
```

## Automatic extension installation

### Using PHPStan extension installer (recommended)

The easiest way is to use the official PHPStan extension installer.

```bash
composer require --dev phpstan/extension-installer
```

Add the plugin configuration to your `composer.json`.

```json
{
    "require-dev": {
        "phpstan/extension-installer": "^1.4",
        "yii2-extensions/phpstan": "^0.5"
    },
    "config": {
        "allow-plugins": {
            "phpstan/extension-installer": true,
            "yiisoft/yii2-composer": true
        }
    }
}
```

With this setup, the extension will be automatically registered, and you only need to configure the Yii specific
settings.

### Manual extension registration

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

    tmpDir: %currentWorkingDirectory%/runtime

    yii2:
        config_path: config/phpstan-config.php
```

## Creating PHPStan configuration file

Create a dedicated configuration file for PHPStan analysis. This should be separate from your main application configuration.

### Web application configuration

Create `config/phpstan-config.php`.

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

### Console application configuration

For console applications, create `config/phpstan-console-config.php`.

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
        config_path: config/phpstan-console-config.php
```

## Verification

Test your installation by running PHPStan.

```bash
vendor/bin/phpstan analyse
```

You should see output similar to.

```bash
PHPStan - PHP Static Analysis Tool
    [OK] No errors
```

### Test type inference

Create a simple test file to verify type inference is working.

```php
<?php

declare(strict_types=1);

// test-phpstan.php

// This should be typed as yii\web\Application, or the class set in phpstan.application_type
$app = \Yii::$app;

// This should show proper component types
$db = \Yii::$app->db;      // Connection
$user = \Yii::$app->user;  // User
```

Run PHPStan on this file.

```bash
vendor/bin/phpstan analyse test-phpstan.php --level=5
```

## Bootstrap configuration

The extension ships a `bootstrap.php` that it loads before reading your configuration file, defining `YII_DEBUG`,
`YII_ENV_DEV`, `YII_ENV_PROD`, and `YII_ENV_TEST` with safe analysis defaults, so most projects don't need to define
them manually. Existing definitions are preserved.

If your application requires additional bootstrap logic — autoloading Yii, defining `YII_ENV` (the environment string),
or your own constants — create a bootstrap file (for example, `tests/bootstrap.php`).

```php
<?php

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

### Debugging installation

Enable verbose output to see what is happening.

```bash
vendor/bin/phpstan --debug -vvv --error-format=table --memory-limit=1G
```

Check which extensions are loaded.

```bash
vendor/bin/phpstan --version
```

## Upgrading to 0.5.0

Version 0.5.0 requires PHPStan 2.3 or higher and Yii2 2.0.54+ or 22.x. Yii ships generic and conditional PHPDoc types
for `ActiveRecord`, `ActiveQuery`, `Container`, `ServiceLocator`, and `HeaderCollection`, so the extension no longer
duplicates them: `find()`, `findOne()`, `findAll()`, `findBySql()`, `hasOne()`, `hasMany()`, `one()`, and `all()` are
typed by Yii itself. The extension adds what that PHPDoc can't express: the model of a custom query class without
generic PHPDoc, such as one generated by Gii, taken from the `find()` call site or from the class's own `one()` and
`all()` overrides, `asArray()` row shapes built from the model `@property` tags, `getAttribute()` typing, and the
application class, params, components, behaviors, and container services from your configuration file.

Your configuration doesn't change: `yii2.config_path`, `phpstan.application_type`, and `params` work as before, and so do
narrowing, assignments, and write checks on `Yii::$app` and its params. The extension no longer generates stub files at
runtime. It ships a static stub, `stubs/yii.stub`, registered through `stubFiles` in `extension.neon`, that declares
`yii\BaseYii::$app` and `yii\base\Module::$params` with placeholder PHPDoc types, and a PHPDoc type resolver of the
extension (`ApplicationTypeNodeResolverExtension`) turns them into the configured application class and params shape. A
writable temporary directory is no longer needed, and the `yii2-phpstan-stub-*.stub` files that 0.4.x wrote to the
system temporary directory and never removed can be deleted.

If `extension.neon` is included from a path outside your project's Composer vendor directory, such as a checkout of this
repository analyzing itself, a git submodule, or a monorepo or path repository included by its real path, PHPStan
validates `stubs/yii.stub` as a project stub. A Composer install included through `vendor/`, symlinked or not, isn't
affected. The validation runs only on a full analysis with a cold result cache, and its errors can't be suppressed with
`ignoreErrors`. It can report two errors:

- `class.notFound` for the configured application class, since the stub is validated against the classes declared in
  stub files only. Declare that class in a stub file of your project, for the default
  `<?php namespace yii\web { class Application {} }`, and add that file to `stubFiles`; this repository does so with
  `tests/support/application-classes.stub`, registered in `phpstan.neon`.
- `missingType.iterableValue` at level 6 or higher when no `params` are configured. Configure at least one param, or
  include the extension through `vendor/`.

### Inferred type changes

Update `@var` and `@return` annotations, and `assertType()` expectations, that rely on the previous types.

| Call                                                              | Before (0.4.x)                      | After (0.5.0)                            |
| ----------------------------------------------------------------- | ----------------------------------- | ---------------------------------------- |
| `Model::find()->all()`, `$model->hasMany(...)->all()`             | `array<int, Model>`                 | `array<Model>`                           |
| `Model::find()->asArray()->all()`                                 | `array<int, array{...}>`            | `array<array{...}>`                      |
| `static::find()`, `self::find()` inside a model                   | `ActiveQuery<Model>`                | `ActiveQuery<static(Model)>`             |
| `$model->hasOne($flag ? A::class : B::class, [...])`              | `ActiveQuery` of one class only     | `ActiveQuery<A\|B>`                      |
| `$model->hasOne($class, [...])` with `class-string<User> $class`  | PHPStan internal error              | `ActiveQuery<User>`                      |
| `CommentQuery` method chain, then `one()`                         | `mixed`                             | `Comment\|null`                          |
| `Article::find()->asArray()`                                      | `ActiveQuery<array<string, mixed>>` | `ActiveQuery<array{id: int, ...}>`       |
| `asArray()` row key of a `@property Comment[] $comments` tag      | `comments: array<Comment>`          | `comments?: array<array<string, mixed>>` |
| `asArray()` row key of a `@property Category\|null $category` tag | `category: Category\|null`          | `category?: array<string, mixed>\|null`  |
| `asArray()` row key of a `@property-read string $label` tag       | `label: string`                     | `label?: string`                         |
| `$article->getAttribute('id')`                                    | `mixed`                             | `int`                                    |
| `$container->get('unknown')`, `$container->get($id)`              | `mixed`                             | `object`                                 |
| `Yii::$app->get('unknown')`, `$locator->get($id)`                 | `mixed`                             | `object`                                 |
| `$locator->get('user', false)`, `$locator->get('user', $bool)`    | `User`                              | `User\|null`                             |
| `$headers->get($name, null, false)`                               | `array<int, string>\|null`          | `array<string>\|null`                    |
| `$headers->get($name, [], false)`                                 | `array<int, string>`                | `array<string>`                          |
| `Target::filterMessages([])`                                      | array of `never`                    | `array{}`                                |

`CommentQuery` stands for a custom query class declared with `@extends ActiveQuery<Comment>`, and `Article` for a model
without `@property` tags of its own that extends a base class declaring them. A model whose `find()` returns a custom
query class without generic PHPDoc gets `static::find()` and `self::find()` typed as
`ModelQuery&yii\db\ActiveQuery<static(Model)>` instead. Relation calls with `static::class` or `get_class($this)` no
longer abort the analysis either; `$this->hasMany(static::class, [...])` is `ActiveQuery<static(Model)>`.

Relation keys, which an `asArray()` row holds only when the relation is eager-loaded with `with()`, and read-only keys
are optional. Reading one directly, as in `$row['comments']`, is reported at level 7 and higher as an offset that might
not exist; guard it with `??`, `isset()`, or `array_key_exists()`. In 0.4.x these keys were required and relation keys
were typed as model objects, which type-checked but didn't match the rows Yii returns.

Other inference changes in this release:

- A `find()` that returns a custom query class without `@template` or `@extends` PHPDoc, as Gii generates it, is bound
  to the model named at the call site: `Invoice::find()->one()` is `Invoice|null` and `Invoice::find()->all()` is
  `array<Invoice>` through the fluent methods inherited from `ActiveQuery` and through scope methods typed `: static` or
  `@return $this`, and `batch()` and `each()` yield `Invoice`. This works for a class name, a `class-string` variable,
  `self::find()`, `static::find()`, and `parent::find()`, and `CreditInvoice::find()` on a subclass yields
  `CreditInvoice`. PHPStan prints the query as `InvoiceQuery&yii\db\ActiveQuery<Invoice>`, also in error messages, and
  accepts it wherever `InvoiceQuery` or `ActiveQuery<Invoice>` is expected. With `reportWrongPhpDocTypeInVarTag`
  enabled, as `phpstan/phpstan-strict-rules` does, a redundant `/** @var InvoiceQuery $query */` on
  `$query = Invoice::find();` is now reported as `varTag.type`
  (`PHPDoc tag @var with type InvoiceQuery is not subtype of type InvoiceQuery&yii\db\ActiveQuery<Invoice>.`); remove
  the annotation, since the inferred type is more precise. Generic tags on the query class, a generic `@return` tag on
  `find()`, and edits to the `one()` and `all()` overrides that Gii generates are no longer needed; see
  [Custom Active Query classes](examples.md#custom-active-query-classes) for what still needs PHPDoc.
- A query typed only as a query class without generic PHPDoc, such as a typed parameter or property, a value returned
  by a method, or the result of a scope typed `: self`, takes its model from its `one()` and `all()` overrides, or
  those of a parent query class, when they name exactly one Active Record class, as Gii's
  `@return Invoice|array|null` and `@return Invoice[]|array` do: `one()` is `Invoice|null`, `all()` is `array<Invoice>`,
  `batch()` and `each()` yield `Invoice`, and `asArray()` gives the `Invoice` row shape. A query class without such
  overrides, a union of different query classes, and a relation getter typed `@return \yii\db\ActiveQuery|CommentQuery`,
  as Gii writes it, keep PHPStan's own answer, such as `array|yii\db\ActiveRecord|null` for `one()`; declare
  `@extends \yii\db\ActiveQuery<Model>` on the query class, or a precise `@return` on the getter.
- Because `one()` and `all()` on these queries no longer include `array`, a defensive `is_array()` or `instanceof` check
  on their results, which 0.4.x typed as `mixed`, is now reported as always false or always true
  (`function.impossibleType`, `instanceof.alwaysTrue`); remove it. Rows are typed only through the value `asArray()`
  returns, not after a bare `$query->asArray();` statement or a switch to array mode inside the query class, and a
  model taken from the overrides belongs to the query class, not to the model the query object was built for. See
  [Custom Active Query classes](examples.md#custom-active-query-classes).
- An `asArray()` row treats a `@property` tag whose type, ignoring `null`, is an Active Record model or an iterable of
  models as a relation: its key is optional and holds a related row (`array<string, mixed>|null`) or an array of
  related rows (`array<array<string, mixed>>`). Any other tag keeps its own type, as a required key when it's writable
  and as an optional key when it's read-only, and write-only tags give no key. A column tagged with a value object or an
  enum keeps that type as a required key, although the row holds the raw database value.
- Components and container services accept every definition form Yii accepts when its class can be determined, a
  `yii\di\Instance` reference in a container definition resolves through its ID, and a definition whose class can't
  be determined, such as a closure without a return type or a `yii\di\Instance` reference in a component definition,
  no longer stops the analysis: `get()` returns `object` for it, and `Yii::$app->id` is `object` too for a
  component the application class doesn't declare. A string definition, or the `class` or `__class` of an array
  definition, that names another container ID, such as `'mailer.alias' => 'mailer.real'`, resolves through that ID,
  following chains, and `get()` returns `object` for it when that ID's class can't be determined. Such a definition
  under a class name ID, such as `Foo::class => static fn() => new Bar()`, is `object` as well, no longer `Foo`. A list
  holding only a definition, such as `[['class' => Foo::class]]`, which Yii rejects, is no longer resolved. The
  exception `Please provide return type for '<id>' service closure.` no longer exists, and
  `Unsupported definition for '<id>'.` is thrown only for an integer, float, or boolean definition. See
  [Definition forms](configuration.md#definition-forms).
- `asArray()` row shapes and `getAttribute()` types include the `@property` tags inherited from parent classes, used
  traits, and interfaces, so a model that extends a generated base class gets the tags of that class. The nearest
  declaration of a name wins, and tags declared by `yii\db\ActiveRecord` and the classes, interfaces, and traits it's
  built from aren't included.
- `asArray()` keeps the row shape through a nullsafe chain: `$user?->hasOne(Category::class, [...])->asArray()` is
  `ActiveQuery<array{...}>|null`.
- Calls that unpack any argument, such as `$headers->get('Accept', ...$args)`, `Target::filterMessages(...$args)`,
  `asArray(...$args)`, and `getAttribute(...$args)`, return the type Yii declares for the method. `get()` on the
  container or a service locator does so only when the id itself is unpacked, as in `$container->get(...$args)`; with
  only the arguments after the id unpacked, as in `$container->get('logger', ...$args)`, it still resolves the
  configured class.
- Each logger message tuple in `Logger::$messages` and `Target::$messages` is a `list`, so it's accepted where a `list`
  is expected; its printed shape doesn't change.
- Params whose keys contain quotes, backslashes, whitespace, line breaks, or other characters that aren't valid in an
  identifier get their exact shape. In 0.4.x, such keys could break the generated stub, and `Yii::$app->params` lost its
  shape. With no params configured, `Module::$params` stays `array`.

### Removed and changed API

These changes affect only code that extends or instantiates the extension classes directly. Class names are relative to
the `yii2\extensions\phpstan` namespace.

- `type\ActiveRecordDynamicMethodReturnTypeExtension` and `type\ActiveRecordDynamicStaticMethodReturnTypeExtension` are
  removed; delete any manual registration of them from your PHPStan configuration.
- `StubFilesExtension` is removed, together with its `stubDirectory` constructor argument; delete any manual
  registration of it. `stubs/yii.stub` and `type\ApplicationTypeNodeResolverExtension` replace the stub it generated.
- `ServiceMap::getComponentDefinitionByClassName()` is removed; use `ServiceMap::getComponentDefinitionById()`, which
  now omits the `__class` key as well as `class`.
- `ServiceMap::isUnresolvedComponent()` is new; it returns `true` for a component that is configured but whose class
  can't be determined.
- `reflection\ComponentPropertyReflection::getType()` is removed; use `getReadableType()`.
- Constructors that changed, with their new parameter lists (`DeclarationDependencyTracker` is
  `PHPStan\Analyser\DeclarationDependencyTracker`):
  - `type\ActiveQueryDynamicMethodReturnTypeExtension`: `ReflectionProvider`, `PropertyTagTypeResolver` (replaces
    `FileTypeMapper`).
  - `type\ActiveRecordGetAttributeDynamicMethodReturnTypeExtension`: `ReflectionProvider`, `ServiceMap`,
    `PropertyTagTypeResolver` (replaces `FileTypeMapper`, which came before `ServiceMap`).
  - `type\ContainerDynamicMethodReturnTypeExtension`: `ServiceMap` (`ReflectionProvider` removed).
  - `property\ApplicationPropertiesClassReflectionExtension`: `AnnotationsPropertiesClassReflectionExtension`,
    `ReflectionProvider`, `ServiceMap`, `DeclarationDependencyTracker`, `array $genericComponents = []`
    (`DeclarationDependencyTracker` added).
  - `property\BehaviorPropertiesClassReflectionExtension`: `ReflectionProvider`, `ServiceMap`,
    `DeclarationDependencyTracker` (`AnnotationsPropertiesClassReflectionExtension` removed, `DeclarationDependencyTracker`
    added).
  - `method\BehaviorMethodsClassReflectionExtension`: `ReflectionProvider`, `ServiceMap`, `DeclarationDependencyTracker`
    (`DeclarationDependencyTracker` added).
- New public classes: `ParamsTypeBuilder`, `PropertyTagTypeResolver`, `ServiceMapResultCacheValueExtension`,
  `type\ActiveRecordQueryDynamicStaticMethodReturnTypeExtension`, and `type\ApplicationTypeNodeResolverExtension`.

Services registered through `extension.neon` are autowired and need no change.

### Result cache

The extension declares, through PHPStan 2.3 dependency tracking, what each analyzed file reads from your configuration:
a component or container service by id, and the behaviors of a class. It also records class dependencies on the
configured application class and on behavior classes. The application class and the params type are recorded instead on
the classes that declare `Yii::$app` and `Module::$params`, `yii\BaseYii` and `yii\base\Module`, and PHPStan propagates
them to every analyzed file that depends on either class or on a subclass. When the application class or the params
type changes, all those files are re-analyzed, including files that only override or inherit the properties. Editing the
configuration file, or a file it pulls in with `require` such as a params file, re-analyzes only the files that depend on
a changed value, and a change that leaves those values as they were, such as a param value of the same type, re-analyzes
nothing. You don't need to clear the cache (`--clear-result-cache` or deleting `tmpDir`) after configuration changes.

## Next steps

Once the installation is complete.

- ⚙️ [Configuration Reference](configuration.md)
- 💡 [Usage Examples](examples.md)
- 🧪 [Testing Guide](testing.md)

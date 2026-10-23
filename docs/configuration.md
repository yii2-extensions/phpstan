# Configuration reference

Start with [installation](installation.md); use the [examples](examples.md) to verify inferred types. Configuration is
split between PHPStan's NEON parameters and a PHP file describing the application.

## NEON parameters

The extension registers `parameters.yii2` in `extension.neon`:

| Key                  | Default               | Meaning                                                                      |
| -------------------- | --------------------- | ---------------------------------------------------------------------------- |
| `config_path`        | `''`                  | PHP file to execute, relative to the process working directory or absolute.  |
| `component_generics` | `user: identityClass` | Component ID to configuration key holding its single generic class argument. |

PHPStan's `paths`, `level`, `bootstrapFiles`, `stubFiles` and `tmpDir` remain normal
[PHPStan options](https://phpstan.org/config-reference).

## Analysis configuration

The PHP file returns an array with these optional sections:

| Section                    | Meaning                                               |
| -------------------------- | ----------------------------------------------------- |
| `phpstan.application_type` | Application class; defaults to `yii\web\Application`. |
| `params`                   | Values from which the params shape is built.          |
| `components`               | Application component IDs and definitions.            |
| `container.definitions`    | Container service IDs and definitions.                |
| `container.singletons`     | Container singleton IDs and definitions.              |
| `behaviors`                | Owner class name to a list of behavior class names.   |

Sections must be arrays, application type must be a string, and component, service and behavior owner IDs must be
strings. A file that returns something other than an array fails analysis. Integer, float and boolean service or
component definitions are rejected with `Unsupported definition for '<id>'.`. Non-string entries inside a behavior list
are ignored; keep the list explicit so omissions are visible.

The file is executed, including its `require` statements and object construction. Describe types without running
application startup, reading secrets or opening services. The extension does not instantiate string or array class
definitions to discover types. A factory's return type is inspected without invoking the factory.

## Web, console and custom applications

Set the application class in the PHP description, not under `parameters.yii2`:

```php
return [
    'phpstan' => ['application_type' => yii\console\Application::class],
    'params' => ['batchSize' => 100],
];
```

Use a real autoloadable subclass for a custom application. The selected class determines `Yii::$app` and core component
annotations. A console application must select `yii\console\Application` explicitly.

For a project with both entry points, create separate NEON configurations with different `paths`, `config_path` and
preferably `tmpDir`, and run each with `-c`. Each analysis has one application class and one params shape.

## Params

The shape is inferred from configured values. Strings, integers, floats and booleans are widened to their types; `null`
stays `null`, nested arrays become nested shapes, and objects/resources become `mixed`. An empty nested array becomes
`array<mixed, mixed>`. With no params, the top-level type stays `array`.

Keys may contain dots, quotes, whitespace or backslashes. `Yii::$app->params` and the inherited `Module::$params` share
this shape, including `$this->module->params` in a controller. A module declaring its own native `params` property keeps
its own type. This is one analysis-wide shape, not a separate shape for each module or request.

## Definition forms

Both component and container maps resolve a class name string, an array with `class` or `__class`, a closure with a
single non-null class return type, or an object. Classes need to be autoloadable for PHPStan to inspect their members.

```php
return [
    'components' => [
        'typedView' => static fn(): yii\web\View => new yii\web\View(),
        'untypedView' => static fn() => new yii\web\View(),
        'request' => ['cookieValidationKey' => 'analysis-only'],
    ],
    'container' => [
        'definitions' => [
            'view.real' => ['class' => yii\web\View::class],
            'view.alias' => 'view.real',
        ],
    ],
];
```

`Yii::$app->get('typedView')` is `yii\web\View`; the untyped factory gives `object`. Nullable, union and builtin factory
return types, array callables and unresolved aliases also give `object`. A typed factory is the smallest useful fix when
the implementation has one class result. For a core component configured without a class, property access such as
`Yii::$app->request` keeps the type declared by the application; `get('request')` still gives `object`.

Definition differences follow Yii's container and service locator contracts:

- Component arrays prefer `__class` to `class`; container arrays prefer `class` to `__class`.
- A `yii\di\Instance` object in a component map is typed as that object. In a container map its ID is resolved instead.
- An invocable object is a factory in a container map, resolved from its single class return type; in a component map it
  is the component object itself. Closures are factories in both maps.
- A container `[definition, params]` pair is resolved from its first entry. A list containing only a definition is not
  unwrapped. An empty definition under an instantiable class ID resolves to that class.
- A class string or array class key can name another container ID. Alias chains resolve across definitions and
  singletons, and components can use those aliases. Cycles and unresolved targets give `object`. The class of an object
  and the return type of a factory are final, without alias resolution.
- Definitions and singletons share an ID space. When an ID appears in both, the subsection listed later in the PHP array
  wins, including when its result is unresolved.

`Yii::$app->get()` and other service locators resolve component IDs only. A container-only ID or arbitrary class name
keeps Yii's return type (`object`) and may throw at runtime. Resolve container services through
`Yii::$container->get()`. Passing `false` or a non-constant boolean as a service locator's `$throwException` argument
adds `null` to a resolved component type. The analysis map describes the application; it does not configure a newly
created standalone locator at runtime.

## Generic components

The built-in mapping is `user: identityClass`. For an array component definition containing that key, the component
property becomes `yii\web\User<IdentityClass>`. The identity can still be `null`; preserve null checks before reading
its members. Generic component mapping applies to application property access, not every `get()` return type.

Add a mapping by component ID for a custom component that actually declares a generic parameter:

```neon
parameters:
    yii2:
        component_generics:
            repository: modelClass
```

Its array definition needs `class` and `modelClass`, both naming real classes. The component must declare its template
and use it in its PHPDoc. The mapping supplies one class argument; it does not make an ordinary class generic. PHPStan
merges the associative map with the extension's built-in `user` mapping.

## Behaviors

The top-level `behaviors` section maps an owner class to behavior classes for property and method reflection:

```php
return [
    'behaviors' => [
        app\models\Article::class => [app\behaviors\PublishableBehavior::class],
    ],
];
```

The map describes runtime attachments; conditional `behaviors()` implementations are not discovered automatically.
Native members and PHPDoc on the behavior provide its types. See the [behavior recipe](examples.md#behaviors).

## Constants and result cache

`extension.neon` registers `YII_DEBUG`, `YII_ENV` and the three environment flags as `dynamicConstantNames`. This
changes how PHPStan reasons about their values; it does not define them. The shipped bootstrap defines four analysis
defaults as described in [installation](installation.md#bootstrap-only-what-is-needed). Add custom constant names
alongside the Yii entries if needed; PHPStan merges list parameters across included configurations.

PHPStan 2.3 dependency tracking invalidates the affected analysis when component, container, behavior, application class
or params types change, including values loaded from a required file. Changing a scalar param without changing its type
need not re-analyze files. Do not delete caches routinely after configuration edits. See
[migration](migration.md#result-cache) for the dependency boundaries.

## Diagnose an unexpected type

Confirm the selected configuration, process working directory, autoloading and installed versions first:

```bash
vendor/bin/phpstan analyse -c phpstan.neon --debug -vvv
vendor/bin/phpstan diagnose -c phpstan.neon
```

Inspect the factory return type, component ID and chosen application class before adding annotations. Keep unresolved
services as `object` until the type can be established. Use a small type assertion or `PHPStan\dumpType()` probe and
remove temporary probes after diagnosis.

[Back to documentation](../README.md#documentation)

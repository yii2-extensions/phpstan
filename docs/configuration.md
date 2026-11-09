# Configuration reference

Start with [installation](installation.md); use the [examples](examples.md) to verify inferred types. Configuration is
split between PHPStan's NEON parameters and a PHP file describing the application.

## NEON parameters

The extension registers `parameters.yii2` in `extension.neon`:

| Key                                     | Default               | Meaning                                                                          |
| --------------------------------------- | --------------------- | -------------------------------------------------------------------------------- |
| `config_path`                           | `''`                  | PHP file to execute, relative to the process working directory or absolute.      |
| `component_generics`                    | `user: identityClass` | Component ID to configuration key holding its single generic class argument.     |
| `rules.behaviorInvalidAsDefinition`     | `false`               | Enables the [`as` behavior option rule](#invalid-as-behavior-options).           |
| `rules.behaviorInvalidDefinition`       | `false`               | Enables the [behavior definition rule](#invalid-behavior-definitions).           |
| `rules.createObjectInvalidOptionType`   | `false`               | Enables the [option type rule](#invalid-yiicreateobject-option-types).           |
| `rules.createObjectUnknownOption`       | `false`               | Enables the [unknown option rule](#unknown-yiicreateobject-options).             |
| `rules.externalActionInvalidOptionType` | `false`               | Enables the [external action option type rule](#options-of-external-actions).    |
| `rules.externalActionUnknownOption`     | `false`               | Enables the [external action unknown option rule](#options-of-external-actions). |
| `rules.newInvalidOptionType`            | `false`               | Enables the [`new` option type rule](#options-of-new-constructions).             |
| `rules.newUnknownOption`                | `false`               | Enables the [`new` unknown option rule](#options-of-new-constructions).          |
| `rules.widgetInvalidOptionType`         | `false`               | Enables the [widget option type rule](#options-of-widget-calls).                 |
| `rules.widgetUnknownOption`             | `false`               | Enables the [widget unknown option rule](#options-of-widget-calls).              |

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

Sections must be arrays, application type must be a string naming an application class (see
[Web, console and custom applications](#web-console-and-custom-applications)), and component, service and behavior owner
IDs must be strings. A file that returns something other than an array fails analysis. Integer, float and boolean
service or component definitions are rejected with `Unsupported definition for '<id>'.`. Non-string entries inside a
behavior list are ignored; keep the list explicit so omissions are visible.

The file is executed, including its `require` statements and object construction, and PHPStan can execute it more than
once in an analysis. It can't rely on `bootstrapFiles`; load its prerequisites as described in
[installation](installation.md#load-what-the-analysis-configuration-needs). Describe types without running application
startup, reading secrets or opening services. The extension does not instantiate string or array class definitions to
discover types. A factory's return type is inspected without invoking the factory.

## Web, console and custom applications

Set the application class in the PHP description, not under `parameters.yii2`:

```php
return [
    'phpstan' => ['application_type' => yii\console\Application::class],
    'params' => ['batchSize' => 100],
];
```

An omitted or empty `application_type` selects `yii\web\Application`. Any other value must name `yii\base\Application`
or a class extending it, such as a console, Web or custom subclass, in any namespace. The selected class determines
`Yii::$app` and core component annotations. A console application must select `yii\console\Application` explicitly.

The extension checks the class through PHPStan's reflection when the analysis first reads `Yii::$app` or an application
property, not while the PHP file runs. The class can therefore come from Composer autoloading, an analyzed path,
`bootstrapFiles` or a file this description requires; see
[how PHPStan discovers symbols](https://phpstan.org/user-guide/discovering-symbols) and
[configuration and bootstrap order](installation.md#load-what-the-analysis-configuration-needs). A class declared only
in a stub file, an unknown class, an interface, a trait, an enum, or a class that doesn't extend `yii\base\Application`
stops the analysis with `Application type '<value>' in 'phpstan.application_type' is <kind>; ...`. PHPStan 2.3.1 reports
it as an internal error and doesn't save the result cache, so the error repeats until the value is fixed.

For a project with both entry points, create separate NEON configurations with different `paths`, `config_path` and
preferably `tmpDir`, and run each with `-c`. Each analysis has one application class and one params shape.

## Non-null `Yii::$app`

The shipped stub declares `Yii::$app` as exactly the selected application class, without `null`. This is an analysis
contract for code that runs after the application is created, not a statement about Yii's lifecycle: Yii declares the
property nullable, and it is `null` before an application exists or after code resets it. Assignments, including
`Yii::$app = null`, still narrow the property as usual, but only for the spelling used: after
`yii\BaseYii::$app = null`, PHPStan still types `Yii::$app` as the application, although both name the same property at
runtime.

Under this contract PHPStan treats some null checks and checks for the other application kind as redundant or
impossible. With a Web application, PHPStan 2.3.1 reports:

- `isset(Yii::$app)` as `isset.property` and `Yii::$app ?? $fallback` as `nullCoalesce.property`, from level 4;
- `Yii::$app instanceof yii\console\Application` as `instanceof.alwaysFalse`, typing its branch as `*NEVER*`, and the
  check for the selected class as `instanceof.alwaysTrue`, from level 4; code after a branch that always returns can be
  reported as `deadCode.unreachable`;
- `Yii::$app = null` as `assign.propertyType`, from level 3.

`Yii::$app === null` isn't reported, but its branch is `*NEVER*` as well. A console analysis gives the same results with
the application kinds swapped.

Web and console are analyzed separately so that each run keeps the exact application type. Code included in both runs,
such as a helper that checks the application kind, receives these diagnostics in the run where its check is constant.

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

## Unknown `Yii::createObject()` options

An opt-in rule reports configuration options that Yii can't assign when it creates the object. Enable it per project:

```neon
parameters:
    yii2:
        rules:
            createObjectUnknownOption: true
```

```php
final class Feed extends yii\base\BaseObject
{
    public string $url = '';

    public function setTimeout(int $seconds): void {}
}

Yii::createObject(['class' => Feed::class, 'url' => '/rss', 'timeout' => 5]);        // valid
Yii::createObject(['class' => Feed::class, 'url' => '/rss', 'timeoutSeconds' => 5]); // reported
```

The second call throws `yii\base\UnknownPropertyException` at runtime and is reported at the line of the
`timeoutSeconds` key with the identifier `yii2.createObject.unknownOption`. Values are checked by the separate
[option type rule](#invalid-yiicreateobject-option-types).

The configuration doesn't have to be written in the call. Keys, the class and the configuration itself can come from
class constants, local variables or unpacked constant arrays, as long as PHPStan's native type proves a single value:

```php
$config = ['class' => Feed::class, 'url' => '/rss'];
$config['timeoutSeconds'] = 5;

Yii::createObject($config); // reported at this line
```

An option written as a key in the call is reported at that key, including a constant key such as `self::OPTION`, when
the configuration is an array literal without unpacking and every key has one constant string value. Otherwise, every
option is reported at the call line: this includes a configuration that comes from a variable or a constant, and an
array literal that unpacks another array. PHPDoc types such as `@param array{...}`, `@var` or `@return` never prove a
configuration; only native types, which PHPStan derives from literal values, constants and assignments, do.

The rule reports only when Yii's assignment path is certain:

- the call is `createObject()` declared by `yii\BaseYii`, through `Yii`, `BaseYii`, an imported alias or a subclass that
  doesn't override it, with no `$params` or with `$params` whose native type is one array shape;
- the native type of the configuration is exactly one array shape with string keys and no optional key, and its
  `__class` value, or else its `class` value, is one class name; with [constructor arguments](#constructor-arguments),
  the configuration can also be one class name;
- the class name has no container binding, or only bindings that build one class and assign the configuration to it, as
  described below;
- the class Yii builds is instantiable, inherits the constructor of `yii\base\BaseObject` and inherits `__set()` from
  `yii\base\BaseObject` or `yii\base\Component`.

An option is accepted when it names a public property or a public or protected `set` method, inherited or from a trait.
Options that fail differently at runtime are not reported either: a private `set` method, a name with only a `get`
method, and static or read-only properties. The `class` and `__class` keys with which `Yii::createObject()` selects the
class are never reported. A `@property` tag alone does not make an option assignable, since Yii assigns through native
members.

The rule stands aside for custom constructors or `__set()` methods; abstract or unknown classes; configurations whose
native type allows more than one array shape, an optional or integer key, or unknown unpacked data; class values that
aren't one known name; and constructor arguments that may address another parameter, as described under
[constructor arguments](#constructor-arguments).

`Yii::createObject()` passes the configuration to `Yii::$container`, so a container binding of the class name can
replace the class or ignore the configuration. The rule follows a `container.definitions` entry for the class name only
when Yii then builds one class and assigns the configuration to it:

- a class name string, a `yii\di\Instance` reference, or an array with a `class` or `__class` key, which makes Yii build
  that class instead; the class can itself be a bound ID, so chains of several entries are followed;
- `[]` or `null`, or an array without `class` and `__class` under a namespaced class name, which makes Yii build the
  class name itself;
- either form in a `[definition, params]` pair, and an array with a `__construct()` key that holds an array, whose
  [constructor arguments](#constructor-arguments) are merged with those of the call.

The other keys of an array are options that Yii assigns before the options of the call; an option of the call with the
same name replaces the definition's value. The rule follows such an entry only when each of its options, on every entry
of the chain, is a public instance property of the class Yii builds that isn't read-only, hooked or of asymmetric
visibility, because Yii then assigns them without running code of the class.

```php
'container' => [
    'definitions' => [
        FeedInterface::class => 'feed.default',
        'feed.default' => ['class' => Feed::class, 'url' => '/rss'],
    ],
],
```

```php
Yii::createObject(['class' => FeedInterface::class, 'timeoutSeconds' => 5]); // reported for Feed
```

IDs are compared as written, as Yii does: `'\app\Feed'` doesn't match an entry for `app\Feed`, and a chain that names
`'\app\Feed'` builds that class directly, bypassing such an entry. Every other binding on the chain stands aside, even
when it resolves to the same class: a `container.singletons` entry, a later singleton for the same ID, a factory or an
invocable object, which may ignore the configuration or apply it, an object, which Yii returns as is, an array with any
other option, such as a setter, an `on ...` or `as ...` key, or a name the class can't assign, whose code or failure
runs before the call's options, an array with an integer key, a `__construct()` key that doesn't hold an array, or both
`class` and `__class`, and a cycle, which Yii follows until the process fails.

The analysis configuration is treated as the complete list of container bindings: a binding registered at runtime
outside it, for example in a bootstrap class, is not seen. Describe such bindings in the analysis configuration or keep
the rule disabled for that code. Changing a binding on the chain, whether its target or its form, adding or removing a
behavior in the analysis configuration, changing the keys of the constructor arguments of a definition, changing the
class Yii builds or one of its parents, or changing a constant the configuration or `$params` reads reanalyses the
affected calls without clearing the result cache.

For a `yii\base\Component` subclass, `__set()` also attaches `on ...` event handlers and `as ...` behaviors, and passes
a name without a public property or setter to the attached behaviors. `on ...` and `as ...` options are never reported.
Any other option without a public property or setter is reported only when no behavior can receive it:

- the class inherits `behaviors()` and `ensureBehaviors()` from `yii\base\Component`, without an override in the class,
  a parent class or a trait;
- the configuration has no `as ...` option, wherever it appears;
- the [`behaviors`](#behaviors) section of the analysis configuration lists no behavior for the class, a parent class,
  an interface it implements or a trait it uses;
- no earlier option in the configuration calls a `set` method or attaches an event handler through an overridden `on()`,
  because that code can attach a behavior before Yii assigns the option.

```php
final class Feed extends yii\base\Component
{
    public string $url = '';
}

Yii::createObject(['class' => Feed::class, 'on refresh' => $handler, 'url' => '/rss']); // valid
Yii::createObject(['class' => Feed::class, 'url' => '/rss', 'timeoutSeconds' => 5]);    // reported
Yii::createObject(['class' => Feed::class, 'as cache' => CacheBehavior::class, 'ttl' => 60]); // not checked
```

The prefixes are compared case-sensitively, as Yii does, so `'On refresh'` is reported. The rule doesn't read the
`behaviors()` method or the behavior definitions; a behavior attached only at runtime, outside the analysis
configuration and the class hierarchy, for example by an `init()` override or a bootstrap class after construction,
doesn't receive configuration options and doesn't affect the rule.

PHPStan doesn't follow every way a local variable can change. `extract()`, `include`, `eval()`, a `foreach` loop by
reference, or a called function that writes a `global` variable can replace a configuration whose native type still
describes the earlier value. Pass the configuration directly or keep the rule disabled for such code.

### Constructor arguments

`$params`, a `__construct()` key of the configuration, and the params or `__construct()` key of the definitions on the
chain pass constructor arguments. Yii merges them by key: `$params` of the call over the params of the definitions,
nearest first, over the `__construct()` array of the call or, without one, of the nearest definition that has one. The
inherited constructor of `yii\base\BaseObject` has one parameter, `$config`, so the rule follows these arguments only
when every key names it, as position `0` or as the name `config`:

- when the call or a definition has options, Yii assigns them and ignores the argument, so the call's options are
  checked as usual;
- otherwise Yii passes the argument as the configuration. It is checked when the call supplies it, in `$params` or in
  `__construct()`, as one array shape with string keys. Yii assigns each key as a property, in order, `class`, `__class`
  and `__construct()` included, so these keys are checked like any other option.

```php
Yii::createObject(Feed::class, [['timeoutSeconds' => 5]]);                                // reported
Yii::createObject(['class' => Feed::class, '__construct()' => [['timeoutSeconds' => 5]]]); // reported
Yii::createObject(['class' => Feed::class, 'url' => '/rss'], [['timeoutSeconds' => 5]]);  // valid
```

In the last call Yii assigns `url` and ignores the argument. The rule stands aside when the keys mix positions and
names, which makes Yii throw; when a key addresses another parameter, such as position `1` or another name, which makes
Yii pass the configuration to that parameter instead; when `$params` or `__construct()` isn't one array shape; and when
the argument Yii uses as configuration comes from a definition.

## Invalid `Yii::createObject()` option types

A second opt-in rule reports option values that the property or setter Yii assigns them to can never accept. It is
independent of the unknown option rule; enable either or both:

```neon
parameters:
    yii2:
        rules:
            createObjectInvalidOptionType: true
```

```php
final class Feed extends yii\base\BaseObject
{
    public int $limit = 10;

    public function setTags(array $tags): void {}
}

Yii::createObject(['class' => Feed::class, 'limit' => '20', 'tags' => ['news']]); // valid
Yii::createObject(['class' => Feed::class, 'limit' => 'all', 'tags' => 'news']);  // two reported
```

The second call throws `TypeError` at runtime. Each option is reported at the same line as an unknown option would be,
with the identifier `yii2.createObject.invalidOptionType`. The rule analyses exactly the calls and classes described for
the unknown option rule, and a name with no public property or setter is left to that rule.

Yii assigns a public, non-static property directly, even when a setter with the same name exists. Any other option
reaches `__set()`, which calls the public or protected `set` method; the value is checked against its first parameter.
For a `yii\base\Component` subclass, `__set()` calls the setter before it considers events and behaviors, so these
checks also apply when the class attaches behaviors; an option that only a behavior can receive is not checked.
`yii\BaseYii`, `yii\base\BaseObject` and `yii\base\Component` don't declare `strict_types`, so PHP coerces scalar
values: `'20'` is accepted by an `int` property, `5` by a `string` one and any scalar by a `bool` one. A value is
reported only when no value of its native type can be coerced to the native type of the property or parameter, as with
`'all'` for `int`, `''` or `'1abc'` for `int` or `float`, an array for a scalar, a scalar for an array, `null` for a
non-nullable type, or an object of an unrelated class.

The rule stands aside when the rejection isn't certain:

- PHPDoc types of the property, the parameter or the value are not consulted, so `@var non-empty-string` doesn't make
  `''` invalid and a value documented only by `@return` or `@param` is treated as its native type;
- a value whose native type is only partly incompatible, such as a `string` parameter or `'abc'|5`, is not reported;
- an object is never reported for a type that accepts `string`, because a subclass or an internal class can be converted
  to a string;
- a string or an array is never reported for a `callable` parameter, because the function or method it names can exist
  only at runtime;
- read-only, hooked and asymmetric-visibility properties, private setters, setters without parameters and options with
  an empty name are not checked;
- some values that PHP rejects are not reported either: `INF` or `NAN` for `int`, and scalar values for a `true` or
  `false` type, which PHP doesn't coerce;
- a value that may be a `yii\di\Instance` reference is also checked as the object or `null` that the container may
  resolve it to before the assignment: Yii resolves references among the options of the configuration, and in a
  constructor argument only when the container's `resolveArrays` is enabled at runtime.

The result cache reanalyses a call when the native type of the property or setter changes, including one inherited from
a parent class or trait, and when a class named in that type changes.

## Options of `new` constructions

Two more opt-in rules check the configuration passed to the constructor of a `yii\base\BaseObject` subclass written with
`new`. They are independent of each other and of the `Yii::createObject()` rules; enable either or both:

```neon
parameters:
    yii2:
        rules:
            newInvalidOptionType: true
            newUnknownOption: true
```

```php
final class Feed extends yii\base\BaseObject
{
    public int $limit = 10;
}

new Feed(['limit' => '20']);                         // valid
new Feed(['limit' => 'all', 'timeoutSeconds' => 5]); // two reported
new Feed(config: ['timeoutSeconds' => 5]);           // reported
```

`timeoutSeconds` is reported with the identifier `yii2.new.unknownOption` and `'all'` with `yii2.new.invalidOptionType`,
at the line of each key; a configuration that comes from a constant or a variable is reported at the line of `new`.
Names, values, `yii\base\Component` options and the proof of the configuration follow the
[unknown option](#unknown-yiicreateobject-options) and [option type](#invalid-yiicreateobject-option-types) rules of
`Yii::createObject()`, with these differences, which match what `new` does at runtime:

- `new` doesn't use `Yii::$container`: a container binding of the class name, an alias, a factory, a singleton, and the
  options or params of a definition change neither the constructed class nor the checks;
- the inherited constructor passes every key to `Yii::configure()`, so `class`, `__class` and `__construct()` are
  ordinary property names, reported when the class has no public property or setter for them;
- a `yii\di\Instance` value is assigned as that object, not resolved, and is reported for a property or setter whose
  native type can't hold it;
- Yii assigns the options in files without `strict_types`, so weak coercion applies even when the file that writes `new`
  declares `strict_types=1`.

The rules analyse a construction only when:

- the class is written as a name: a fully qualified or imported name, `self`, `parent`, or `static` in a final class;
- the class is instantiable, inherits the constructor of `yii\base\BaseObject` and inherits `__set()` from
  `yii\base\BaseObject` or `yii\base\Component`;
- the configuration is the only argument, passed by position or by its name `config`, without unpacking.

They stand aside for a class held in a variable or an expression, anonymous classes, `self`, `parent` and `static`
inside a closure or an arrow function, `static` in a class that isn't final, and any custom constructor, even one that
calls `parent::__construct($config)`. They also stand aside for unpacked arguments, extra arguments, named arguments
other than `config`, and configurations whose native type isn't one array shape with string keys; PHPStan's own rules
report malformed argument lists. Changing the constructed class, a parent class or a trait it uses, or the `behaviors`
of the analysis configuration for a `yii\base\Component` subclass reanalyses the construction; changing a container
binding doesn't.

## Options of widget calls

Two more opt-in rules check the configuration passed to `widget()` and `begin()` of a `yii\base\Widget` subclass. They
are independent of each other and of the `Yii::createObject()` and `new` rules; enable either or both:

```neon
parameters:
    yii2:
        rules:
            widgetInvalidOptionType: true
            widgetUnknownOption: true
```

```php
class Feed extends yii\base\Widget
{
    public int $limit = 10;
}

Feed::widget(['limit' => '20']);                         // valid
Feed::widget(['limit' => 'all', 'timeoutSeconds' => 5]); // two reported
Feed::begin(config: ['timeoutSeconds' => 5]);            // reported
```

`timeoutSeconds` is reported with the identifier `yii2.widget.unknownOption` and `'all'` with
`yii2.widget.invalidOptionType`, at the same lines as for `Yii::createObject()`. The message names the called class and
method and the class Yii creates.

Both methods set the `class` key of the configuration to the called class and pass it to `Yii::createObject()`, so the
rules check it as the [unknown option](#unknown-yiicreateobject-options) and
[option type](#invalid-yiicreateobject-option-types) rules check that array:

- a `class` key written in the configuration is replaced and never changes the created class;
- a `__class` key that names one class selects that class instead;
- a container definition of the called class is followed as for `Yii::createObject()`, so the options are checked
  against the class the container builds, and the rules stand aside on the definitions it stands aside on;
- `__construct()` passes constructor arguments, and a `yii\di\Instance` value is checked as the object or `null` it may
  resolve to.

The rules analyse a call only when:

- the class is written as a fully qualified or imported name of `yii\base\Widget` or a subclass, and the method is
  written as `widget` or `begin`;
- the class inherits that method from `yii\base\Widget`;
- the configuration is the only argument, passed by position or by its name `config`, without unpacking;
- the class Yii creates is an instantiable `yii\base\Widget` subclass that inherits the constructor of
  `yii\base\BaseObject` and `__set()` from `yii\base\Component`.

They stand aside for `self::`, `parent::` and `static::` calls, a class or method name held in a variable or an
expression, calls through an object, first-class callables such as `Feed::widget(...)`, and a class that overrides the
method, directly or through a trait, even one that calls the parent method. They also stand aside for a `__class` key
that is `null` or not one class name, a created class that isn't a widget, and configurations whose native type isn't
one array shape with string keys. Calls without a configuration report nothing. PHPStan's own rules report malformed
argument lists. Changing the called or created class, or a container definition of the called class, reanalyses the
call.

## Options of external actions

Two more opt-in rules check the array definitions that a controller's `actions()` method returns, which
`yii\base\Controller::createAction()` passes to `Yii::createObject($definition, [$id, $this])`. They are independent of
each other and of the other option rules; enable either or both:

```neon
parameters:
    yii2:
        rules:
            externalActionInvalidOptionType: true
            externalActionUnknownOption: true
```

```php
final class FeedAction extends yii\base\Action
{
    public int $limit = 10;

    public function run(): string
    {
        return '';
    }
}

final class SiteController extends yii\web\Controller
{
    public function actions(): array
    {
        return [
            'feed' => ['class' => FeedAction::class, 'limit' => '20'],                          // valid
            'all' => ['class' => FeedAction::class, 'limit' => 'all', 'timeoutSeconds' => 5], // two reported
        ];
    }
}
```

`timeoutSeconds` is reported with the identifier `yii2.externalAction.unknownOption` and `'all'` with
`yii2.externalAction.invalidOptionType`, at the line of each key; a definition that comes from a constant or a variable
is reported at the line of its action ID, and a whole map that comes from one at the line of `return`. The message names
the action ID, the controller and the class Yii creates.

Names, values and `yii\base\Component` options follow the [unknown option](#unknown-yiicreateobject-options) and
[option type](#invalid-yiicreateobject-option-types) rules of `Yii::createObject()`, including the selection by
`__class` or `class` and the container definitions they follow, with these differences, which match what
`createAction()` does at runtime:

- the class Yii creates must be an instantiable `yii\base\Action` subclass that inherits the constructor of
  `yii\base\Action` and `__set()` from `yii\base\Component`;
- that constructor assigns `id` and `controller` before the configuration, so both must be plain public properties,
  without property hooks or a trait that declares them;
- the controller supplies the constructor arguments, so a `__construct()` key, or a container definition with params or
  a `__construct()` key, makes the rules stand aside.

The rules analyse a `return` statement only when:

- it belongs to the `actions()` method of a concrete, non-anonymous `yii\base\Controller` subclass, outside a trait, a
  closure or an arrow function;
- the controller inherits `createAction()` from `yii\base\Controller`;
- the map is an array literal or has a native type that is exactly one array shape, and the action ID is a non-empty
  string.

They stand aside for abstract controllers and for controllers that only inherit their `actions()` method, an override of
`createAction()` in the controller or a parent, dynamic or integer action IDs, maps that are merged, unpacked,
conditional or documented only by PHPDoc, and definitions that are class names, callables or `null`. A concrete
controller is checked even when a subclass could replace its map. The limits of native types described for
`Yii::createObject()` apply to maps and definitions held in variables. Changing the created class, a container
definition, the `behaviors` of the analysis configuration, the controller or a parent controller reanalyses the method.

## Invalid behavior definitions

An opt-in rule reports behavior definitions that Yii can't attach, in the lists `yii\base\Component::behaviors()`
returns and in the arguments of `attachBehavior()` and `attachBehaviors()`:

```neon
parameters:
    yii2:
        rules:
            behaviorInvalidDefinition: true
```

```php
final class Formatter extends yii\base\BaseObject {}

final class Article extends yii\base\Component
{
    public function behaviors(): array
    {
        return [
            'timestamp' => yii\behaviors\TimestampBehavior::class, // valid
            'formatter' => Formatter::class,                        // reported
        ];
    }

    public function init(): void
    {
        parent::init();

        $this->attachBehavior('settings', ['enabled' => true]); // reported: no class element
    }
}
```

Yii passes each definition that isn't a `yii\base\Behavior` instance to `Yii::createObject()` and calls `attach()` on
the result without checking its type. The rule reports, with the identifier `yii2.behavior.invalidDefinition`:

- a class name or configuration array whose class, after the container definitions, aliases and `Instance` references of
  the analysis configuration, is a concrete class that is not a `yii\base\Behavior`, has no public `attach()` method and
  no `__call()` other than the one `yii\base\BaseObject` declares, which always throws;
- a configuration array without a non-null `__class` or `class` element that Yii can't call as a callable. In
  `behaviors()` this is reported only when the method's declared return type accepts the array, since Yii's own
  declaration already makes PHPStan report it.

`__class` takes precedence over `class`, as in `Yii::createObject()`. A class that isn't a behavior but declares a
public `attach()`, static or not, is attached by Yii and is not reported; a missing `detach()` fails only on a later
replacement or detach.

Literal list items are reported at their own line, `attachBehavior()` definitions at the argument line, and lists that
native types prove constant at the line of `return` or of the call. The message names the behavior, the owner or
receiver, the method, and the defined and resolved classes.

The rule stands aside for container factories, singletons, closures, invocable objects, callable arrays, class names
unknown to the analysis, abstract classes and interfaces without a binding, `yii\base\Component` results, unions and
other values that are not one constant, unpacked, merged or dynamic lists, `behaviors()` declared in a trait or returned
from a closure, owners that override `ensureBehaviors()`, and receivers that override the attach method. Changing a
container definition or the declaration of the resolved class or one of its parents reanalyses the affected file.

## Invalid `as` behavior options

An opt-in rule reports the `as name` options of `Yii::createObject()` configurations that the created component rejects
or can't attach:

```neon
parameters:
    yii2:
        rules:
            behaviorInvalidAsDefinition: true
```

With the classes of the previous example:

```php
$article = Yii::createObject([
    'class' => Article::class,
    'as timestamp' => yii\behaviors\TimestampBehavior::class, // valid
    'as formatter' => Formatter::class,                       // reported: not a behavior subclass
    'as settings' => ['enabled' => true],                     // reported: no class element
]);
```

`yii\base\Component::__set()` checks the supplied value before the container resolves it. It accepts a
`yii\base\Behavior` instance, a closure, or a class name or configuration class that is a strict subclass of
`yii\base\Behavior`, and throws for anything else, even when the container binds that class to a behavior. The rule
reports, with the identifier `yii2.behavior.invalidAsDefinition`:

- a class name, or the class of a configuration array, that the analysis knows and that is not a strict subclass of
  `yii\base\Behavior`, including `yii\base\Behavior` itself, interfaces and classes with a public `attach()` method;
- a configuration array without a non-null `__class` or `class` element, including callable arrays;
- a value that is neither a string nor an array and can't be a behavior, a closure or an `ArrayAccess` object, such as
  `null`, an integer or `new Formatter()`;
- an accepted class that the container definitions of the analysis configuration resolve to a concrete class that is
  neither a behavior nor invocable, since `attachBehavior()` then rejects the created object.

`__class` takes precedence over `class`, and an element whose value is `null` doesn't count, as in `Component::__set()`.
Options are reported at their key, and configurations that native types prove constant at the line of the call. The
message names the option, the component, and the defined and resolved classes.

The rule analyses only the `Yii::createObject()` configurations whose created class and options the
[unknown option rule](#unknown-yiicreateobject-options) can prove, on components that inherit `__set()` from
`yii\base\Component`; `new`, widget, action, application configuration and direct assignments are not analysed. It
stands aside for class names unknown to the analysis, container IDs included, abstract behaviors, unions and other
values that are not one constant, closures, possible `ArrayAccess` objects, container factories, singletons, alias
cycles, abstract or invocable container results and, for container results only, components that override
`attachBehavior()`. Changing a container definition, the declaration of a named or resolved class or one of its parents,
or an `attachBehavior()` override of the component reanalyses the affected file.

## Constants and result cache

`extension.neon` registers `YII_DEBUG`, `YII_ENV` and the three environment flags as `dynamicConstantNames`. This
changes how PHPStan reasons about their values; it does not define them. The shipped bootstrap defines four analysis
defaults as described in [installation](installation.md#load-what-the-analysis-configuration-needs). Add custom constant
names alongside the Yii entries if needed; PHPStan merges list parameters across included configurations.

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

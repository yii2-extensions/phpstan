# Usage examples

These recipes show inferred types using the analysis setup from [installation](installation.md). Adapt the classes
and definitions to your application.

## Application components and params

In `config/phpstan.php`, describe the values and component definitions that analysis should see:

```php
<?php

declare(strict_types=1);

return [
    'params' => [
        'adminEmail' => 'admin@example.com',
        'maxItems' => 100,
        'nested' => ['enabled' => true],
    ],
    'components' => [
        'exampleView' => yii\web\View::class,
        'typedView' => static fn(): yii\web\View => new yii\web\View(),
        'untypedView' => static fn() => new yii\web\View(),
        'request' => ['cookieValidationKey' => 'analysis-only'],
    ],
    'container' => [
        'definitions' => [
            'view.real' => ['class' => yii\web\View::class],
            'view.alias' => 'view.real',
        ],
        'singletons' => [
            'sharedView' => static fn(): yii\web\View => new yii\web\View(),
        ],
    ],
];
```

Point `parameters.yii2.config_path` at that file and analyze from the application root:

```php
$email = Yii::$app->params['adminEmail'];          // string
$limit = Yii::$app->params['maxItems'];            // int
$enabled = Yii::$app->params['nested']['enabled']; // bool
$view = Yii::$app->get('typedView');               // yii\web\View
$unknown = Yii::$app->get('untypedView');          // object
$request = Yii::$app->request;                    // yii\web\Request
```

Scalars are widened, so `100` becomes `int`. The params shape also applies to inherited module params. A closure
needs a single class return type for precise service inference; its body alone does not provide that type.
Configuring a core component such as `request` without a class preserves the application's annotated property type.

## Separate console analysis

For a console description, select `yii\console\Application` explicitly under `phpstan.application_type` and avoid
reusing Web-only core component definitions. Its `request` property is then `yii\console\Request`. Run a separate
NEON configuration with the console paths. See [application types](configuration.md#web-console-and-custom-applications).

## Container and service locator

With the container and component definitions above:

```php
$view = Yii::$container->get('view.alias');       // yii\web\View
$shared = Yii::$container->get('sharedView');     // yii\web\View
$optional = Yii::$app->get('exampleView', false); // yii\web\View|null
$unknown = Yii::$app->get('view.alias');          // object
```

The alias belongs to the container, while `exampleView` is a component ID. Unknown component IDs can throw at
runtime. Passing `false` or a non-constant boolean as `$throwException` adds `null` to a resolved component type.

## Behaviors

Suppose `app\behaviors\TagBehavior` provides a string `label` property and a `summarize(): string` method, and the
application attaches it to `app\components\TaggedComponent`. Add that same relationship to the analysis description:

```php
'behaviors' => [
    app\components\TaggedComponent::class => [app\behaviors\TagBehavior::class],
],
```

Then the owner members are inferred from the behavior's native declarations and PHPDoc:

```php
$component = new app\components\TaggedComponent();
$label = $component->label;        // string
$summary = $component->summarize(); // string
```

The analysis-only map never attaches the behavior. Keep it consistent with actual runtime attachments, including
conditional attachments. See [behavior configuration](configuration.md#behaviors).

## Active Record row shapes

Suppose `app\models\Article` declares `@property int $id` and `@property string $title`, and its `find()` returns a
custom query with a `published(): static` scope:

```php
use app\models\Article;

$article = Article::find()->published()->one(); // Article|null
$articles = Article::find()->all();             // array<Article>
$rows = Article::find()->asArray()->all();      // array<array{id: int, title: string}>

if ($article !== null) {
    $title = $article->getAttribute('title');   // string
}
```

The extension uses model `@property` tags, including tags inherited from parent classes, traits and interfaces. The
nearest declaration wins; framework base-model tags are excluded. This is annotation-based inference, not inspection
of the database schema, SQL projection or selected columns. Keep annotations consistent with real data.

A relation tag becomes an optional key containing related rows, and a read-only tag also produces an optional key.
Guard optional keys with `??`, `isset()` or `array_key_exists()`, even when a query eager-loads a relation. Write-only
tags do not produce row keys. Value-object/enum tags keep their annotated types even when a database row contains
raw scalar values.

Yii supplies generic PHPDoc for basic `findOne()`, `findAll()`, `hasOne()`, `hasMany()`, `one()` and `all()` calls.
The extension adds configuration-dependent types, row shapes, attribute types and unannotated custom-query binding.

## Custom Active Query classes

For a Gii-style query without generic tags, `Article::find()` binds the query to the model at the call site.
PHPStan prints an intersection such as `ArticleQuery&yii\db\ActiveQuery<Article>`. Fluent Yii methods and scopes
returning `static` or `$this` keep the binding; subclasses inheriting `find()` bind to the subclass.

A query reached through a typed parameter/property or a scope returning `self` no longer has that call-site binding.
It can recover a model from its own (or inherited) `one()` and `all()` overrides when they name exactly one Active
Record class, including Gii's `@return Invoice|array|null` and `@return Invoice[]|array`. Invalid alternatives such as
`false`, nullable `all()` results or nullable elements do not establish a model. With neither those overrides nor
generic PHPDoc, a separately typed query keeps Yii's broader answer.

Use PHPDoc where the model cannot be recovered:

- Add `@extends \yii\db\ActiveQuery<Model>` to a query used independently of `Model::find()`.
- Use `@return \yii\db\ActiveQuery<Comment>` on a relation getter whose generated union otherwise widens the return.
- Prefer `static` or `@return $this` for scopes that preserve a subclass binding. `self` can fall back to the model
  named by the query's overrides, losing the subclass. A scope without a return type can lose inference entirely.
- A union of different custom query classes retains PHPStan's own answer.

For an intentionally generic query, declare its template and `@extends ActiveQuery<T>`, and have the model's `find()`
return `ModelQuery<static>`. Preserve existing generic PHPDoc rather than replacing it with the unannotated recipe.
A generic query keeps its class after `asArray()`; a non-generic query becomes `ActiveQuery<array{...}>`, so call its
scopes before `asArray()`.

Use the returned query when changing modes: `$rows = $query->asArray()->all()`. A bare `$query->asArray();` statement
or a mode change hidden inside the query class does not update the original variable's inferred type. Once a model
is inferred, `one()` no longer includes array rows unless the analyzed chain selects `asArray()`. Check the runtime
mode before removing a defensive array check reported as redundant after upgrading.

## Headers and logger messages

```php
$headers = new yii\web\HeaderCollection();
$host = $headers->get('Host');                  // string|null
$ids = $headers->get('X-Request-ID', [], false); // array<string>
$messages = yii\log\Target::filterMessages([]); // array{}
```

Logger and target messages are list tuples containing payload, integer level, string category, float timestamp,
trace list and an optional memory field. Payload types depend on the installed Yii revision. Filtering preserves
input key and tuple types while permitting an empty result.

See [configuration](configuration.md) for unresolved definitions and [migration](migration.md) for changes to old
inferred types and extension APIs.

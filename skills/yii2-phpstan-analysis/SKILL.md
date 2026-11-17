---
name: yii2-phpstan-analysis
description: Configure yii2-extensions/phpstan in a Yii application, diagnose its component, params, behavior, container and Active Record inference, enable its opt-in configuration rules, or migrate its PHPStan setup. Not for unrelated PHPStan errors, general Yii runtime configuration, or changes to the extension's own source.
license: BSD-3-Clause
metadata:
    guide: https://yii2-framework.github.io/docs/extensions/phpstan/
---

# Analyze a Yii application with PHPStan

Use the installed package revision and its
[requirements](https://yii2-framework.github.io/docs/extensions/phpstan/installation/index.md).

## Read the relevant contract

In a root package checkout, use `composer show --self --format=json` for metadata. For installed dependencies, resolve
the real path and read the configured vendor directory's `composer/installed.php` (`versions[<package>]`):
`install_path`, `pretty_version` and Composer's selected `reference`. Do not blindly prefer `source.reference` over
`dist.reference`. For any live package checkout, including a symlinked path install, inspect its current `HEAD` and
files even when clean; report metadata mismatches and let the actual files govern. Uncommitted changes have no matching
SHA. Otherwise use the selected reference or a verified matching tag only after establishing its source provenance.
Compare `composer.lock`; it may differ from the inspected installation. Identify a lock-only state when no package files
are installed, and resolve dependencies separately. Use hosted pages, catalogs or snapshots only when their source
revision matches.

Inspect existing NEON includes, analysis bootstrap and selected PHP configuration before editing. Prefer matching
supplied Markdown or package `docs/`. Otherwise check these published pages against the resolved revision:

- Setup and bootstrap: <https://yii2-framework.github.io/docs/extensions/phpstan/installation/index.md>.
- Components, params, behaviors, container definitions and opt-in rules:
  <https://yii2-framework.github.io/docs/extensions/phpstan/configuration/index.md>.
- Inference recipes and query limitations: <https://yii2-framework.github.io/docs/extensions/phpstan/examples/index.md>.
- 0.4-to-0.5 inferred type and extension API changes:
  <https://yii2-framework.github.io/docs/extensions/phpstan/migration/index.md>.

For documentation outside the local checkout, use the package source at
`https://github.com/yii2-extensions/phpstan/tree/<installed-ref>/docs`, substituting the resolved reference. If guides
are unavailable or offline, inspect local source/PHPDoc and the changelog, and report the gap. For exact APIs, inspect
the relevant type and its PHPDoc in the resolved package files. Use the site's `llms.txt` when the needed page is
unknown.

## Establish a reproducible analysis

Register `extension.neon` once, either through an enabled extension-installer plugin or a manual include. Preserve other
extensions/settings and use existing application paths. `yii2.config_path` is resolved relative to the process working
directory, not the NEON file; use the application root or an absolute readable `.php` file.

The PHP description is executed with `require`, possibly more than once, and must return an array. Keep it
deterministic, idempotent and independent of database connections and application startup. The `phpstan` and behavior
map describe analysis types. `bootstrapFiles` supply autoloading, aliases and global `Yii` to analyzed code, but are not
guaranteed to run before the description; PHPStan 2.3.1 runs them later. The description must load its own
prerequisites, for example with `require_once` of the same idempotent bootstrap. Load `Yii.php` with `require_once`; a
plain `require` after `Yii` is loaded redeclares it. The extension preserves predefined constants, defines four analysis
flags before requiring the description, and does not define `YII_ENV`.

Select console/custom application classes in `phpstan.application_type` inside the PHP array; the default is Web. The
value must be `yii\base\Application` or a subclass that PHPStan can reflect (autoloaded, analyzed, loaded by
`bootstrapFiles` or by the description). An internal error starting with `Application type '<value>'` means a missing,
stub-only or non-application class; fix the value or make the class discoverable. Use separate NEON runs for Web and
console paths. There is one application class and one inherited module params shape per analysis. Params scalars widen
to their types; no params means an unshaped array.

## Diagnose the smallest missing type

Distinguish component IDs from container service IDs. `Yii::$app->get()` and other locators resolve components; use
`Yii::$container->get()` for container services. An analysis map does not configure a new locator at runtime. Unknown
component IDs can throw at runtime.

Class strings, class-key arrays and factories with a single non-null class return type can supply precise types. An
untyped/union/nullable factory gives `object`; add a class return type when the factory guarantees one. A core component
configured without a class preserves the application's annotated property type. Consult definition forms before changing
aliases, `Instance` references, invocable objects or container subsection order.

Keep the behavior class map consistent with actual runtime attachments; it supplies reflection, not attachment. Generic
component mappings supply one class parameter to application property access. They require a truly generic component and
a class-valued definition key. User identity may still be null; retain appropriate guards.

Yii's PHPDoc supplies ordinary Active Record/query generics. This extension adds row shapes, attribute types and binding
of unannotated custom queries. Shapes come from model property tags, not the database or SQL projection. Relations and
read-only row keys are optional; guard them. Keep value-object tags consistent with raw row values. Use `static`/`$this`
scopes to preserve call-site query binding and use the query returned by `asArray()`. A query used independently of
`Model::find()` needs recoverable `one()`/`all()` overrides or generic PHPDoc; widened relation unions and unions of
query classes retain PHPStan's broader answer. Preserve existing intentional generic query types.

For upgrades, read the migration guide before removing annotations or runtime checks. It covers static stubs, removed
APIs and inferred type changes. Including the extension outside `vendor/` also changes project-stub validation. PHPStan
2.3 tracks configuration-derived cache dependencies; routine cache clearing is unnecessary after edits.

## Enable the opt-in rules

`parameters.yii2.rules` holds 14 diagnostic rules, all `false` by default. They report configuration that Yii rejects or
cannot assign at runtime: unknown options and option types for `Yii::createObject()`, `new` constructions of
`BaseObject` classes, `Widget::widget()`/`begin()`, `Controller::actions()` and behavior configurations; invalid
`behaviors()`, `attachBehavior()` and `as` behavior definitions; incomplete `Model::rules()` entries and invalid
validator types. Enable only the keys the task needs, in the application's NEON configuration:

```neon
parameters:
    yii2:
        rules:
            createObjectUnknownOption: true
```

Keys are camel-cased (`newUnknownOption`); error identifiers are dotted (`yii2.new.unknownOption`). Read the
configuration guide's section for a rule before acting on its report, and ignore an intended case narrowly by identifier
and path.

## Intended behavior

These look like bugs and are package contracts; do not "fix" them in package or application code:

- `Yii::$app->get()` with a container-only ID or alias is `object`; only `Yii::$container->get()` resolves it. With
  `false` as `$throwException`, a configured component is nullable (`yii\web\View|null`).
- Params scalars widen to their types (`'admin@example.com'` is `string`, not a literal); without params,
  `Yii::$app->params` is `array`.
- With the built-in `user: identityClass` mapping, `Yii::$app->user->identity` stays `<identityClass>|null`.
- `asArray()` row shapes mark `@property-read` and relation keys optional, for example `slug?: string`.
- `Yii::$app` is the selected application class, never `null`: from level 4, `isset(Yii::$app)` is reported as
  `isset.property`. Keep the stub contract; code that really runs before the application exists keeps its check and a
  narrow ignore.
- A relative `yii2.config_path` resolves against the working directory: run from elsewhere, analysis stops with
  `Provided config path '<path>' must be a readable PHP file.`
- The option-assignment rules report only when Yii's assignment path is certain; their abstentions include custom
  constructors or `__set()`, container factories and singletons, and PHPDoc-only configurations. Other rule families
  have different boundaries: the validator structure rules require inherited `Model::createValidators()` and
  `getValidators()`, not an inherited constructor or `__set()`. Read each rule's documented scope. No report does not
  prove a configuration valid.

Classes under `src/` implement the extension, including internal helpers. `extension.neon` registers its PHPStan
services; applications configure the extension through NEON and the PHP description, not by calling those services. Code
that extends or registers them directly follows the migration guide's removed and changed API section.

## Verify and report

Run PHPStan with the selected configuration and inspect relevant types using a minimal probe. Check Web and console
separately when both are affected, and remove temporary probes after diagnosis. Use `docs/testing.md` for package checks
and the application's tests for its integration.

Report the installed versions, configurations, inferred types and checks performed, including any runtime checks still
needed. Report dependency resolution, native client discovery, skill activation and PHPStan analysis separately.

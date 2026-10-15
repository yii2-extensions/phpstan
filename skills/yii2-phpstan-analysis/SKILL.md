---
name: yii2-phpstan-analysis
description: Configure yii2-extensions/phpstan in a Yii application, diagnose its component, params, behavior, container and Active Record inference, or migrate its PHPStan setup. Use for this extension rather than unrelated PHPStan errors or general Yii runtime configuration.
license: BSD-3-Clause
metadata:
    guide: https://yii2-framework.github.io/docs/extensions/phpstan/
---

# Analyze a Yii application with PHPStan

Use the installed package revision and its [requirements](https://yii2-framework.github.io/docs/extensions/phpstan/installation/index.md).

## Read the relevant contract

Inspect the application Composer requirements/lock, existing NEON includes, analysis bootstrap and selected PHP
configuration before editing. Prefer supplied local Markdown or the package's `docs/` at that revision. Otherwise
check the relevant published Markdown before relying on it:

- Setup and bootstrap: <https://yii2-framework.github.io/docs/extensions/phpstan/installation/index.md>.
- Components, params, behaviors and container definitions:
  <https://yii2-framework.github.io/docs/extensions/phpstan/configuration/index.md>.
- Inference recipes and query limitations:
  <https://yii2-framework.github.io/docs/extensions/phpstan/examples/index.md>.
- 0.4-to-0.5 inferred type and extension API changes:
  <https://yii2-framework.github.io/docs/extensions/phpstan/migration/index.md>.

For documentation outside the local checkout, use the package source at
`https://github.com/yii2-extensions/phpstan/tree/<installed-ref>/docs` or the published guides. For exact APIs, inspect
source PHPDoc or `https://yii2-framework.github.io/docs/reference/phpstan.json`. Use the site's `llms.txt` when the
needed page is unknown.

## Establish a reproducible analysis

Register `extension.neon` once, either through an enabled extension-installer plugin or a manual include. Preserve
other extensions/settings and use existing application paths. `yii2.config_path` is resolved relative to the process
working directory, not the NEON file; use the application root or an absolute readable `.php` file.

The PHP description is executed with `require` and must return an array. Keep it deterministic and independent of
database connections and application startup. The `phpstan` and behavior map describe analysis types.
Supply autoloading/aliases needed by application classes; load global `Yii` with a minimal bootstrap if needed. The extension preserves predefined constants, defines four
analysis flags, and does not define `YII_ENV`.

Select console/custom application classes in `phpstan.application_type` inside the PHP array; the default is Web.
Use separate NEON runs for Web and console paths. There is one application class and one inherited module params
shape per analysis. Params scalars widen to their types; no params means an unshaped array.

## Diagnose the smallest missing type

Distinguish component IDs from container service IDs. `Yii::$app->get()` and other locators resolve components;
use `Yii::$container->get()` for container services. An analysis map does not configure a new locator at runtime.
Unknown component IDs can throw at runtime.

Class strings, class-key arrays and factories with a single non-null class return type can supply precise types.
An untyped/union/nullable factory gives `object`; add a class return type when the factory guarantees one.
A core component configured without a class preserves the application's annotated property type. Consult definition
forms before changing aliases, `Instance` references, invocable objects or container subsection order.

Keep the behavior class map consistent with actual runtime attachments; it supplies reflection, not attachment.
Generic component mappings supply one class parameter to application property access. They require a truly generic
component and a class-valued definition key. User identity may still be null; retain appropriate guards.

Yii's PHPDoc supplies ordinary Active Record/query generics. This extension adds row shapes, attribute types and
binding of unannotated custom queries. Shapes come from model property tags, not the database or SQL projection.
Relations and read-only row keys are optional; guard them. Keep value-object tags consistent with raw row values.
Use `static`/`$this` scopes to preserve call-site query binding and use the query returned by `asArray()`. A query used
independently of `Model::find()` needs recoverable `one()`/`all()` overrides or generic PHPDoc; widened relation unions
and unions of query classes retain PHPStan's broader answer. Preserve existing intentional generic query types.

For upgrades, read the migration guide before removing annotations or runtime checks. It covers static stubs,
removed APIs and inferred type changes. Including the extension outside `vendor/` also changes project-stub validation.
PHPStan 2.3 tracks configuration-derived cache dependencies; routine cache clearing is unnecessary after edits.

## Verify and report

Run PHPStan with the selected configuration and inspect relevant types using a minimal probe. Check Web and console
separately when both are affected, and remove temporary probes after diagnosis. Use `docs/testing.md` for package
checks and the application's tests for its integration.

Report the installed versions, configurations, inferred types and checks performed, including any runtime checks
still needed. Report dependency resolution, native client discovery, skill activation and PHPStan analysis
separately.

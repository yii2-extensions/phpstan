# PHPStan repository guide

This package is `yii2-extensions/phpstan`, namespace `yii2\extensions\phpstan`. Inspect `composer.json`, the Git diff,
`extension.neon` and the relevant source/tests before changing inference. The current development revision requires PHP >=8.3,
PHPStan ^2.3 and Yii `~2.0.55 || ^22.0@dev`; the selected framework may require newer PHP. Do not copy another
extension's Yii 22-only requirement or widen constraints without verifying compatibility.

Application integration guidance belongs to `skills/yii2-phpstan-analysis/SKILL.md`. That is the canonical skill;
`.agents/skills/` and `.claude/skills/` contain discovery links. Public documentation is English. Keep the README's
logo, badges and organization sections, its quick start concise, and detailed human recipes in `docs/examples.md`. Keep local validation fixtures and skill evaluations private under
ignored `internal/`; never link them from public documentation or include them in skill bundles. Avoid duplicate
PHPDoc usage examples. Keep migration details in `docs/migration.md`, linked from
`UPGRADE.md`. Append new CHANGELOG entries at the end of the current version section.

The analysis PHP configuration is executed by `ServiceMap`; inference does not configure application runtime.
Preserve the distinction between container services and component IDs, unresolved definitions and missing services,
Yii-owned generic types and extension-owned row/configuration inference. Test result-cache dependencies when an
inference change depends on new configuration values or class declarations. Report unrelated runtime problems
instead of refactoring them during documentation work.

Use the commands in `docs/testing.md`. Run both Web and console static configurations, the type inference suite and
executable examples when relevant. Validate Prettier and EditorConfig together with Markdownlint on every edited
file, including skill files and migration notes. Multi-line numbered-list continuations can conflict with the
Markdown indentation rule; prefer headings or bullets instead of disabling the checks. `composer ecs` and
`composer rector` mutate files; use `vendor/bin/ecs check` for verification.

The documentation site consumes this package's `docs/`, `src/` PHPDoc and `skills/`. Do not edit generated site
outputs or its shared navigation/catalog during package-only work; provide the exact catalog proposal to the site
coordinator. Keep dependency resolution, native client discovery, skill activation and successful application
analysis as separately evidenced results.

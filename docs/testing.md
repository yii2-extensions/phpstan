# Testing

Use a source checkout with the development dependencies installed. Their resolved PHP requirements can exceed the
library's PHP 8.1 minimum. Record the actual PHP, PHPStan and Yii versions:

```bash
composer install
composer validate --strict
composer audit
composer show yiisoft/yii2
vendor/bin/phpstan --version
```

The repository's ignored lockfile can describe a different dependency resolution from a fresh checkout. Preserve
Composer advisory blocking; do not bypass an advisory to obtain a passing install. CI pins its compatibility job to Yii 2.0.55
and separately checks Yii 22 on PHP 8.3, 8.4 and 8.5. A local run with one resolution is not the whole
compatibility matrix.

## Package checks

```bash
composer tests
composer static
vendor/bin/phpstan analyse -c phpstan-console.neon --memory-limit=1G
composer check-dependencies
vendor/bin/ecs check
```

PHPUnit compares inferred types through `PHPStan\Testing\TypeInferenceTestCase`, including configuration and cache
dependency cases. Both static configurations matter because `Yii::$app` has one configured application type per
analysis. `composer ecs` applies fixes and `composer rector` refactors files; use them only for intentional changes
and inspect their diffs. This package has no mutation script; do not claim a mutation score from a PHPUnit run.

`composer-require-checker.json` allowlists `yii\log\PsrMessage`, a Yii 22-only logger payload type. The resolver
checks for that class through reflection before including it; the allowlist lets dependency checking run on Yii
2.0.55 as well. Retain the existence guard and version-aware logger tests when changing this entry.

## Verify documented application setups

Adapt the [usage recipes](examples.md) to the application's real classes and configuration. Check both Web and
console analyses when both entry points are affected. Inspect inferred types for the component, params, service or
query being changed, rather than treating a diagnostic-free run as proof of the expected type. Verify actual
behavior attachments and database/HTTP integration separately when changing an application.

## Documentation quality

The pinned reusable workflow in `.github/workflows/quality.yml` runs Prettier 3.8.4, EditorConfig Checker 3.7.0 and
Markdownlint CLI2 0.22.1. Use its repository configurations together:

```bash
npx prettier@3.8.4 --check --config .prettierrc.json --ignore-path .prettierignore '**/*.md' '**/*.{yml,yaml}'
editorconfig-checker -config .github/linters/.editorconfig-checker.json
npx markdownlint-cli2@0.22.1 --config .github/linters/.markdown-lint.yml '**/*.md' '#vendor/**' '#runtime/**'
```

Check all changed public Markdown, PHP, NEON, ignore files and discovery links, not only `docs/`. Verify relative
links and heading anchors. Keep the canonical skill in distribution archives; exclude local validation assets and
checkout client aliases. Use headings or bullets when a numbered-list continuation would introduce indentation
incompatible with EditorConfig. Formatting one tool's output is not proof that the other checks pass.

Native Claude Code, Codex and OpenCode discovery/activation must be checked in their own sessions; document
unperformed checks explicitly. See [AI assistance](agents.md).

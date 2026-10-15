# Testing

Install the development dependencies in a source checkout and record the installed versions:

```bash
composer install
composer validate --strict
composer audit
composer show yiisoft/yii2
vendor/bin/phpstan --version
```

The library's lockfile is ignored; CI resolves dependencies for Yii 2.0.55 and Yii 22 on PHP 8.3, 8.4 and 8.5.
Static analysis targets PHP 8.3 and runs on PHP 8.3 and 8.5 in CI.

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
and inspect their diffs.

`composer-require-checker.json` allowlists `yii\log\PsrMessage`, a Yii 22-only logger payload type. The resolver
checks for that class through reflection before including it; the allowlist lets dependency checking run on Yii
2.0.55 as well. Retain the existence guard and version-aware logger tests when changing this entry.

## Verify documented application setups

Adapt the [usage recipes](examples.md) to the application and inspect the affected types. Run both Web and console
analyses when relevant, and verify runtime behavior attachments or database/HTTP integration when they change.

## Documentation quality

The pinned reusable workflow in `.github/workflows/quality.yml` runs Prettier 3.8.4, EditorConfig Checker 3.7.0 and
Markdownlint CLI2 0.22.1. Use its repository configurations together:

```bash
npx prettier@3.8.4 --check --config .prettierrc.json --ignore-path .prettierignore '**/*.md' '**/*.{yml,yaml}'
editorconfig-checker -config .github/linters/.editorconfig-checker.json
npx markdownlint-cli2@0.22.1 --config .github/linters/.markdown-lint.yml '**/*.md' '#vendor/**' '#runtime/**'
```

Check changed files and relative links. Use headings or bullets when numbered-list continuations conflict with
EditorConfig indentation. For client discovery and activation, see [AI assistance](agents.md).

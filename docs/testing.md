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
Static analysis targets PHP 8.3 and runs on PHP 8.3 and 8.5 in CI, with both Web and console configurations.

## Repository quality checks

Make the CLI tools used by the shared quality workflow available on `PATH`:
actionlint with its ShellCheck helper, codespell, EditorConfig Checker, markdownlint-cli2, Prettier and
yamllint. The command reads the repository's linter, formatter and ignore files. It skips ignored local dependency, runtime and private artifact trees that are absent from a clean CI checkout.

```bash
composer quality
```

## Workflow security checks

Make zizmor and Gitleaks available on `PATH`. Audit the current workflow files
with the same pedantic persona used by the shared security workflow:

```bash
composer zizmor
```

Run that audit and scan Git history with the repository's Gitleaks configuration:

```bash
composer security
```

Zizmor's online audits require GitHub API access. Report offline limitations separately; do not count a skipped audit as passed.

## Package checks

```bash
composer tests
composer static
composer static-console
composer check-dependencies
vendor/bin/ecs check
```

PHPUnit compares inferred types through `PHPStan\Testing\TypeInferenceTestCase`, including configuration and cache
dependency cases. Both static configurations matter because `Yii::$app` has one configured application type per
analysis. `composer static` checks the Web configuration; `composer static-console` checks `phpstan-console.neon`, matching the second CI job. `composer ecs` applies fixes and `composer rector` refactors files; use them only for intentional changes
and inspect their diffs.

The logger resolver looks up the optional Yii 22 `yii\log\PsrMessage` class by name and checks for it through
reflection before including it. The test bootstrap defines the expected payload type for the installed Yii version,
so PHPUnit and direct static checks validate the same fixture on Yii 2.0.55 and Yii 22. Retain the existence guard
and both sets of expectations when changing logger inference.

When checking an isolated consumer, preserve its `composer.json` and configured vendor directory together. PHPStan
uses that manifest to recognize dependency stubs; copying only the vendor tree can trigger project-stub validation.

## Verify documented application setups

Adapt the [usage recipes](examples.md) to the application and inspect the affected types. Run both Web and console
analyses when relevant, and verify runtime behavior attachments or database/HTTP integration when they change.

## Coding standards (ECS)

Check standards without changing files:

```bash
composer ecs-check
```

`composer ecs` applies fixes. Review its changes before keeping them.

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

[Back to documentation](../README.md#documentation)

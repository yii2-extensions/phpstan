# Testing

This package provides a consistent set of [Composer](https://getcomposer.org/) scripts for local validation.

Tool references:

- [Actionlint](https://github.com/rhysd/actionlint) for GitHub Actions workflow linting.
- [Codespell](https://github.com/codespell-project/codespell) for spelling checks.
- [Composer Require Checker](https://github.com/maglnet/ComposerRequireChecker) for dependency definition checks.
- [Easy Coding Standard (ECS)](https://github.com/easy-coding-standard/easy-coding-standard) for coding standards.
- [EditorConfig Checker](https://github.com/editorconfig-checker/editorconfig-checker) for EditorConfig compliance.
- [Gitleaks](https://github.com/gitleaks/gitleaks) for secret scanning.
- [Markdownlint-cli2](https://github.com/DavidAnson/markdownlint-cli2) for Markdown linting.
- [PHPStan](https://phpstan.org/) for static analysis.
- [PHPUnit](https://phpunit.de/) for unit tests.
- [Prettier](https://prettier.io/) for Markdown and YAML formatting checks.
- [Rector](https://github.com/rectorphp/rector) for automated refactoring.
- [Yamllint](https://github.com/adrienverge/yamllint) for YAML linting.
- [Zizmor](https://github.com/zizmorcore/zizmor) for GitHub Actions security audits.

## Automated refactoring (Rector)

Run Rector to apply automated code refactoring. This command modifies files.

```bash
composer rector
```

Preview the refactoring without modifying files.

```bash
composer rector-check
```

## Coding standards (ECS)

Check coding standards without modifying files.

```bash
composer ecs-check
```

Apply coding standard fixes. Review the changes before keeping them.

```bash
composer ecs
```

## Dependency definition check

Verify that runtime dependencies are correctly declared in `composer.json`.

```bash
composer check-dependencies
```

## Repository quality checks

Make the CLI tools used by the shared quality workflow available on `PATH`: actionlint with its ShellCheck helper,
codespell, EditorConfig Checker, markdownlint-cli2, Prettier and yamllint. Run all checks:

```bash
composer quality
```

Each tool also has its own script:

```bash
composer actionlint
composer codespell
composer editorconfig-checker
composer markdownlint
composer prettier
composer yamllint
```

The linter configuration lives in `.github/linters`, and the Prettier configuration in `.prettierrc.json` and
`.prettierignore`. CI uses the same files.

Check changed files and relative links. Use headings or bullets when numbered-list continuations conflict with
EditorConfig indentation.

## Static analysis (PHPStan)

Run static analysis with the Web application configuration (`phpstan.neon`).

```bash
composer static
```

Run static analysis with the console application configuration (`phpstan-console.neon`), matching the second CI job.

```bash
composer static-console
```

Static analysis targets PHP 8.3 and runs on PHP 8.3 and 8.5 in CI, with both configurations.

## Unit tests (PHPUnit)

Run the full type inference suite. The script runs PHPUnit without a PHP memory limit.

```bash
composer tests
```

## Workflow security checks

Make zizmor and Gitleaks available on `PATH`. Audit the current workflow files with the same pedantic persona used by
the shared security workflow:

```bash
composer zizmor
```

Scan Git history for secrets with the repository's Gitleaks configuration:

```bash
composer gitleaks
```

Run both checks. Findings make the command fail; inspect them rather than disabling the checks.

```bash
composer security
```

Zizmor needs GitHub API access for its online audits. If the tool runs offline, report that limit separately. To request
an offline audit explicitly:

```bash
composer zizmor -- --offline
```

Report hosted CI execution and its PHP/OS matrix separately from these local commands. Keep generated reports in ignored
`internal/`.

## Passing extra arguments

Composer scripts support forwarding additional arguments using `--`.

Run PHPUnit with code coverage report generation.

```bash
composer tests -- --coverage-html internal/coverage
```

Run PHPStan with a different memory limit.

```bash
composer static -- --memory-limit=512M
```

## Install and audit

Install the development dependencies in a source checkout and record the installed versions:

```bash
composer install
composer validate --strict
composer audit
composer show yiisoft/yii2
vendor/bin/phpstan --version
```

The library's lockfile is ignored; CI resolves dependencies for Yii 2.0.55 and Yii 22 on PHP 8.3, 8.4 and 8.5.

## Package checks

PHPUnit compares inferred types through `PHPStan\Testing\TypeInferenceTestCase`, including configuration and cache
dependency cases. Both static configurations matter because `Yii::$app` has one configured application type per
analysis, so run both `composer static` and `composer static-console`.

The logger resolver looks up the optional Yii 22 `yii\log\PsrMessage` class by name and checks for it through reflection
before including it. The test bootstrap defines the expected payload type for the installed Yii version, so PHPUnit and
direct static checks validate the same fixture on Yii 2.0.55 and Yii 22. Retain the existence guard and both sets of
expectations when changing logger inference.

When checking an isolated consumer, preserve its `composer.json` and configured vendor directory together. PHPStan uses
that manifest to recognize dependency stubs; copying only the vendor tree can trigger project-stub validation.

## Verify documented application setups

Adapt the [usage recipes](examples.md) to the application and inspect the affected types. Run both Web and console
analyses when relevant, and verify runtime behavior attachments or database/HTTP integration when they change.

For client discovery and activation, see [AI assistance](agents.md).

[Back to documentation](../README.md#documentation)

# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Conventional Commits](https://www.conventionalcommits.org/en/v1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 0.4.2 Under development

- chore: update dependencies and configuration files.
- chore: adopt scaffold tooling and ensure compatibility with `PHPStan` 2.2 and `Yii2` generic types.
- docs: update badge links and remove quality code section from `README.md`.
- docs: normalize PHPDoc headers across `src` and `tests`, and move authorship metadata to `composer.json`.
- fix: harden `ServiceMap::class` config path validation (regular readable `.php` file, resolve symlinks before `require`) to prevent local file disclosure.
- refactor: clean up `ServiceMap::class` by moving Yii environment constants to a shipped `bootstrap.php` (loaded before requiring the configuration file) and simplifying config-section processing.
- ci: migrate GitHub workflows and project status badges to pinned `yii2-framework/actions` reusable workflows with repository-specific quality and security exceptions, renaming the linter workflow to quality.

## 0.4.1 April 05, 2026

- fix: update Rector command in `composer.json` to remove unnecessary 'src' argument.
- feat: replace static stub files with dynamic stub generation for `Yii::$app` type inference, adding support for custom application types.
- chore: remove `sync-metadata` script and `docs/development.md`, update documentation links.
- feat: add `Yii::$app->params` type inference from configuration for precise array shape typing.

## 0.4.0 January 26, 2026

- chore: update `.gitattributes` to exclude additional files from the package.
- chore: exclude `phpstan-console.neon` from the package in `.gitattributes`.
- ci: update workflow actions to use `v1` stable version instead of `main`.
- docs: update `README.md` to include `Behavior` integration section and example usage.
- docs: update `README.md` to enhance badge visibility and improve installation instructions.
- build: bump `php-forge/actions` from `1` to `2`.
- ci: update workflows and documentation for improved CI/CD processes and feature clarity.
- chore: improve `.gitignore` formatting and add missing entries for better clarity.
- build: update `symplify/easy-coding-standard` requirement from `^12.1` to `^13.0`.
- feat: add `php-forge/coding-standard` to development dependencies for code quality checks and add support `PHP 8.5`.

## 0.3.1 August 16, 2025

- refactor: order class elements in methods and properties.
- chore: add case to the ordered class elements in `ECS` configuration.
- docs: correct badge URL formatting in `README.md`.
- docs: add missing `Composer` requirement in installation guide.
- docs: update license badge in `README.md` for correct display and add missing header in `LICENSE.md`.
- fix: correct default stub filename in `StubFilesExtension.php` for accurate PHPStan analysis.
- refactor: refactor dynamic return type inference for `HeaderCollection::get()` method, and add tests for type inference.
- docs: update `CHANGELOG.md` to include recent bugfixes and enhancements for version `0.3.1`.
- chore: update branch alias version in `composer.json` from `0.3.x-dev` to `0.4.x-dev`.

## 0.3.0 June 27, 2025

- refactor: move config fixtures into `config` subdirectory.
- refactor: update `ActiveQuery` and `ActiveRecord` dynamic return type extensions for improved type inference and error handling; remove deprecated `ActiveQueryObjectType` and `ActiveRecordObjectType` classes.
- feat: enhance `DI` container type inference and testing.
- fix: correct exception message formatting in `ServiceMapServiceTest`.
- fix: resolve `Container::get()` type inference for unconfigured classes in config (`ServiceMap`).
- feat: enhance `PHPStan` analysis for `Behavior` type inference and testing.
- docs: refactor `PHPDoc` comments for consistency and clarity.
- refactor: move `ApplicationPropertiesClassReflectionExtension` to `property` directory and add testing.
- test: add tests for session property availability in `Console` and `Web` `Applications`.
- refactor: move `UserPropertiesClassReflectionExtension` to `property` directory and add testing.
- fix: improve `ServiceMap` configuration for application types (`Base`, `Console`, `Web`).
- docs: update `README.md` to enhance clarity and structure of `docs/installation.md`, `docs/configuration.md` and `docs/examples.md`.
- feat: add `ActiveRecordGetAttributeDynamicMethodReturnTypeExtension` to provide precise type inference for `getAttribute()` method calls based on PHPDoc annotations.
- refactor: update `PHPStan` configuration paths and create new config files for improved structure and clarity.
- docs: update documentation for `ServiceMap` and related classes to enhance clarity for Yii Application static analysis.
- docs: add Testing Guide link to `installation` and `examples` documentation.
- docs: reorder Installation section in `README.md` for improved clarity.
- docs: standardize headings and improve clarity in documentation files.
- docs: update documentation for consistency and clarity; change section titles and add strict types declaration.
- chore: update `PHPStan` `tmpDir` config; move `runtime` directory to `root`; update docs.
- ci: remove `OS` and `PHP` version specifications from workflow files for simplification.
- feat: add `ServiceLocatorDynamicMethodReturnTypeExtension` to provide precise type inference for `get()` method.
- docs: clarify exception documentation and improve type inference descriptions in test cases.
- fix: handle generic type components in `ApplicationPropertiesClassReflectionExtension`.
- fix: handle generic type components in `ApplicationPropertiesClassReflectionExtension`.
- chore: update `CHANGELOG.md` for version `0.3.0` release date and adjust `composer.json` for stability settings.
- docs: update `README.md` with new `GitHub` release badge and correct `Yii2` version badge.

## 0.2.3 June 09, 2025

- feat: add support for `PHPStan` Extension Installer.
- feat: add `PHPStan` extension installer instructions and improve `ServiceMap` configuration handling.
- fix: fix error handling in `ServiceMap` for invalid configuration structures and add corresponding test cases.
- refactor: refactor component handling in `ServiceMap` to improve variable naming and streamline logic.
- feat: enable strict rules and bleeding edge analysis, and update `README.md` with strict configuration examples.
- fix: fix `ActiveRecordDynamicStaticMethodReturnTypeExtension` type inference for `ActiveQuery` support, and fix phpstan errors max lvl in tests.
- fix: fix property reflection in `UserPropertiesClassReflectionExtension` to support `identityClass` resolution and improve type inference for `user` component properties.

## 0.2.2 June 04, 2025

- fix: make `$configPath` optional in constructor `ServiceMap` class and update docs `README.md`.
- fix: update the path to `Yii.php` in the `stubFiles` configuration for correct referencing.
- fix: improve `Yii2` integration component property handling.

## 0.2.1 June 03, 2025

- fix: update licenses, docs, configuration, and reflection/type logic.
- docs: update `CHANGELOG.md` date for version `0.2.1` and format `phpunit.xml.dist`.

## 0.2.0 June 02, 2025

- feat: upgrade to `PHPStan` `2.1`.
- feat: enhance `PHPStan` integration with yii2 extensions.
- ci: consolidate `PHPUnit` workflows and update `README.md` with `Yii2` version badges.
- docs: correct badge label formatting for `Yii2` version in `README.md`.
- ci: remove duplicate concurrency settings from phpunit-compatibility job in `build.yml`.
- docs: update changelog for version `0.2.0` with recent enhancements and bugfixes.
- docs: add usage instructions and configuration details for `phpstan.neon` in `README.md`.

## 0.1.0 February 27, 2024

- feat: initial release.

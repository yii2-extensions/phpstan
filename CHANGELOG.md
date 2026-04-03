# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Conventional Commits](https://www.conventionalcommits.org/en/v1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 0.4.1 Under development

- fix: update Rector command in `composer.json` to remove unnecessary 'src' argument (#81).
- feat: replace static stub files with dynamic stub generation for `Yii::$app` type inference, adding support for custom application types.
- chore: remove `sync-metadata` script and `docs/development.md`, update documentation links.

## 0.4.0 January 26, 2026

- chore: update `.gitattributes` to exclude additional files from the package (#71).
- chore: exclude `phpstan-console.neon` from the package in `.gitattributes` (#72).
- ci: update workflow actions to use `v1` stable version instead of `main` (#73).
- docs: update `README.md` to include `Behavior` integration section and example usage (#74).
- docs: update `README.md` to enhance badge visibility and improve installation instructions (#75).
- build: bump `php-forge/actions` from `1` to `2` (#76).
- ci: update workflows and documentation for improved CI/CD processes and feature clarity (#77).
- chore: improve `.gitignore` formatting and add missing entries for better clarity (#78).
- build: update `symplify/easy-coding-standard` requirement from `^12.1` to `^13.0` (#79).
- feat: add `php-forge/coding-standard` to development dependencies for code quality checks and add support `PHP 8.5` (#80).

## 0.3.1 August 16, 2025

- refactor: order class elements in methods and properties (#62).
- chore: add case to the ordered class elements in `ECS` configuration (#63).
- docs: correct badge URL formatting in `README.md` (#64).
- docs: add missing `Composer` requirement in installation guide (#65).
- docs: update license badge in `README.md` for correct display and add missing header in `LICENSE.md` (#66).
- fix: correct default stub filename in `StubFilesExtension.php` for accurate PHPStan analysis (#67).
- refactor: refactor dynamic return type inference for `HeaderCollection::get()` method, and add tests for type inference (#68).
- docs: update `CHANGELOG.md` to include recent bugfixes and enhancements for version `0.3.1` (#69).
- chore: update branch alias version in `composer.json` from `0.3.x-dev` to `0.4.x-dev` (#70).

## 0.3.0 June 27, 2025

- refactor: move config fixtures into `config` subdirectory (#35).
- refactor: update `ActiveQuery` and `ActiveRecord` dynamic return type extensions for improved type inference and error handling; remove deprecated `ActiveQueryObjectType` and `ActiveRecordObjectType` classes (#36).
- feat: enhance `DI` container type inference and testing (#37).
- fix: correct exception message formatting in `ServiceMapServiceTest` (#38).
- fix: resolve `Container::get()` type inference for unconfigured classes in config (`ServiceMap`) (#39).
- feat: enhance `PHPStan` analysis for `Behavior` type inference and testing (#40).
- docs: refactor `PHPDoc` comments for consistency and clarity (#41).
- refactor: move `ApplicationPropertiesClassReflectionExtension` to `property` directory and add testing (#42).
- test: add tests for session property availability in `Console` and `Web` `Applications` (#43).
- refactor: move `UserPropertiesClassReflectionExtension` to `property` directory and add testing (#44).
- fix: improve `ServiceMap` configuration for application types (`Base`, `Console`, `Web`) (#45).
- docs: update `README.md` to enhance clarity and structure of `docs/installation.md`, `docs/configuration.md` and `docs/examples.md` (#46).
- feat: add `ActiveRecordGetAttributeDynamicMethodReturnTypeExtension` to provide precise type inference for `getAttribute()` method calls based on PHPDoc annotations (#47).
- refactor: update `PHPStan` configuration paths and create new config files for improved structure and clarity (#48).
- docs: update documentation for `ServiceMap` and related classes to enhance clarity for Yii Application static analysis (#49).
- docs: add Testing Guide link to `installation` and `examples` documentation (#50).
- docs: reorder Installation section in `README.md` for improved clarity (#51).
- docs: standardize headings and improve clarity in documentation files (#52).
- docs: update documentation for consistency and clarity; change section titles and add strict types declaration (#53).
- chore: update `PHPStan` `tmpDir` config; move `runtime` directory to `root`; update docs (#54).
- ci: remove `OS` and `PHP` version specifications from workflow files for simplification (#55).
- feat: add `ServiceLocatorDynamicMethodReturnTypeExtension` to provide precise type inference for `get()` method (#56).
- docs: clarify exception documentation and improve type inference descriptions in test cases (#57).
- fix: handle generic type components in `ApplicationPropertiesClassReflectionExtension` (#58).
- fix: handle generic type components in `ApplicationPropertiesClassReflectionExtension` (#59).
- chore: update `CHANGELOG.md` for version `0.3.0` release date and adjust `composer.json` for stability settings (#60).
- docs: update `README.md` with new `GitHub` release badge and correct `Yii2` version badge (#61).

## 0.2.3 June 09, 2025

- feat: add support for `PHPStan` Extension Installer (#25).
- feat: add `PHPStan` extension installer instructions and improve `ServiceMap` configuration handling (#26).
- fix: fix error handling in `ServiceMap` for invalid configuration structures and add corresponding test cases (#27).
- refactor: refactor component handling in `ServiceMap` to improve variable naming and streamline logic (#28).
- feat: enable strict rules and bleeding edge analysis, and update `README.md` with strict configuration examples (#29).
- fix: fix `ActiveRecordDynamicStaticMethodReturnTypeExtension` type inference for `ActiveQuery` support, and fix phpstan errors max lvl in tests (#33).
- fix: fix property reflection in `UserPropertiesClassReflectionExtension` to support `identityClass` resolution and improve type inference for `user` component properties (#34).

## 0.2.2 June 04, 2025

- fix: make `$configPath` optional in constructor `ServiceMap` class and update docs `README.md` (#22).
- fix: update the path to `Yii.php` in the `stubFiles` configuration for correct referencing (#23).
- fix: improve `Yii2` integration component property handling (#24).

## 0.2.1 June 03, 2025

- fix: update licenses, docs, configuration, and reflection/type logic (#20).
- docs: update `CHANGELOG.md` date for version `0.2.1` and format `phpunit.xml.dist` (#21).

## 0.2.0 June 02, 2025

- feat: upgrade to `PHPStan` `2.1` (#12).
- feat: enhance `PHPStan` integration with yii2 extensions (#13).
- ci: consolidate `PHPUnit` workflows and update `README.md` with `Yii2` version badges (#14).
- docs: correct badge label formatting for `Yii2` version in `README.md` (#15).
- ci: remove duplicate concurrency settings from phpunit-compatibility job in `build.yml` (#16).
- docs: update changelog for version `0.2.0` with recent enhancements and bugfixes (#17).
- docs: add usage instructions and configuration details for `phpstan.neon` in `README.md` (#18).

## 0.1.0 February 27, 2024

- feat: initial release.

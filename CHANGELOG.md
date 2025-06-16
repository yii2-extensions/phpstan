# Change Log

## 0.3.0 Under development

- Enh #35: Move config fixtures into `config` subdirectory (@terabytesoftw)
- Enh #36: Update `ActiveQuery` and `ActiveRecord` dynamic return type extensions for improved type inference and error handling; remove deprecated `ActiveQueryObjectType` and `ActiveRecordObjectType` classes (@terabytesoftw)
- Enh #37: Enhance `DI` container type inference and testing (@terabytesoftw)
- Bug #38: Correct exception message formatting in `ServiceMapServiceTest` (@terabytesoftw)
- Bug #39: Resolve `Container::get()` type inference for unconfigured classes in config (`ServiceMap`) (@terabytesoftw)
- Enh #40: Enhance `PHPStan` analysis for `Behavior` type inference and testing (@terabytesoftw)
- Enh #41: Refactor `PHPDoc` comments for consistency and clarity (@terabytesoftw)
- Bug #42: Move `ApplicationPropertiesClassReflectionExtension` to `property` directory and add testing (@terabytesoftw)
- Enh #43: Add tests for session property availability in `Console` and `Web` `Applications` (@terabytesoftw)
- Bug #44: Move `UserPropertiesClassReflectionExtension` to `property` directory and add testing (@terabytesoftw)
- Bug #45: Improve `ServiceMap` configuration for application types (`Base`, `Console`, `Web`) (@terabytesoftw)

## 0.2.3 June 09, 2025

- Enh #25: Add support for `PHPStan` Extension Installer (@samuelrajan747)
- Enh #26: Add `PHPStan` extension installer instructions and improve `ServiceMap` configuration handling (@terabytesoftw)
- Bug #27: Fix error handling in `ServiceMap` for invalid configuration structures and add corresponding test cases (@terabytesoftw)
- Bug #28: Refactor component handling in `ServiceMap` to improve variable naming and streamline logic (@terabytesoftw)
- Enh #29: Enable strict rules and bleeding edge analysis, and update `README.md` with strict configuration examples (@terabytesoftw)
- Bug #33: Fix `ActiveRecordDynamicStaticMethodReturnTypeExtension` type inference for `ActiveQuery` support, and fix phpstan errors max lvl in tests (@terabytesoftw)
- Bug #34: Fix property reflection in `UserPropertiesClassReflectionExtension` to support `identityClass` resolution and improve type inference for `user` component properties (@terabytesoftw)

## 0.2.2 June 04, 2025

- Bug #22: Make `$configPath` optional in constructor `ServiceMap` class and update docs `README.md` (@terabytesoftw)
- Bug #23: Update the path to `Yii.php` in the `stubFiles` configuration for correct referencing (@terabytesoftw)
- Bug #24: Improve `Yii2` integration component property handling.

## 0.2.1 June 03, 2025

- Bug #20: Update licenses, docs, configuration, and reflection/type logic (@terabytesoftw)
- Bug #21: Update `CHANGELOG.md` date for version `0.2.1` and format `phpunit.xml.dist` (@terabytesoftw)

## 0.2.0 June 02, 2025

- Enh #12: Upgrade to `PHPStan` `2.1` (@glpzzz)
- Enh #13: Enhance `PHPStan` integration with yii2 extensions (@terabytesoftw)
- Enh #14: Consolidate `PHPUnit` workflows and update `README.md` with `Yii2` version badges (@terabytesoftw)
- Bug #15: Correct badge label formatting for `Yii2` version in `README.md` (@terabytesoftw)
- Bug #16: Remove duplicate concurrency settings from phpunit-compatibility job in `build.yml` (@terabytesoftw)
- Bug #17: Update changelog for version `0.2.0` with recent enhancements and bug fixes (@terabytesoftw)
- Bug #18: Add usage instructions and configuration details for `phpstan.neon` in `README.md` (@terabytesoftw)

## 0.1.0 February 27, 2024

- Initial release

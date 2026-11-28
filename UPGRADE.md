# Upgrade Guide

## 0.6.1

- Remove `ignoreErrors` or baseline entries for the `Yii::$app = null` assignment, which is no longer reported; see the
  [migration notes](docs/migration.md#upgrading-to-061).

## 0.6.0

- Follow the [migration notes](docs/migration.md#upgrading-to-060) for application receivers unrelated to the configured
  application and for the stricter `phpstan.application_type` validation.

## 0.5.0

- Upgrade PHP to 8.3 or later; see the [PHP 8.3 requirement](docs/migration.md#php-83-requirement).
- Follow the [migration notes](docs/migration.md#upgrading-to-050) for inferred type changes, removed extension APIs,
  static stubs and result caching.

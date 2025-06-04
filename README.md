<p align="center">
    <a href="https://github.com/yii2-extensions/phpstan" target="_blank">
        <img src="https://www.yiiframework.com/image/yii_logo_light.svg" height="100px;">
    </a>
    <h1 align="center">Extension for PHPStan.</h1>
</p>

<p align="center">
    <a href="https://www.php.net/releases/8.1/en.php" target="_blank">
        <img src="https://img.shields.io/badge/PHP-%3E%3D8.1-787CB5" alt="PHP-Version">
    </a>
    <a href="https://github.com/yiisoft/yii2/tree/2.0.52" target="_blank">
        <img src="https://img.shields.io/badge/Yii2%20-2.0.52-blue" alt="Yii-22.0.52">
    </a>
    <a href="https://github.com/yiisoft/yii2/tree/22.0" target="_blank">
        <img src="https://img.shields.io/badge/Yii2%20-22-blue" alt="Yii2-22">
    </a>    
    <a href="https://github.com/yii2-extensions/phpstan/actions/workflows/build.yml" target="_blank">
        <img src="https://github.com/yii2-extensions/phpstan/actions/workflows/build.yml/badge.svg" alt="PHPUnit">
    </a>
    <a href="https://github.com/yii2-extensions/phpstan/actions/workflows/static.yml" target="_blank">        
        <img src="https://github.com/yii2-extensions/phpstan/actions/workflows/static.yml/badge.svg" alt="Static-Analysis">
    </a>          
    <a href="https://codecov.io/gh/yii2-extensions/phpstan" target="_blank">
        <img src="https://codecov.io/gh/yii2-extensions/phpstan/branch/main/graph/badge.svg?token=MF0XUGVLYC" alt="Codecov">
    </a>  
</p>

## Installation

The preferred way to install this extension is through [composer](https://getcomposer.org/download/).

Either run

```shell
composer require --dev --prefer-dist yii2-extensions/phpstan:^0.2
```

or add

```json
"yii2-extensions/phpstan": "^0.2"
```

## Usage

This extension provides enhanced static analysis for `Yii2` applications by adding:

- **Container service resolution** with proper type inference.
- **Dynamic method return types** for `ActiveRecord` and `ActiveQuery`.
- **Header collection dynamic methods** support.
- **Property reflection extensions** for `Application`, `Request`, `Response`, and `User` components.
- **Service map integration** for dependency injection analysis.

### Basic Configuration

To use this extension, you need to add the following configuration to your `phpstan.neon` file:

```neon
includes:
    - vendor/yii2-extensions/phpstan/extension.neon

parameters:
    bootstrapFiles:
        - tests/bootstrap.php

    level: 5

    paths:
        - src

    # Exclude paths from analysis
    excludePaths:
        - c3.php
        - requirements.php
        - config
        - tests
        - vendor

    yii2:
        # Path to your `Yii2` configuration file (optional)
        # If not provided or empty, will work without explicit configuration 
        config_path: %currentWorkingDirectory%/config/test.php
```

### Dynamic Constants Configuration

The extension automatically recognizes common `Yii2` dynamic constants:

- `YII_DEBUG`
- `YII_ENV`
- `YII_ENV_DEV`
- `YII_ENV_PROD`
- `YII_ENV_TEST`

If you need to add additional dynamic constants, you can extend the configuration:

```neon
includes:
    - vendor/yii2-extensions/phpstan/extension.neon

parameters:
    # Your existing dynamic constants will be merged with the extension's defaults
    dynamicConstantNames:
        - YII_DEBUG         # Already included by the extension
        - YII_ENV           # Already included by the extension
        - YII_ENV_DEV       # Already included by the extension
        - YII_ENV_PROD      # Already included by the extension
        - YII_ENV_TEST      # Already included by the extension
        - MY_CUSTOM_CONSTANT
        - ANOTHER_CONSTANT

    yii2:
        config_path: %currentWorkingDirectory%/config/test.php
```

**Note:** When you define `dynamicConstantNames` in your configuration, it **replaces** the extension's default
constants. To maintain the `Yii2` constants recognition, you must include them explicitly along with your custom 
constants as shown above.

### Advanced Configuration Example

```neon
includes:
    - vendor/yii2-extensions/phpstan/extension.neon

parameters:
    level: 8
    
    paths:
        - src
        - controllers
        - models
        - widgets

    excludePaths:
        - src/legacy
        - tests/_support
        - vendor
        
    bootstrapFiles:
        - config/bootstrap.php
        - tests/bootstrap.php

    # Complete dynamic constants list (extension defaults + custom)
    dynamicConstantNames:
        - YII_DEBUG
        - YII_ENV
        - YII_ENV_DEV
        - YII_ENV_PROD
        - YII_ENV_TEST
        - APP_VERSION
        - MAINTENANCE_MODE

    yii2:
        config_path: %currentWorkingDirectory%/config/web.php
```

## Quality code

[![phpstan-level](https://img.shields.io/badge/PHPStan%20level-9-blue)](https://github.com/yii2-extensions/phpstan/actions/workflows/static.yml)
[![style-ci](https://github.styleci.io/repos/701347895/shield?branch=main)](https://github.styleci.io/repos/701347895?branch=main)

## Testing

[Check the documentation testing](docs/testing.md) to learn about testing.

## Our social networks

[![Twitter](https://img.shields.io/badge/twitter-follow-1DA1F2?logo=twitter&logoColor=1DA1F2&labelColor=555555?style=flat)](https://twitter.com/Terabytesoftw)

## License

BSD-3-Clause license. Please see [License File](LICENSE.md) for more information.

## Fork 

This package is a fork of [proget-hq/phpstan-yii2](https://github.com/proget-hq/phpstan-yii2) with some corrections.

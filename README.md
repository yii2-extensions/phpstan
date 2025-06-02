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

```shel
composer require --dev --prefer-dist yii2-extensions/phpstan:^0.1
```

or add

```json
"yii2-extensions/phpstan": "^0.1"
```

## Usage

To use this extension, you need to add the following configuration to your `phpstan.neon` file:

```neon
includes:
	- vendor/yii2-extensions/phpstan/extension.neon

parameters:
    bootstrapFiles:
        - tests/bootstrap.php

    dynamicConstantNames:
        - YII_DEBUG
        - YII_ENV

    level: 5

    paths:
        - src

    scanFiles:
        - vendor/yiisoft/yii2/Yii.php

    yii2:
        # Path to your Yii2 configuration file
        config_path: %currentWorkingDirectory%/config/test.php
```

## Quality code

[![phpstan-level](https://img.shields.io/badge/PHPStan%20level-6-blue)](https://github.com/yii2-extensions/phpstan/actions/workflows/static.yml)
[![style-ci](https://github.styleci.io/repos/701347895/shield?branch=main)](https://github.styleci.io/repos/701347895?branch=main)

## Testing

[Check the documentation testing](docs/testing.md) to learn about testing.

## Our social networks

[![Twitter](https://img.shields.io/badge/twitter-follow-1DA1F2?logo=twitter&logoColor=1DA1F2&labelColor=555555?style=flat)](https://twitter.com/Terabytesoftw)

## License

The MIT License. Please see [License File](LICENSE) for more information.

## Fork 

This package is a fork of [proget-hq/phpstan-yii2](https://github.com/proget-hq/phpstan-yii2) with some corrections.

# Testing

## Checking dependencies

This package uses [composer-require-checker](https://github.com/maglnet/ComposerRequireChecker) to check if all dependencies are correctly defined in `composer.json`.

To run the checker, execute the following command:

```shell
composer run check-dependencies
```

## Easy coding standard

The code is checked with [Easy Coding Standard](https://github.com/easy-coding-standard/easy-coding-standard) and
[PHP CS Fixer](https://github.com/PHP-CS-Fixer/PHP-CS-Fixer). To run it:

```shell
composer run ecs
```

## Static analysis

The code is statically analyzed with [PHPStan](https://phpstan.org/). To run static analysis:

```shell
composer run phpstan
```

## Unit tests

The code is tested with [PHPUnit](https://phpunit.de/). To run tests:

```
composer run test
```

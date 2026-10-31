<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

/**
 * Data provider for {@see \yii2\extensions\phpstan\tests\reflection\ApplicationClassResolverTest} test cases.
 *
 * Provides accepted and rejected `phpstan.application_type` configurations with their expected outcomes.
 */
final class ApplicationClassResolverProvider
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function acceptedApplicationTypeProvider(): iterable
    {
        yield 'base application' => [
            'phpstan-base-app-config.php',
            'yii\base\Application',
        ];
        yield 'console application' => [
            'phpstan-console-config.php',
            'yii\console\Application',
        ];
        yield 'custom console application' => [
            'phpstan-console-custom-app-config.php',
            'yii2\extensions\phpstan\tests\support\stub\ApplicationConsoleCustom',
        ];
        yield 'custom Web application with identity generic' => [
            'phpstan-custom-app-config.php',
            'yii2\extensions\phpstan\tests\support\stub\ApplicationCustom',
        ];
        yield 'empty application type' => [
            'phpstan-empty-app-type-config.php',
            'yii\web\Application',
        ];
        yield 'global namespace application' => [
            'phpstan-global-class-app-config.php',
            'GlobalApplication',
        ];
        yield 'leading backslash' => [
            'phpstan-leading-backslash-app-config.php',
            'yii\web\Application',
        ];
        yield 'no configuration file' => [
            '',
            'yii\web\Application',
        ];
        yield 'omitted application type' => [
            'phpstan-config.php',
            'yii\web\Application',
        ];
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function rejectedApplicationTypeProvider(): iterable
    {
        yield 'class not extending the base application' => [
            'phpstan-invalid-app-non-application-class-config.php',
            'yii\base\Component',
            "a class that doesn't extend 'yii\\base\\Application'",
        ];
        yield 'enum' => [
            'phpstan-invalid-app-enum-config.php',
            'yii2\extensions\phpstan\tests\support\stub\LedgerStatus',
            'an enum',
        ];
        yield 'interface' => [
            'phpstan-invalid-app-interface-config.php',
            'yii\web\IdentityInterface',
            'an interface',
        ];
        yield 'missing class' => [
            'phpstan-invalid-app-missing-class-config.php',
            'yii2\extensions\phpstan\tests\support\stub\MissingApplication',
            'an unknown class',
        ];
        yield 'trait' => [
            'phpstan-invalid-app-trait-config.php',
            'yii\base\ArrayableTrait',
            'a trait',
        ];
    }
}

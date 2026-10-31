<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

/**
 * Data provider for {@see \yii2\extensions\phpstan\tests\ApplicationTypeValidationTest} test cases.
 *
 * Provides application types made available to PHPStan through each class discovery source, and rejected types.
 */
final class ApplicationTypeValidationProvider
{
    /**
     * @return iterable<string, array{string|null, list<string>}>
     */
    public static function acceptedApplicationTypeProvider(): iterable
    {
        yield 'analysed path' => [
            'Probe\AnalysedApplication',
            ['Probe\AnalysedApplication', 'yii\web\Request'],
        ];
        yield 'bootstrap file' => [
            'BootstrapApplication',
            ['BootstrapApplication', 'yii\web\Request'],
        ];
        yield 'Composer autoloading' => [
            'yii2\extensions\phpstan\tests\support\stub\ApplicationCustom',
            ['yii2\extensions\phpstan\tests\support\stub\ApplicationCustom', 'yii\web\Request'],
        ];
        yield 'configuration require' => [
            'ConfigRequiredApplication',
            ['ConfigRequiredApplication', 'yii\web\Request'],
        ];
        yield 'omitted' => [
            null,
            ['yii\web\Application', 'yii\web\Request'],
        ];
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function rejectedApplicationTypeProvider(): iterable
    {
        yield 'enum in analysed path' => [
            'Probe\AnalysedEnum',
            'an enum',
        ];
        yield 'missing class' => [
            'Probe\MissingApplication',
            'an unknown class',
        ];
        yield 'stub file only' => [
            'StubApplication',
            'an unknown class',
        ];
    }
}

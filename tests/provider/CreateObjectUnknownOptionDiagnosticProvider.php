<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

use yii2\extensions\phpstan\tests\data\diagnostic\CreateObjectUnknownOptionDiagnostic;
use yii2\extensions\phpstan\tests\support\FixtureMethod;

/**
 * Data provider for {@see \yii2\extensions\phpstan\tests\CreateObjectUnknownOptionDiagnosticTest} test cases.
 */
final class CreateObjectUnknownOptionDiagnosticProvider
{
    /**
     * @phpstan-return iterable<string, array{list<string>, list<array{int, string}>}>
     */
    public static function ruleSwitchProvider(): iterable
    {
        yield 'disabled by default' => [
            [],
            [
                [26, 'array.duplicateKey'],
                [31, 'argument.type'],
                [36, 'arguments.count'],
                [49, 'argument.unknown'],
                [61, 'variable.undefined'],
                [67, 'array.duplicateKey'],
                [77, 'arguments.count'],
                [113, 'array.duplicateKey'],
                [117, 'arguments.count'],
                [172, 'array.duplicateKey'],
                ...self::constructorArguments(false, false),
            ],
        ];
        yield 'unknown option enabled' => [
            ['createObjectUnknownOption'],
            [
                [20, 'yii2.createObject.unknownOption'],
                [26, 'array.duplicateKey'],
                [26, 'yii2.createObject.unknownOption'],
                [31, 'argument.type'],
                [36, 'arguments.count'],
                [49, 'argument.unknown'],
                [55, 'yii2.createObject.unknownOption'],
                [61, 'variable.undefined'],
                [67, 'array.duplicateKey'],
                [67, 'yii2.createObject.unknownOption'],
                [74, 'yii2.createObject.unknownOption'],
                [77, 'arguments.count'],
                [107, 'yii2.createObject.unknownOption'],
                [113, 'array.duplicateKey'],
                [117, 'arguments.count'],
                [139, 'yii2.createObject.unknownOption'],
                [152, 'yii2.createObject.unknownOption'],
                [172, 'array.duplicateKey'],
                [172, 'yii2.createObject.unknownOption'],
                ...self::constructorArguments(true, false),
            ],
        ];
        yield 'invalid option type enabled' => [
            ['createObjectInvalidOptionType'],
            [
                [26, 'array.duplicateKey'],
                [31, 'argument.type'],
                [36, 'arguments.count'],
                [49, 'argument.unknown'],
                [61, 'variable.undefined'],
                [67, 'array.duplicateKey'],
                [77, 'arguments.count'],
                [100, 'yii2.createObject.invalidOptionType'],
                [106, 'yii2.createObject.invalidOptionType'],
                [113, 'array.duplicateKey'],
                [117, 'arguments.count'],
                [145, 'yii2.createObject.invalidOptionType'],
                [151, 'yii2.createObject.invalidOptionType'],
                [166, 'yii2.createObject.invalidOptionType'],
                [172, 'array.duplicateKey'],
                ...self::constructorArguments(false, true),
            ],
        ];
        yield 'both enabled' => [
            ['createObjectInvalidOptionType', 'createObjectUnknownOption'],
            [
                [20, 'yii2.createObject.unknownOption'],
                [26, 'array.duplicateKey'],
                [26, 'yii2.createObject.unknownOption'],
                [31, 'argument.type'],
                [36, 'arguments.count'],
                [49, 'argument.unknown'],
                [55, 'yii2.createObject.unknownOption'],
                [61, 'variable.undefined'],
                [67, 'array.duplicateKey'],
                [67, 'yii2.createObject.unknownOption'],
                [74, 'yii2.createObject.unknownOption'],
                [77, 'arguments.count'],
                [100, 'yii2.createObject.invalidOptionType'],
                [106, 'yii2.createObject.invalidOptionType'],
                [107, 'yii2.createObject.unknownOption'],
                [113, 'array.duplicateKey'],
                [117, 'arguments.count'],
                [139, 'yii2.createObject.unknownOption'],
                [145, 'yii2.createObject.invalidOptionType'],
                [151, 'yii2.createObject.invalidOptionType'],
                [152, 'yii2.createObject.unknownOption'],
                [166, 'yii2.createObject.invalidOptionType'],
                [172, 'array.duplicateKey'],
                [172, 'yii2.createObject.unknownOption'],
                ...self::constructorArguments(true, true),
            ],
        ];
    }

    /**
     * Returns the diagnostics of the constructor argument methods, located by method and key.
     *
     * @phpstan-return list<array{int, string}>
     */
    private static function constructorArguments(bool $unknownOption, bool $invalidOptionType): array
    {
        $fixture = CreateObjectUnknownOptionDiagnostic::class;

        $construct = FixtureMethod::line($fixture, 'createWithConstructOption', "'count'");
        $duplicate = FixtureMethod::line($fixture, 'createWithDuplicateArgumentKey', "'colour'");

        return [
            ...($invalidOptionType ? [[$construct, 'yii2.createObject.invalidOptionType']] : []),
            [$duplicate, 'array.duplicateKey'],
            ...($unknownOption ? [[$duplicate, 'yii2.createObject.unknownOption']] : []),
            [FixtureMethod::line($fixture, 'createWithExtraArgument'), 'arguments.count'],
            [FixtureMethod::line($fixture, 'createWithParamsNotArray'), 'argument.type'],
            [FixtureMethod::line($fixture, 'createWithUnknownNamedArgument'), 'argument.unknown'],
        ];
    }
}

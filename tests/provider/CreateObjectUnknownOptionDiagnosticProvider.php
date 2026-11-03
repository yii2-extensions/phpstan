<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

/**
 * Data provider for {@see \yii2\extensions\phpstan\tests\CreateObjectUnknownOptionDiagnosticTest} test cases.
 *
 * Provides the enabled rule switches and the diagnostics expected on the shared fixture, as line and identifier pairs.
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
                [18, 'array.duplicateKey'],
                [19, 'argument.type'],
                [20, 'arguments.count'],
                [21, 'argument.unknown'],
                [23, 'variable.undefined'],
                [24, 'array.duplicateKey'],
                [29, 'arguments.count'],
                [44, 'array.duplicateKey'],
                [45, 'arguments.count'],
            ],
        ];
        yield 'unknown option enabled' => [
            ['createObjectUnknownOption'],
            [
                [17, 'yii2.createObject.unknownOption'],
                [18, 'array.duplicateKey'],
                [18, 'yii2.createObject.unknownOption'],
                [19, 'argument.type'],
                [20, 'arguments.count'],
                [21, 'argument.unknown'],
                [22, 'yii2.createObject.unknownOption'],
                [23, 'variable.undefined'],
                [24, 'array.duplicateKey'],
                [24, 'yii2.createObject.unknownOption'],
                [28, 'yii2.createObject.unknownOption'],
                [29, 'arguments.count'],
                [43, 'yii2.createObject.unknownOption'],
                [44, 'array.duplicateKey'],
                [45, 'arguments.count'],
            ],
        ];
        yield 'invalid option type enabled' => [
            ['createObjectInvalidOptionType'],
            [
                [18, 'array.duplicateKey'],
                [19, 'argument.type'],
                [20, 'arguments.count'],
                [21, 'argument.unknown'],
                [23, 'variable.undefined'],
                [24, 'array.duplicateKey'],
                [29, 'arguments.count'],
                [42, 'yii2.createObject.invalidOptionType'],
                [43, 'yii2.createObject.invalidOptionType'],
                [44, 'array.duplicateKey'],
                [45, 'arguments.count'],
            ],
        ];
        yield 'both enabled' => [
            ['createObjectInvalidOptionType', 'createObjectUnknownOption'],
            [
                [17, 'yii2.createObject.unknownOption'],
                [18, 'array.duplicateKey'],
                [18, 'yii2.createObject.unknownOption'],
                [19, 'argument.type'],
                [20, 'arguments.count'],
                [21, 'argument.unknown'],
                [22, 'yii2.createObject.unknownOption'],
                [23, 'variable.undefined'],
                [24, 'array.duplicateKey'],
                [24, 'yii2.createObject.unknownOption'],
                [28, 'yii2.createObject.unknownOption'],
                [29, 'arguments.count'],
                [42, 'yii2.createObject.invalidOptionType'],
                [43, 'yii2.createObject.unknownOption'],
                [43, 'yii2.createObject.invalidOptionType'],
                [44, 'array.duplicateKey'],
                [45, 'arguments.count'],
            ],
        ];
    }
}

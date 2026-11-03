<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

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
            ],
        ];
    }
}

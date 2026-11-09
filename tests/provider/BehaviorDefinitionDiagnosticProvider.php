<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

use function sort;

/**
 * Data provider for {@see \yii2\extensions\phpstan\tests\BehaviorDefinitionDiagnosticTest} test cases.
 */
final class BehaviorDefinitionDiagnosticProvider
{
    /**
     * @phpstan-return iterable<string, array{list<string>, list<array{string, int, string, null}>}>
     */
    public static function ruleSwitchProvider(): iterable
    {
        yield 'disabled by default' => [
            [],
            self::results(false),
        ];
        yield 'enabled' => [
            ['behaviorInvalidDefinition'],
            self::results(true),
        ];
        yield 'only the createObject() rules enabled' => [
            ['createObjectInvalidOptionType', 'createObjectUnknownOption'],
            self::results(false),
        ];
    }

    /**
     * Returns the sorted results of the owner: native diagnostics, plus the rule's when it is enabled.
     *
     * @phpstan-return list<array{string, int, string, null}>
     */
    private static function results(bool $enabled): array
    {
        $results = [
            // Yii's declared `behaviors()` element type rejects the array without class; the rule doesn't repeat it
            [
                'src/Owner.php',
                11,
                'return.type',
                null,
            ],
            // Yii's declared `attachBehavior()` parameter type rejects closures, which Yii invokes at runtime
            [
                'src/Owner.php',
                20,
                'argument.type',
                null,
            ],
        ];

        if ($enabled) {
            $results[] = [
                'src/Owner.php',
                12,
                'yii2.behavior.invalidDefinition',
                null,
            ];
            $results[] = [
                'src/Owner.php',
                19,
                'yii2.behavior.invalidDefinition',
                null,
            ];
        }

        sort($results);

        return $results;
    }
}

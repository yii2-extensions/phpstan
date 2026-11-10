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
            self::results(false, false, false),
        ];
        yield 'definition rule enabled' => [
            ['behaviorInvalidDefinition'],
            self::results(true, false, false),
        ];
        yield 'as definition rule enabled' => [
            ['behaviorInvalidAsDefinition'],
            self::results(false, true, false),
        ];
        yield 'unknown option rule enabled' => [
            ['behaviorUnknownOption'],
            self::results(false, false, true),
        ];
        yield 'all enabled' => [
            ['behaviorInvalidAsDefinition', 'behaviorInvalidDefinition', 'behaviorUnknownOption'],
            self::results(true, true, true),
        ];
        yield 'only the createObject() rules enabled' => [
            ['createObjectInvalidOptionType', 'createObjectUnknownOption'],
            self::results(false, false, false),
        ];
    }

    /**
     * Returns the sorted results of the owners and the factory: native diagnostics, plus those of each enabled rule.
     *
     * @phpstan-return list<array{string, int, string, null}>
     */
    private static function results(bool $definition, bool $asDefinition, bool $unknownOption): array
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

        if ($asDefinition) {
            $results[] = [
                'src/Factory.php',
                11,
                'yii2.behavior.invalidAsDefinition',
                null,
            ];
        }

        if ($definition) {
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

        if ($unknownOption) {
            foreach ([12, 18, 22] as $line) {
                $results[] = [
                    'src/OptionOwner.php',
                    $line,
                    'yii2.behavior.unknownOption',
                    null,
                ];
            }
        }

        sort($results);

        return $results;
    }
}

<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

use function sort;

/**
 * Data provider for {@see \yii2\extensions\phpstan\tests\ExternalActionDiagnosticTest} test cases.
 */
final class ExternalActionDiagnosticProvider
{
    /**
     * Entries of the returned action map, one per line, from the line given by {@see FIRST_LINE}.
     */
    public const array ENTRIES = [
        "'unknown' => ['class' => Target::class, 'colour' => 'red'],",
        "'type' => ['class' => Target::class, 'count' => 'all'],",
        "'duplicate' => ['class' => Target::class, 'colour' => 'red', 'colour' => 'blue'],",
        "'legacy' => ['__class' => Target::class, 'shade' => 'dark'],",
        "'missing' => ['class' => Missing::class, 'colour' => 'red'],",
        "'valid' => ['class' => Target::class, 'count' => '5'],",
    ];

    /**
     * Line of the first map entry in the generated controller.
     */
    public const int FIRST_LINE = 13;

    /**
     * Line of the `return` statement of `actions()`.
     */
    private const int RETURN_LINE = 12;

    /**
     * @phpstan-return iterable<string, array{list<string>, list<array{string, int, string, null}>}>
     */
    public static function ruleSwitchProvider(): iterable
    {
        yield 'disabled by default' => [
            [],
            self::results(false, false, false, false),
        ];
        yield 'unknown option enabled' => [
            ['externalActionUnknownOption'],
            self::results(true, false, false, false),
        ];
        yield 'invalid option type enabled' => [
            ['externalActionInvalidOptionType'],
            self::results(false, true, false, false),
        ];
        yield 'both enabled' => [
            ['externalActionInvalidOptionType', 'externalActionUnknownOption'],
            self::results(true, true, false, false),
        ];
        yield 'only createObject() rules enabled' => [
            ['createObjectInvalidOptionType', 'createObjectUnknownOption'],
            self::results(false, false, true, false),
        ];
        yield 'only widget and new rules enabled' => [
            ['newInvalidOptionType', 'newUnknownOption', 'widgetInvalidOptionType', 'widgetUnknownOption'],
            self::results(false, false, false, true),
        ];
    }

    /**
     * Returns the sorted results of the controller for the enabled rules, beside the native diagnostics.
     *
     * @phpstan-return list<array{string, int, string, null}>
     */
    private static function results(
        bool $unknownOption,
        bool $invalidOptionType,
        bool $createObject,
        bool $newObject,
    ): array {
        $results = [
            ['src/ProbeController.php', self::RETURN_LINE, 'return.type', null],
            ['src/ProbeController.php', self::FIRST_LINE + 2, 'array.duplicateKey', null],
            ['src/ProbeController.php', self::FIRST_LINE + 4, 'class.notFound', null],
        ];

        if ($unknownOption) {
            $results[] = ['src/ProbeController.php', self::FIRST_LINE, 'yii2.externalAction.unknownOption', null];
            $results[] = ['src/ProbeController.php', self::FIRST_LINE + 2, 'yii2.externalAction.unknownOption', null];
            $results[] = ['src/ProbeController.php', self::FIRST_LINE + 3, 'yii2.externalAction.unknownOption', null];
        }

        if ($invalidOptionType) {
            $results[] = ['src/ProbeController.php', self::FIRST_LINE + 1, 'yii2.externalAction.invalidOptionType', null];
        }

        if ($createObject) {
            $results[] = ['src/Consumer.php', 11, 'yii2.createObject.unknownOption', null];
        }

        if ($newObject) {
            $results[] = ['src/Consumer.php', 12, 'yii2.new.unknownOption', null];
        }

        sort($results);

        return $results;
    }
}

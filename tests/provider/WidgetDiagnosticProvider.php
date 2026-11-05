<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

use function sort;

/**
 * Data provider for {@see \yii2\extensions\phpstan\tests\WidgetDiagnosticTest} test cases.
 */
final class WidgetDiagnosticProvider
{
    /**
     * Statements of the consumer method, one per line, from the line given by {@see FIRST_LINE}.
     */
    public const array STATEMENTS = [
        "Plain::widget(['colour' => 'red']);",
        "Plain::begin(['count' => 'all']);",
        "Plain::widget(['colour' => 'red', 'colour' => 'blue']);",
        "Plain::widget(['colour' => 'red'], 'extra');",
        "Plain::begin(options: ['colour' => 'red']);",
        "Plain::widget('colour');",
        "Plain::widget(['count' => '5']);",
        "Missing::widget(['colour' => 'red']);",
        "Target::widget(['colour' => 'red', 'name' => 'value']);",
        "\\yii\\BaseYii::createObject(['class' => Plain::class, 'shade' => 'dark']);",
        "new Plain(['shade' => 'dark']);",
        "new Target(['colour' => 'red']);",
    ];

    /**
     * Line of the first statement in the generated consumer.
     */
    private const int FIRST_LINE = 11;

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
            ['widgetUnknownOption'],
            self::results(true, false, false, false),
        ];
        yield 'invalid option type enabled' => [
            ['widgetInvalidOptionType'],
            self::results(false, true, false, false),
        ];
        yield 'both enabled' => [
            ['widgetInvalidOptionType', 'widgetUnknownOption'],
            self::results(true, true, false, false),
        ];
        yield 'only createObject() rules enabled' => [
            ['createObjectInvalidOptionType', 'createObjectUnknownOption'],
            self::results(false, false, true, false),
        ];
        yield 'only new rules enabled' => [
            ['newInvalidOptionType', 'newUnknownOption'],
            self::results(false, false, false, true),
        ];
    }

    /**
     * Returns the sorted results of the consumer for the enabled rules, beside the native diagnostics.
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
            [2, 'array.duplicateKey'],
            [3, 'arguments.count'],
            [4, 'argument.unknown'],
            [5, 'argument.type'],
            [7, 'class.notFound'],
        ];

        if ($unknownOption) {
            $results[] = [0, 'yii2.widget.unknownOption'];
            $results[] = [2, 'yii2.widget.unknownOption'];
            $results[] = [8, 'yii2.widget.unknownOption'];
        }

        if ($invalidOptionType) {
            $results[] = [1, 'yii2.widget.invalidOptionType'];
        }

        if ($createObject) {
            $results[] = [9, 'yii2.createObject.unknownOption'];
        }

        if ($newObject) {
            $results[] = [10, 'yii2.new.unknownOption'];
            $results[] = [11, 'yii2.new.unknownOption'];
        }

        $sorted = [];

        foreach ($results as [$statement, $identifier]) {
            $sorted[] = ['src/Consumer.php', self::FIRST_LINE + $statement, $identifier, null];
        }

        sort($sorted);

        return $sorted;
    }
}

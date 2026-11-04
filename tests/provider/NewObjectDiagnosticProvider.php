<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

/**
 * Data provider for {@see \yii2\extensions\phpstan\tests\NewObjectDiagnosticTest} test cases.
 */
final class NewObjectDiagnosticProvider
{
    /**
     * Statements of the consumer method, one per line, from the line given by {@see FIRST_LINE}.
     */
    public const array STATEMENTS = [
        "new Target(['colour' => 'red']);",
        "new Target(['count' => 'all']);",
        "new Target(['colour' => 'red', 'colour' => 'blue']);",
        "new Target(['colour' => 'red'], 'extra');",
        "new Target(options: ['colour' => 'red']);",
        "new Target('colour');",
        "new Target(['count' => '5', 'name' => 5]);",
        "new Missing(['colour' => 'red']);",
        "\\yii\\BaseYii::createObject(['class' => Target::class, 'colour' => 'red']);",
        "\\yii\\BaseYii::createObject(['class' => Target::class, 'shade' => 'dark']);",
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
            self::results(false, false, false),
        ];
        yield 'unknown option enabled' => [
            ['newUnknownOption'],
            self::results(true, false, false),
        ];
        yield 'invalid option type enabled' => [
            ['newInvalidOptionType'],
            self::results(false, true, false),
        ];
        yield 'both enabled' => [
            ['newInvalidOptionType', 'newUnknownOption'],
            self::results(true, true, false),
        ];
        yield 'only createObject() rules enabled' => [
            ['createObjectInvalidOptionType', 'createObjectUnknownOption'],
            self::results(false, false, true),
        ];
    }

    /**
     * Returns the sorted results of the consumer for the enabled rules, beside the native diagnostics.
     *
     * @phpstan-return list<array{string, int, string, null}>
     */
    private static function results(bool $unknownOption, bool $invalidOptionType, bool $createObject): array
    {
        $results = [
            [2, 'array.duplicateKey'],
            [3, 'arguments.count'],
            [4, 'argument.unknown'],
            [5, 'argument.type'],
            [7, 'class.notFound'],
        ];

        if ($unknownOption) {
            $results[] = [0, 'yii2.new.unknownOption'];
            $results[] = [2, 'yii2.new.unknownOption'];
        }

        if ($invalidOptionType) {
            $results[] = [1, 'yii2.new.invalidOptionType'];
        }

        if ($createObject) {
            $results[] = [9, 'yii2.createObject.unknownOption'];
        }

        $sorted = [];

        foreach ($results as [$statement, $identifier]) {
            $sorted[] = ['src/Consumer.php', self::FIRST_LINE + $statement, $identifier, null];
        }

        sort($sorted);

        return $sorted;
    }
}

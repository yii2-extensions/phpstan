<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

use function sort;

/**
 * Data provider for {@see \yii2\extensions\phpstan\tests\ValidatorIncompleteRuleDiagnosticTest} test cases.
 */
final class ValidatorIncompleteRuleDiagnosticProvider
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
            ['validatorIncompleteRule'],
            self::results(true),
        ];
        yield 'only the behavior definition rule enabled' => [
            ['behaviorInvalidDefinition'],
            self::results(false),
        ];
    }

    /**
     * Returns the sorted results of the model: native diagnostics, plus those of the rule when enabled.
     *
     * @phpstan-return list<array{string, int, string, null}>
     */
    private static function results(bool $enabled): array
    {
        $results = [
            // Yii's declared `rules()` element type rejects the string rule; the rule doesn't repeat it
            [
                'src/Article.php',
                11,
                'return.type',
                null,
            ],
        ];

        if ($enabled) {
            foreach ([13, 14] as $line) {
                $results[] = [
                    'src/Article.php',
                    $line,
                    'yii2.validator.incompleteRule',
                    null,
                ];
            }
        }

        sort($results);

        return $results;
    }
}

<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

use function sort;

/**
 * Data provider for {@see \yii2\extensions\phpstan\tests\ValidatorInvalidDefinitionDiagnosticTest} test cases.
 */
final class ValidatorInvalidDefinitionDiagnosticProvider
{
    /**
     * @phpstan-return iterable<string, array{list<string>, list<array{string, int, string, null}>}>
     */
    public static function ruleSwitchProvider(): iterable
    {
        yield 'disabled by default' => [
            [],
            self::results(false, false),
        ];
        yield 'enabled' => [
            ['validatorInvalidDefinition'],
            self::results(true, false),
        ];
        yield 'enabled with the incomplete rule check' => [
            ['validatorIncompleteRule', 'validatorInvalidDefinition'],
            self::results(true, true),
        ];
        yield 'only the incomplete rule check enabled' => [
            ['validatorIncompleteRule'],
            self::results(false, true),
        ];
    }

    /**
     * Returns the sorted results of the model: native diagnostics, plus those of each enabled rule.
     *
     * @phpstan-return list<array{string, int, string, null}>
     */
    private static function results(bool $invalidDefinition, bool $incompleteRule): array
    {
        $results = [
            // Yii's declared `rules()` element type rejects the string rule; neither rule repeats it
            ['src/Article.php', 11, 'return.type', null],
            // the unknown class is native; its validator type stands aside
            ['src/Article.php', 15, 'class.notFound', null],
        ];

        if ($invalidDefinition) {
            foreach ([13, 14] as $line) {
                $results[] = ['src/Article.php', $line, 'yii2.validator.invalidDefinition', null];
            }
        }

        if ($incompleteRule) {
            foreach ([16, 17] as $line) {
                $results[] = ['src/Article.php', $line, 'yii2.validator.incompleteRule', null];
            }
        }

        sort($results);

        return $results;
    }
}

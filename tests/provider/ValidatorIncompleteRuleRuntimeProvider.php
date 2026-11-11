<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

use yii\base\Model;
use yii2\extensions\phpstan\tests\data\rule\validator\{
    CreateValidatorsOverrideModel,
    GetValidatorsOverrideModel,
    InheritedCreateValidatorsModel,
    InheritedGetValidatorsModel,
    TraitCreateValidatorsModel,
};

use function array_keys;
use function in_array;
use function strrpos;
use function substr;

/**
 * Data provider for {@see \yii2\extensions\phpstan\tests\rules\ValidatorIncompleteRuleRuntimeTest} test cases.
 */
final class ValidatorIncompleteRuleRuntimeProvider
{
    /**
     * @phpstan-return iterable<string, array{class-string<Model>}>
     */
    public static function overrideProvider(): iterable
    {
        foreach (
            [
                CreateValidatorsOverrideModel::class,
                GetValidatorsOverrideModel::class,
                InheritedCreateValidatorsModel::class,
                InheritedGetValidatorsModel::class,
                TraitCreateValidatorsModel::class,
            ] as $model
        ) {
            yield self::shortName($model) => [$model];
        }
    }

    /**
     * @phpstan-return iterable<string, array{class-string<Model>, int|string, bool}>
     */
    public static function ruleProvider(): iterable
    {
        foreach (ValidatorIncompleteRuleRuleProvider::reportedRules() as $model => $reported) {
            foreach (array_keys((new $model())->rules()) as $index) {
                yield self::shortName($model) . " {$index}" => [
                    $model,
                    $index,
                    in_array($index, $reported, true),
                ];
            }
        }
    }

    /**
     * @phpstan-return iterable<string, array{class-string<Model>}>
     */
    public static function validProvider(): iterable
    {
        foreach (ValidatorIncompleteRuleRuleProvider::validModels() as $model) {
            yield self::shortName($model) => [$model];
        }
    }

    /**
     * Returns the class name without its namespace.
     */
    private static function shortName(string $class): string
    {
        return substr($class, (int) strrpos($class, '\\') + 1);
    }
}

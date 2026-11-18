<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use yii\validators\{NumberValidator, TrimValidator};
use yii2\extensions\phpstan\rules\ValidatorInvalidOptionTypeRule;
use yii2\extensions\phpstan\ServiceMap;
use yii2\extensions\phpstan\tests\data\rule\validator\type\{
    AliasDefaultModel,
    ClientScriptModel,
    IntegerTypeValidator,
    MethodOverrideModel,
    ReportedModel,
    TypedStringValidator,
    TypedValidator,
    UncertainModel,
    ValidModel,
};
use yii2\extensions\phpstan\tests\support\BehaviorRuleTestTrait;

use function dirname;
use function property_exists;
use function sprintf;

/**
 * Unit tests for {@see ValidatorInvalidOptionTypeRule}, including aliases, closures and container definitions.
 *
 * @extends RuleTestCase<ValidatorInvalidOptionTypeRule>
 */
final class ValidatorInvalidOptionTypeRuleTest extends RuleTestCase
{
    use BehaviorRuleTestTrait;

    private const string CLIENT_SCRIPT_TYPE = 'array|yii\validators\client\ClientValidatorScriptInterface|null';
    private const string IDENTIFIER = 'yii2.validator.invalidOptionType';

    public static function getAdditionalConfigFiles(): array
    {
        return [dirname(__DIR__) . '/support/extension-validator-type-test.neon'];
    }

    public function testAcceptCoercibleValuesAndStandAsideOnUncertainConfigurations(): void
    {
        self::assertSame(
            [],
            $this->classErrors(ValidModel::class, UncertainModel::class),
            'No diagnostic is expected.',
        );
    }

    public function testAliasIgnoresTheOverriddenMethodLookup(): void
    {
        $this->assertDiagnostics(
            MethodOverrideModel::class,
            'rules',
            [
                [
                    "['count', 'string'",
                    null,
                    self::message(1, MethodOverrideModel::class, TypedStringValidator::class, 'limit', "'all'", 'int'),
                ],
            ],
        );
    }

    public function testReportClientScriptOnlyWhereYiiDeclaresItsNativeType(): void
    {
        $expected = [];

        if (property_exists(TrimValidator::class, 'clientScript')) {
            $expected = [
                [
                    "'script'",
                    null,
                    self::message(
                        0,
                        ClientScriptModel::class,
                        TrimValidator::class,
                        'clientScript',
                        "'script'",
                        self::CLIENT_SCRIPT_TYPE,
                    ),
                ],
                [
                    "'clientScript' => 5",
                    null,
                    self::message(
                        1,
                        ClientScriptModel::class,
                        NumberValidator::class,
                        'clientScript',
                        '5',
                        self::CLIENT_SCRIPT_TYPE,
                    ),
                ],
            ];
        }

        $this->assertDiagnostics(ClientScriptModel::class, 'rules', $expected);
    }

    public function testReportEachWrittenOptionTheCreatedValidatorNeverAccepts(): void
    {
        $typed = TypedValidator::class;
        $cases = [
            ["'limit' => 'all']", 0, $typed, 'limit', "'all'", 'int'],
            ["'limit' => null]", 1, $typed, 'limit', 'null', 'int'],
            ["'label' => ['short']", 2, $typed, 'label', "array{'short'}", 'string|null'],
            ["'tags' => 'news'", 3, $typed, 'tags', "'news'", 'array', "{$typed}::setTags()"],
            ["'list' => 'a'", 4, $typed, 'list', "'a'", 'array'],
            ["'list' => 'a'", 4, $typed, 'limit', "''", 'int'],
            ["'__class' => TypedValidator::class", 5, $typed, 'tags', "'news'", 'array', "{$typed}::setTags()"],
            ["'app\\validators\\Limited'", 6, $typed, 'limit', 'array{1}', 'int'],
            ["['count', 'string', 'limit'", 7, TypedStringValidator::class, 'limit', "'all'", 'int'],
            ["'on' => 'inactive'", 8, $typed, 'limit', "'all'", 'int'],
            ["'unknown' => 1", 9, $typed, 'limit', "'all'", 'int'],
        ];
        $expected = [];

        foreach ($cases as $case) {
            [$needle, $index, $class, $option, $actual, $accepted] = $case;

            $expected[] = [
                $needle,
                null,
                self::message($index, ReportedModel::class, $class, $option, $actual, $accepted, $case[6] ?? null),
            ];
        }

        $this->assertDiagnostics(ReportedModel::class, 'rules', $expected);
    }

    public function testReportOnlyTheWrittenOverrideOfAnAliasDefault(): void
    {
        $this->assertDiagnostics(
            AliasDefaultModel::class,
            'rules',
            [
                [
                    "'type' => 'local'",
                    null,
                    self::message(1, AliasDefaultModel::class, IntegerTypeValidator::class, 'type', "'local'", 'int'),
                ],
            ],
        );
    }

    protected function getRule(): Rule
    {
        return new ValidatorInvalidOptionTypeRule(
            self::createReflectionProvider(),
            self::getContainer()->getByType(ServiceMap::class),
        );
    }

    private static function message(
        int $index,
        string $model,
        string $class,
        string $option,
        string $actual,
        string $accepted,
        string|null $member = null,
    ): string {
        return sprintf(
            'Validation rule #%d in %s::rules() sets %s::$%s to %s, but %s accepts only %s, even with type coercion.',
            $index,
            $model,
            $class,
            $option,
            $actual,
            $member ?? 'the property',
            $accepted,
        );
    }
}

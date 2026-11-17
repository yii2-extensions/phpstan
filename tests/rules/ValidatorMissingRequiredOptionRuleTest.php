<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use yii\validators\{FilterValidator, RangeValidator, RegularExpressionValidator};
use yii2\extensions\phpstan\rules\ValidatorMissingRequiredOptionRule;
use yii2\extensions\phpstan\ServiceMap;
use yii2\extensions\phpstan\tests\data\rule\validator\required\{
    MethodOverrideModel,
    ReportedModel,
    UncertainModel,
    ValidModel,
};
use yii2\extensions\phpstan\tests\support\BehaviorRuleTestTrait;

use function dirname;
use function sprintf;

/**
 * Unit tests for {@see ValidatorMissingRequiredOptionRule}, including alias priority and container definitions.
 *
 * @extends RuleTestCase<ValidatorMissingRequiredOptionRule>
 */
final class ValidatorMissingRequiredOptionRuleTest extends RuleTestCase
{
    use BehaviorRuleTestTrait;

    private const string IDENTIFIER = 'yii2.validator.missingRequiredOption';

    public static function getAdditionalConfigFiles(): array
    {
        return [dirname(__DIR__) . '/support/extension-validator-required-test.neon'];
    }

    public function testAcceptSuppliedOptionsAndStandAsideOnUncertainConfigurations(): void
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
            [["['status', 'in']", null, self::message(1, MethodOverrideModel::class, RangeValidator::class, 'range')]],
        );
    }

    public function testReportEachRuleWithTheRequiredOptionOfItsExactClass(): void
    {
        $cases = [
            ["['status', 'in'],", RangeValidator::class, 'range'],
            ["'range' => null", RangeValidator::class, 'range'],
            ["'on' => 'inactive'", RangeValidator::class, 'range'],
            ["'not' => true", RegularExpressionValidator::class, 'pattern'],
            ["'pattern' => null", RegularExpressionValidator::class, 'pattern'],
            ['RegularExpressionValidator::class]', RegularExpressionValidator::class, 'pattern'],
            ["'skipOnEmpty' => true", FilterValidator::class, 'filter'],
            ["'filter' => null", FilterValidator::class, 'filter'],
            ["'on afterValidate'", FilterValidator::class, 'filter'],
            ["'__class' => RangeValidator::class", RangeValidator::class, 'range'],
            ["'class' => SubRangeValidator::class", RangeValidator::class, 'range'],
            ['MessageRange', RangeValidator::class, 'range'],
        ];
        $expected = [];

        foreach ($cases as $index => [$needle, $class, $option]) {
            $expected[] = [$needle, null, self::message($index, ReportedModel::class, $class, $option)];
        }

        $this->assertDiagnostics(ReportedModel::class, 'rules', $expected);
    }

    protected function getRule(): Rule
    {
        return new ValidatorMissingRequiredOptionRule(
            self::createReflectionProvider(),
            self::getContainer()->getByType(ServiceMap::class),
        );
    }

    private static function message(int $index, string $model, string $class, string $option): string
    {
        return sprintf(
            'Validation rule #%d in %s::rules() leaves required property %s::$%s unset: its init() throws an '
                . 'InvalidConfigException when the option is missing or null.',
            $index,
            $model,
            $class,
            $option,
        );
    }
}

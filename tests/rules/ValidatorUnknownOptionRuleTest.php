<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use yii\validators\{DateValidator, InlineValidator, NumberValidator, StringValidator};
use yii2\extensions\phpstan\rules\ValidatorUnknownOptionRule;
use yii2\extensions\phpstan\ServiceMap;
use yii2\extensions\phpstan\tests\data\rule\validator\unknown\{
    AliasPriorityModel,
    ReportedModel,
    StandAsideModel,
    UncertainModel,
    ValidModel,
};
use yii2\extensions\phpstan\tests\support\BehaviorRuleTestTrait;

use function dirname;
use function sprintf;

/**
 * Unit tests for {@see ValidatorUnknownOptionRule}, including alias merging and priority over model methods.
 *
 * @extends RuleTestCase<ValidatorUnknownOptionRule>
 */
final class ValidatorUnknownOptionRuleTest extends RuleTestCase
{
    use BehaviorRuleTestTrait;

    private const string IDENTIFIER = 'yii2.validator.unknownOption';

    public static function getAdditionalConfigFiles(): array
    {
        return [dirname(__DIR__) . '/support/extension-test.neon'];
    }

    public function testAcceptKnownOptionsAndStandAsideOnUncertainDispatch(): void
    {
        self::assertSame(
            [],
            $this->classErrors(ValidModel::class, StandAsideModel::class, UncertainModel::class),
            'No diagnostic is expected.',
        );
    }

    public function testAliasAndClosureDispatchIgnoreTheOverriddenMethodLookup(): void
    {
        $expected = [];

        foreach ([StringValidator::class, InlineValidator::class] as $index => $class) {
            $expected[] = [
                $index === 0 ? "'string'" : 'static function',
                null,
                sprintf(
                    'Validation rule #%s in %s::rules() sets unknown property %s::$maxx: the class has no public '
                        . 'property or setter with that name.',
                    $index,
                    AliasPriorityModel::class,
                    $class,
                ),
            ];
        }

        $this->assertDiagnostics(AliasPriorityModel::class, 'rules', $expected);
    }

    public function testReportWrittenOptionsWithTheirCreatedClass(): void
    {
        $cases = [
            [0, "'datetime', 'formatt'", DateValidator::class, 'formatt'],
            [1, "'integer', 'class' => StringValidator::class", StringValidator::class, 'integerOnly'],
            [2, "'integer', 'minn' => 0", NumberValidator::class, 'minn'],
            [3, "'__class' => NumberValidator::class, 'length' => 4", NumberValidator::class, 'length'],
            [4, "'string', 'class' => NumberValidator::class", StringValidator::class, 'integerOnly'],
            [5, "'string', 'maxx' => 10", StringValidator::class, 'maxx'],
            [6, "'time', 'formatt'", DateValidator::class, 'formatt'],
            [7, 'static function', InlineValidator::class, 'maxx'],
            [8, "StringValidator::class, 'maxx' => 1,", StringValidator::class, 'maxx'],
            [8, "StringValidator::class, 'maxx' => 1,", StringValidator::class, 'minn'],
            [9, "StringValidator::class, 'maxx' => 10", StringValidator::class, 'maxx'],
            [10, "99 => 'unexpected'", StringValidator::class, '0'],
        ];
        $expected = [];

        foreach ($cases as [$index, $needle, $class, $option]) {
            $expected[] = [
                $needle,
                null,
                sprintf(
                    'Validation rule #%s in %s::rules() sets unknown property %s::$%s: the class has no public '
                        . 'property or setter with that name.',
                    $index,
                    ReportedModel::class,
                    $class,
                    $option,
                ),
            ];
        }

        $this->assertDiagnostics(ReportedModel::class, 'rules', $expected);
    }

    protected function getRule(): Rule
    {
        return new ValidatorUnknownOptionRule(
            self::createReflectionProvider(),
            self::getContainer()->getByType(ServiceMap::class),
        );
    }
}

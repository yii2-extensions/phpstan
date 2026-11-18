<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use yii2\extensions\phpstan\rules\ValidatorInvalidOptionTypeRule;
use yii2\extensions\phpstan\ServiceMap;
use yii2\extensions\phpstan\tests\data\rule\validator\type\{InlineMethodModel, TypedMethodValidator};
use yii2\extensions\phpstan\tests\support\BehaviorRuleTestTrait;

use function dirname;
use function sprintf;

/**
 * Unit tests for {@see ValidatorInvalidOptionTypeRule} with `InlineValidator` mapped to a class with a typed `method`.
 *
 * @extends RuleTestCase<ValidatorInvalidOptionTypeRule>
 */
final class ValidatorInvalidOptionTypeInlineRuleTest extends RuleTestCase
{
    use BehaviorRuleTestTrait;

    private const string IDENTIFIER = 'yii2.validator.invalidOptionType';

    public static function getAdditionalConfigFiles(): array
    {
        return [dirname(__DIR__) . '/support/extension-validator-type-inline-test.neon'];
    }

    public function testReportOnlyTheMethodThatTheRuleAssigns(): void
    {
        $this->assertDiagnostics(
            InlineMethodModel::class,
            'rules',
            [
                [
                    "'method' => 'check'",
                    null,
                    sprintf(
                        'Validation rule #2 in %s::rules() sets %s::$method to %s, but the property accepts only int, '
                            . 'even with type coercion.',
                        InlineMethodModel::class,
                        TypedMethodValidator::class,
                        "'check'",
                    ),
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
}

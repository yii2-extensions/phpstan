<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use yii2\extensions\phpstan\rules\CreateObjectBehaviorInvalidOptionTypeRule;
use yii2\extensions\phpstan\ServiceMap;
use yii2\extensions\phpstan\tests\data\rule\behavior\AsBehaviorTypes;
use yii2\extensions\phpstan\tests\provider\BehaviorInvalidOptionTypeRuleProvider;
use yii2\extensions\phpstan\tests\support\BehaviorRuleTestTrait;

use function dirname;

/**
 * Unit tests for {@see CreateObjectBehaviorInvalidOptionTypeRule}.
 *
 * {@see BehaviorInvalidOptionTypeRuleProvider} for test case data providers.
 *
 * @extends RuleTestCase<CreateObjectBehaviorInvalidOptionTypeRule>
 */
final class CreateObjectBehaviorInvalidOptionTypeRuleTest extends RuleTestCase
{
    use BehaviorRuleTestTrait;

    /**
     * Error identifier reported by the rule.
     */
    private const string IDENTIFIER = 'yii2.behavior.invalidOptionType';

    public static function getAdditionalConfigFiles(): array
    {
        return [dirname(__DIR__) . '/support/extension-behavior-definition-test.neon'];
    }

    public function testReportOnlyRejectedValuesAtTheOptionOrCallLine(): void
    {
        $this->assertDiagnostics(
            AsBehaviorTypes::class,
            'reported',
            BehaviorInvalidOptionTypeRuleProvider::asReported(),
        );
    }

    protected function getRule(): Rule
    {
        return new CreateObjectBehaviorInvalidOptionTypeRule(
            self::createReflectionProvider(),
            self::getContainer()->getByType(ServiceMap::class),
        );
    }
}

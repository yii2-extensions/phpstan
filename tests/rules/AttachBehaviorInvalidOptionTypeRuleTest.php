<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use yii2\extensions\phpstan\rules\AttachBehaviorInvalidOptionTypeRule;
use yii2\extensions\phpstan\ServiceMap;
use yii2\extensions\phpstan\tests\data\rule\behavior\TypeAttachCalls;
use yii2\extensions\phpstan\tests\provider\BehaviorInvalidOptionTypeRuleProvider;
use yii2\extensions\phpstan\tests\support\BehaviorRuleTestTrait;

use function dirname;

/**
 * Unit tests for {@see AttachBehaviorInvalidOptionTypeRule}.
 *
 * {@see BehaviorInvalidOptionTypeRuleProvider} for test case data providers.
 *
 * @extends RuleTestCase<AttachBehaviorInvalidOptionTypeRule>
 */
final class AttachBehaviorInvalidOptionTypeRuleTest extends RuleTestCase
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

    public function testReportOnlyRejectedValuesAtTheOptionOrArgumentLine(): void
    {
        $this->assertDiagnostics(
            TypeAttachCalls::class,
            'reported',
            BehaviorInvalidOptionTypeRuleProvider::attachReported(),
        );
    }

    protected function getRule(): Rule
    {
        return new AttachBehaviorInvalidOptionTypeRule(
            self::createReflectionProvider(),
            self::getContainer()->getByType(ServiceMap::class),
        );
    }
}

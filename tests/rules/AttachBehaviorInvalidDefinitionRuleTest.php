<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use yii2\extensions\phpstan\rules\AttachBehaviorInvalidDefinitionRule;
use yii2\extensions\phpstan\ServiceMap;
use yii2\extensions\phpstan\tests\data\rule\behavior\{AttachCalls, OverridingAttachOwner};
use yii2\extensions\phpstan\tests\provider\BehaviorInvalidDefinitionRuleProvider;
use yii2\extensions\phpstan\tests\support\BehaviorRuleTestTrait;

use function dirname;

/**
 * Unit tests for {@see AttachBehaviorInvalidDefinitionRule}.
 *
 * {@see BehaviorInvalidDefinitionRuleProvider} for test case data providers.
 *
 * @extends RuleTestCase<AttachBehaviorInvalidDefinitionRule>
 */
final class AttachBehaviorInvalidDefinitionRuleTest extends RuleTestCase
{
    use BehaviorRuleTestTrait;

    /**
     * Error identifier reported by the rule.
     */
    private const string IDENTIFIER = 'yii2.behavior.invalidDefinition';

    public static function getAdditionalConfigFiles(): array
    {
        return [dirname(__DIR__) . '/support/extension-behavior-definition-test.neon'];
    }

    public function testReportOnlyRejectedDefinitionsAtTheArgumentOrItemLine(): void
    {
        $this->assertDiagnostics(
            AttachCalls::class,
            'reported',
            BehaviorInvalidDefinitionRuleProvider::attachCallsReported(),
            OverridingAttachOwner::class,
        );
    }

    protected function getRule(): Rule
    {
        return new AttachBehaviorInvalidDefinitionRule(
            self::createReflectionProvider(),
            self::getContainer()->getByType(ServiceMap::class),
        );
    }
}

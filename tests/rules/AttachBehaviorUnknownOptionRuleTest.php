<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use yii2\extensions\phpstan\rules\AttachBehaviorUnknownOptionRule;
use yii2\extensions\phpstan\ServiceMap;
use yii2\extensions\phpstan\tests\data\rule\behavior\OptionAttachCalls;
use yii2\extensions\phpstan\tests\provider\BehaviorUnknownOptionRuleProvider;
use yii2\extensions\phpstan\tests\support\BehaviorRuleTestTrait;

use function dirname;

/**
 * Unit tests for {@see AttachBehaviorUnknownOptionRule}.
 *
 * {@see BehaviorUnknownOptionRuleProvider} for test case data providers.
 *
 * @extends RuleTestCase<AttachBehaviorUnknownOptionRule>
 */
final class AttachBehaviorUnknownOptionRuleTest extends RuleTestCase
{
    use BehaviorRuleTestTrait;

    /**
     * Error identifier reported by the rule.
     */
    private const string IDENTIFIER = 'yii2.behavior.unknownOption';

    public static function getAdditionalConfigFiles(): array
    {
        return [dirname(__DIR__) . '/support/extension-behavior-definition-test.neon'];
    }

    public function testReportOnlyUnknownOptionsAtTheOptionOrArgumentLine(): void
    {
        $this->assertDiagnostics(
            OptionAttachCalls::class,
            'reported',
            BehaviorUnknownOptionRuleProvider::attachReported(),
        );
    }

    protected function getRule(): Rule
    {
        return new AttachBehaviorUnknownOptionRule(
            self::createReflectionProvider(),
            self::getContainer()->getByType(ServiceMap::class),
        );
    }
}

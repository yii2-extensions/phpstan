<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use yii2\extensions\phpstan\rules\BehaviorsInvalidDefinitionRule;
use yii2\extensions\phpstan\ServiceMap;
use yii2\extensions\phpstan\tests\data\rule\behavior\ReportedBehaviorsOwner;
use yii2\extensions\phpstan\tests\provider\BehaviorInvalidDefinitionRuleProvider;
use yii2\extensions\phpstan\tests\support\BehaviorRuleTestTrait;

use function count;
use function dirname;

/**
 * Unit tests for {@see BehaviorsInvalidDefinitionRule}.
 *
 * {@see BehaviorInvalidDefinitionRuleProvider} for test case data providers.
 *
 * @extends RuleTestCase<BehaviorsInvalidDefinitionRule>
 */
final class BehaviorsInvalidDefinitionRuleTest extends RuleTestCase
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

    /**
     * @phpstan-param class-string $owner
     * @phpstan-param list<array{string, string|null, string}> $expected
     */
    #[DataProviderExternal(BehaviorInvalidDefinitionRuleProvider::class, 'reportedProvider')]
    public function testReportAtTheItemOrReturnLine(string $owner, array $expected): void
    {
        $this->assertDiagnostics(
            $owner,
            'behaviors',
            $expected,
        );
    }

    public function testReportEveryEntryOfTheRejectedFixtureOnce(): void
    {
        self::assertCount(
            count((new ReportedBehaviorsOwner())->behaviors()),
            $this->classErrors(ReportedBehaviorsOwner::class),
            'Each entry that Yii rejects must be reported once.',
        );
    }

    /**
     * @phpstan-param list<class-string> $classes
     */
    #[DataProviderExternal(BehaviorInvalidDefinitionRuleProvider::class, 'standAsideProvider')]
    public function testStandAsideWhenDefinitionIsAttachableOrNotProven(array $classes): void
    {
        self::assertSame(
            [],
            $this->classErrors(...$classes),
            'No diagnostic must be reported.',
        );
    }

    protected function getRule(): Rule
    {
        return new BehaviorsInvalidDefinitionRule(
            self::createReflectionProvider(),
            self::getContainer()->getByType(ServiceMap::class),
        );
    }
}

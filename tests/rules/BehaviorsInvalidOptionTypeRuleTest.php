<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use yii2\extensions\phpstan\rules\BehaviorsInvalidOptionTypeRule;
use yii2\extensions\phpstan\ServiceMap;
use yii2\extensions\phpstan\tests\provider\BehaviorInvalidOptionTypeRuleProvider;
use yii2\extensions\phpstan\tests\support\BehaviorRuleTestTrait;

use function dirname;

/**
 * Unit tests for {@see BehaviorsInvalidOptionTypeRule}.
 *
 * {@see BehaviorInvalidOptionTypeRuleProvider} for test case data providers.
 *
 * @extends RuleTestCase<BehaviorsInvalidOptionTypeRule>
 */
final class BehaviorsInvalidOptionTypeRuleTest extends RuleTestCase
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

    /**
     * @phpstan-param class-string $owner
     * @phpstan-param list<array{string, string|null, string}> $expected
     */
    #[DataProviderExternal(BehaviorInvalidOptionTypeRuleProvider::class, 'reportedProvider')]
    public function testReportAtTheOptionOrItemLine(string $owner, array $expected): void
    {
        $this->assertDiagnostics(
            $owner,
            'behaviors',
            $expected,
        );
    }

    /**
     * @phpstan-param list<class-string> $classes
     */
    #[DataProviderExternal(BehaviorInvalidOptionTypeRuleProvider::class, 'standAsideProvider')]
    public function testStandAsideWhenValueIsAcceptedOrNotProven(array $classes): void
    {
        self::assertSame(
            [],
            $this->classErrors(...$classes),
            'No diagnostic must be reported.',
        );
    }

    protected function getRule(): Rule
    {
        return new BehaviorsInvalidOptionTypeRule(
            self::createReflectionProvider(),
            self::getContainer()->getByType(ServiceMap::class),
        );
    }
}

<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use yii2\extensions\phpstan\rules\BehaviorsUnknownOptionRule;
use yii2\extensions\phpstan\ServiceMap;
use yii2\extensions\phpstan\tests\provider\BehaviorUnknownOptionRuleProvider;
use yii2\extensions\phpstan\tests\support\BehaviorRuleTestTrait;

use function dirname;

/**
 * Unit tests for {@see BehaviorsUnknownOptionRule}.
 *
 * {@see BehaviorUnknownOptionRuleProvider} for test case data providers.
 *
 * @extends RuleTestCase<BehaviorsUnknownOptionRule>
 */
final class BehaviorsUnknownOptionRuleTest extends RuleTestCase
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

    /**
     * @phpstan-param class-string $owner
     * @phpstan-param list<array{string, string|null, string}> $expected
     */
    #[DataProviderExternal(BehaviorUnknownOptionRuleProvider::class, 'reportedProvider')]
    public function testReportAtTheOptionOrReturnLine(string $owner, array $expected): void
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
    #[DataProviderExternal(BehaviorUnknownOptionRuleProvider::class, 'standAsideProvider')]
    public function testStandAsideWhenOptionIsAssignableOrNotProven(array $classes): void
    {
        self::assertSame(
            [],
            $this->classErrors(...$classes),
            'No diagnostic must be reported.',
        );
    }

    protected function getRule(): Rule
    {
        return new BehaviorsUnknownOptionRule(
            self::createReflectionProvider(),
            self::getContainer()->getByType(ServiceMap::class),
        );
    }
}

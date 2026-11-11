<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use yii2\extensions\phpstan\rules\ValidatorIncompleteRuleRule;
use yii2\extensions\phpstan\tests\provider\ValidatorIncompleteRuleRuleProvider;
use yii2\extensions\phpstan\tests\support\BehaviorRuleTestTrait;

use function count;
use function dirname;
use function sprintf;

/**
 * Unit tests for {@see ValidatorIncompleteRuleRule}.
 *
 * {@see ValidatorIncompleteRuleRuleProvider} for test case data providers.
 *
 * @extends RuleTestCase<ValidatorIncompleteRuleRule>
 */
final class ValidatorIncompleteRuleRuleTest extends RuleTestCase
{
    use BehaviorRuleTestTrait;

    /**
     * Error identifier reported by the rule.
     */
    private const string IDENTIFIER = 'yii2.validator.incompleteRule';

    public static function getAdditionalConfigFiles(): array
    {
        return [dirname(__DIR__) . '/support/extension-test.neon'];
    }

    /**
     * @phpstan-param class-string $owner
     * @phpstan-param list<array{string, string|null, string}> $expected
     */
    #[DataProviderExternal(ValidatorIncompleteRuleRuleProvider::class, 'reportedProvider')]
    public function testReportAtTheItemOrReturnLine(string $owner, array $expected): void
    {
        $this->assertDiagnostics(
            $owner,
            'rules',
            $expected,
        );
    }

    public function testReportEveryRecordedRuleOfTheReportedFixturesOnce(): void
    {
        foreach (ValidatorIncompleteRuleRuleProvider::reportedRules() as $model => $indexes) {
            self::assertCount(
                count($indexes),
                $this->classErrors($model),
                sprintf('Each recorded rule of `%s` must be reported once.', $model),
            );
        }
    }

    /**
     * @phpstan-param list<class-string> $classes
     */
    #[DataProviderExternal(ValidatorIncompleteRuleRuleProvider::class, 'standAsideProvider')]
    public function testStandAsideWhenRuleIsCompleteOrNotProven(array $classes): void
    {
        self::assertSame(
            [],
            $this->classErrors(...$classes),
            'No diagnostic must be reported.',
        );
    }

    protected function getRule(): Rule
    {
        return new ValidatorIncompleteRuleRule();
    }
}

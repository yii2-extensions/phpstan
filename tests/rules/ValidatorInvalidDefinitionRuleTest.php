<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use yii2\extensions\phpstan\rules\ValidatorInvalidDefinitionRule;
use yii2\extensions\phpstan\tests\provider\ValidatorInvalidDefinitionRuleProvider;
use yii2\extensions\phpstan\tests\support\BehaviorRuleTestTrait;

use function count;
use function dirname;
use function sprintf;

/**
 * Unit tests for {@see ValidatorInvalidDefinitionRule}.
 *
 * {@see ValidatorInvalidDefinitionRuleProvider} for test case data providers.
 *
 * @extends RuleTestCase<ValidatorInvalidDefinitionRule>
 */
final class ValidatorInvalidDefinitionRuleTest extends RuleTestCase
{
    use BehaviorRuleTestTrait;

    /**
     * Error identifier reported by the rule.
     */
    private const string IDENTIFIER = 'yii2.validator.invalidDefinition';

    public static function getAdditionalConfigFiles(): array
    {
        return [dirname(__DIR__) . '/support/extension-test.neon'];
    }

    /**
     * @phpstan-param class-string $owner
     * @phpstan-param list<array{string, string|null, string}> $expected
     */
    #[DataProviderExternal(ValidatorInvalidDefinitionRuleProvider::class, 'reportedProvider')]
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
        foreach (ValidatorInvalidDefinitionRuleProvider::reportedRules() as $model => $indexes) {
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
    #[DataProviderExternal(ValidatorInvalidDefinitionRuleProvider::class, 'standAsideProvider')]
    public function testStandAsideWhenTypeIsValidOrNotProven(array $classes): void
    {
        self::assertSame(
            [],
            $this->classErrors(...$classes),
            'No diagnostic must be reported.',
        );
    }

    protected function getRule(): Rule
    {
        return new ValidatorInvalidDefinitionRule();
    }
}

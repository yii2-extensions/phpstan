<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\rules;

use PHPStan\Analyser\Error;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use yii2\extensions\phpstan\rules\CreateObjectInvalidAsDefinitionRule;
use yii2\extensions\phpstan\ServiceMap;
use yii2\extensions\phpstan\tests\data\rule\behavior\AsOptions;
use yii2\extensions\phpstan\tests\provider\BehaviorInvalidAsDefinitionRuleProvider;
use yii2\extensions\phpstan\tests\support\{BehaviorFixture, BehaviorRuleTestTrait};

use function array_filter;
use function array_values;
use function count;
use function dirname;

/**
 * Unit tests for {@see CreateObjectInvalidAsDefinitionRule}.
 *
 * {@see BehaviorInvalidAsDefinitionRuleProvider} for test case data providers.
 *
 * @extends RuleTestCase<CreateObjectInvalidAsDefinitionRule>
 */
final class CreateObjectInvalidAsDefinitionRuleTest extends RuleTestCase
{
    use BehaviorRuleTestTrait;

    /**
     * Error identifier reported by the rule.
     */
    private const string IDENTIFIER = 'yii2.behavior.invalidAsDefinition';

    public static function getAdditionalConfigFiles(): array
    {
        return [dirname(__DIR__) . '/support/extension-behavior-definition-test.neon'];
    }

    public function testReportEveryRejectedOptionOnce(): void
    {
        self::assertCount(
            count((new AsOptions())->reported()),
            $this->methodErrors('reported'),
            'Each option that Yii rejects must be reported once.',
        );
    }

    public function testReportOnlyRejectedOptionsAtTheOptionOrCallLine(): void
    {
        $this->assertDiagnostics(
            AsOptions::class,
            'reported',
            BehaviorInvalidAsDefinitionRuleProvider::reported(),
        );
    }

    public function testStandAsideWhenOptionIsValidOrNotProven(): void
    {
        self::assertSame(
            [],
            [...$this->methodErrors('valid'), ...$this->methodErrors('standAside')],
            'No diagnostic must be reported.',
        );
    }

    protected function getRule(): Rule
    {
        return new CreateObjectInvalidAsDefinitionRule(
            self::createReflectionProvider(),
            self::getContainer()->getByType(ServiceMap::class),
        );
    }

    /**
     * Returns the diagnostics located in one method of the fixture.
     *
     * @return list<Error>
     */
    private function methodErrors(string $method): array
    {
        $start = BehaviorFixture::line(AsOptions::class, $method, 'function');
        $end = BehaviorFixture::line(AsOptions::class, $method, '];', 'return [');

        return array_values(
            array_filter(
                $this->classErrors(AsOptions::class),
                static fn(Error $error): bool => $error->getLine() >= $start && $error->getLine() <= $end,
            ),
        );
    }
}

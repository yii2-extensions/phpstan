<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\{DataProviderExternal, RequiresPhp};
use ReflectionClass;
use yii\base\Action;
use yii\base\UnknownPropertyException;
use yii2\extensions\phpstan\rules\ExternalActionUnknownOptionRule;
use yii2\extensions\phpstan\ServiceMap;
use yii2\extensions\phpstan\tests\provider\{ExternalActionRuntimeProvider, ExternalActionUnknownOptionRuleProvider};
use yii2\extensions\phpstan\tests\support\ExternalActionRuleTestTrait;

use function dirname;
use function preg_match;
use function sprintf;

/**
 * Unit tests for {@see ExternalActionUnknownOptionRule}.
 *
 * {@see ExternalActionUnknownOptionRuleProvider} for test case data providers.
 *
 * @extends RuleTestCase<ExternalActionUnknownOptionRule>
 */
final class ExternalActionUnknownOptionRuleTest extends RuleTestCase
{
    use ExternalActionRuleTestTrait;

    /**
     * Error identifier of the rule.
     */
    private const string IDENTIFIER = 'yii2.externalAction.unknownOption';

    public static function getAdditionalConfigFiles(): array
    {
        return [dirname(__DIR__) . '/support/extension-external-action-test.neon'];
    }

    /**
     * @phpstan-param class-string $controller
     * @phpstan-param list<array{string|null, string|null, string}> $expected
     */
    #[DataProviderExternal(ExternalActionUnknownOptionRuleProvider::class, 'reportedProvider')]
    public function testReportAtTheKeyDefinitionOrReturnLine(string $controller, array $expected): void
    {
        $this->assertControllerDiagnostics($controller, $expected);
    }

    public function testReportOnlyOptionsThatCreateActionRejects(): void
    {
        $reported = 0;

        foreach (ExternalActionRuntimeProvider::controllers() as $controller) {
            foreach ($this->controllerErrors($controller) as $error) {
                $matches = [];

                self::assertSame(
                    1,
                    preg_match('/^External action \'(\S+)\' in \S+::actions\(\) sets unknown property (\S+)::\$(\S+): /', $error->getMessage(), $matches),
                    'Diagnostic must name the action ID, the created class and the option.',
                );

                [, $actionId, $class, $option] = $matches + ['', '', '', ''];

                [$outcome, $message] = ExternalActionRuntimeProvider::outcome($controller, $actionId);

                self::assertTrue(
                    $outcome === UnknownPropertyException::class && $message === "Setting unknown property: {$class}::{$option}",
                    sprintf('Runtime outcome of `%s` must reject the reported option.', $actionId),
                );

                $reported++;
            }
        }

        self::assertSame(
            (new ReflectionClass(Action::class))->hasMethod('setModule') ? 20 : 21,
            $reported,
            'Every reported option must be checked.',
        );
    }

    /**
     * @phpstan-param list<class-string> $classes
     */
    #[DataProviderExternal(ExternalActionUnknownOptionRuleProvider::class, 'standAsideProvider')]
    public function testStandAsideWhenControllerMapOrDefinitionIsNotProven(array $classes): void
    {
        self::assertSame(
            [],
            $this->controllerErrors(...$classes),
            'No diagnostic must be reported.',
        );
    }

    #[RequiresPhp('>= 8.4')]
    public function testStandAsideWhenIdOrControllerIsHooked(): void
    {
        self::assertSame(
            [],
            $this->fileErrors(self::fixtureFile('hook/HookActionController.php')),
            'Hooked construction properties must leave every definition unreported.',
        );
    }

    protected function getRule(): Rule
    {
        return new ExternalActionUnknownOptionRule(
            self::createReflectionProvider(),
            self::getContainer()->getByType(ServiceMap::class),
        );
    }
}

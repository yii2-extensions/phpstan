<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\{DataProviderExternal, RequiresPhp};
use TypeError;
use yii2\extensions\phpstan\rules\ExternalActionInvalidOptionTypeRule;
use yii2\extensions\phpstan\ServiceMap;
use yii2\extensions\phpstan\tests\provider\{ExternalActionInvalidOptionTypeRuleProvider, ExternalActionRuntimeProvider};
use yii2\extensions\phpstan\tests\support\ExternalActionRuleTestTrait;

use function dirname;
use function preg_match;
use function sprintf;
use function str_contains;

/**
 * Unit tests for {@see ExternalActionInvalidOptionTypeRule}.
 *
 * {@see ExternalActionInvalidOptionTypeRuleProvider} for test case data providers.
 *
 * @extends RuleTestCase<ExternalActionInvalidOptionTypeRule>
 */
final class ExternalActionInvalidOptionTypeRuleTest extends RuleTestCase
{
    use ExternalActionRuleTestTrait;

    /**
     * Error identifier of the rule.
     */
    private const string IDENTIFIER = 'yii2.externalAction.invalidOptionType';

    public static function getAdditionalConfigFiles(): array
    {
        return [dirname(__DIR__) . '/support/extension-external-action-test.neon'];
    }

    /**
     * @phpstan-param class-string $controller
     * @phpstan-param list<array{string|null, string|null, string}> $expected
     */
    #[DataProviderExternal(ExternalActionInvalidOptionTypeRuleProvider::class, 'reportedProvider')]
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
                    preg_match(
                        '/^External action \'(\S+)\' in \S+::actions\(\) sets (\S+)::\$(\S+) to /',
                        $error->getMessage(),
                        $matches,
                    ),
                    'Diagnostic must name the action ID, the created class and the option.',
                );

                [, $actionId, $class, $option] = $matches + ['', '', '', ''];

                [$outcome, $message] = ExternalActionRuntimeProvider::outcome($controller, $actionId);

                self::assertTrue(
                    $outcome === TypeError::class
                        && $message !== null
                        && (
                            str_contains($message, "{$class}::\${$option}")
                            || str_contains($message, "{$class}::set")
                        ),
                    sprintf('Runtime outcome of `%s` must reject the reported option.', $actionId),
                );

                $reported++;
            }
        }

        self::assertSame(
            9,
            $reported,
            'Every reported option must be checked.',
        );
    }

    /**
     * @phpstan-param list<class-string> $classes
     */
    #[DataProviderExternal(ExternalActionInvalidOptionTypeRuleProvider::class, 'standAsideProvider')]
    public function testStandAsideWhenControllerMapOrDefinitionIsNotProven(array $classes): void
    {
        self::assertSame(
            [],
            $this->controllerErrors(...$classes),
            'No diagnostic must be reported.',
        );
    }

    #[RequiresPhp('>= 8.5')]
    public function testStandAsideWhenIdIsHookedThroughTrait(): void
    {
        self::assertSame(
            [],
            $this->fileErrors(self::fixtureFile('hook/TraitHookActionController.php')),
            'Trait-hooked construction properties must leave every definition unreported.',
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
        return new ExternalActionInvalidOptionTypeRule(
            self::createReflectionProvider(),
            self::getContainer()->getByType(ServiceMap::class),
        );
    }
}

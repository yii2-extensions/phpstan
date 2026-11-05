<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use ReflectionClass;
use yii\base\UnknownPropertyException;
use yii2\extensions\phpstan\rules\WidgetUnknownOptionRule;
use yii2\extensions\phpstan\ServiceMap;
use yii2\extensions\phpstan\tests\provider\{WidgetRuntimeProvider, WidgetUnknownOptionRuleProvider};
use yii2\extensions\phpstan\tests\support\{CreateObjectRuleTestTrait, FixtureMethod};

use function dirname;
use function preg_match;
use function sprintf;

/**
 * Unit tests for {@see WidgetUnknownOptionRule}.
 *
 * {@see WidgetUnknownOptionRuleProvider} for test case data providers.
 *
 * @extends RuleTestCase<WidgetUnknownOptionRule>
 */
final class WidgetUnknownOptionRuleTest extends RuleTestCase
{
    use CreateObjectRuleTestTrait;

    /**
     * Error identifier of the rule.
     */
    private const string IDENTIFIER = 'yii2.widget.unknownOption';

    public static function getAdditionalConfigFiles(): array
    {
        return [dirname(__DIR__) . '/support/extension-widget-test.neon'];
    }

    public function testAcceptOptionsTheCreatedWidgetAssigns(): void
    {
        $this->assertNoDiagnostics('WidgetValidOption.php');
        $this->assertNoDiagnostics('WidgetInvalidOptionType.php');
    }

    public function testReportOnlyCallsThatThrowUnknownPropertyException(): void
    {
        $reported = 0;

        foreach (WidgetRuntimeProvider::fixtures() as $fixture) {
            foreach ($this->fixtureErrors((string) (new ReflectionClass($fixture))->getFileName()) as $error) {
                $method = FixtureMethod::at($fixture, $error->getLine());
                $matches = [];

                self::assertSame(
                    1,
                    preg_match(
                        '/^\S+::(?:widget|begin)\(\) sets unknown property (\S+)::\$(\S+): /',
                        $error->getMessage(),
                        $matches,
                    ),
                    sprintf('Diagnostic of `%s()` must name the created class and the option.', $method),
                );

                $class = $matches[1] ?? '';
                $name = $matches[2] ?? '';

                self::assertSame(
                    [UnknownPropertyException::class, "Setting unknown property: {$class}::{$name}"],
                    WidgetRuntimeProvider::outcome($fixture, $method),
                    sprintf('Runtime outcome of `%s()` must reject the reported option.', $method),
                );

                $reported++;
            }
        }

        self::assertSame(
            23,
            $reported,
            'Every reported call must be checked.',
        );
    }

    /**
     * @phpstan-param class-string $fixture
     * @phpstan-param list<array{string|null, string}> $expected
     */
    #[DataProviderExternal(WidgetUnknownOptionRuleProvider::class, 'reportedProvider')]
    public function testReportUnknownOptionsAtTheirKeyOrCall(string $fixture, string $method, array $expected): void
    {
        $this->assertMethodDiagnostics($fixture, $method, $expected, '::');
    }

    public function testStandAsideWhenCallConfigurationOrCreatedWidgetIsNotProven(): void
    {
        $this->assertNoDiagnostics('WidgetStandAside.php');
    }

    protected function getRule(): Rule
    {
        return new WidgetUnknownOptionRule(
            self::createReflectionProvider(),
            self::getContainer()->getByType(ServiceMap::class),
        );
    }
}

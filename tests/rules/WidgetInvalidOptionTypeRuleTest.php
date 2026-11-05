<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use ReflectionClass;
use TypeError;
use yii2\extensions\phpstan\rules\WidgetInvalidOptionTypeRule;
use yii2\extensions\phpstan\ServiceMap;
use yii2\extensions\phpstan\tests\provider\{WidgetInvalidOptionTypeRuleProvider, WidgetRuntimeProvider};
use yii2\extensions\phpstan\tests\support\{CreateObjectRuleTestTrait, FixtureMethod};

use function dirname;
use function preg_match;
use function sprintf;

/**
 * Unit tests for {@see WidgetInvalidOptionTypeRule}.
 *
 * {@see WidgetInvalidOptionTypeRuleProvider} for test case data providers.
 *
 * @extends RuleTestCase<WidgetInvalidOptionTypeRule>
 */
final class WidgetInvalidOptionTypeRuleTest extends RuleTestCase
{
    use CreateObjectRuleTestTrait;

    /**
     * Error identifier of the rule.
     */
    private const string IDENTIFIER = 'yii2.widget.invalidOptionType';

    public static function getAdditionalConfigFiles(): array
    {
        return [dirname(__DIR__) . '/support/extension-widget-test.neon'];
    }

    public function testAcceptValuesTheCreatedWidgetAssigns(): void
    {
        $this->assertNoDiagnostics('WidgetValidOption.php');
        $this->assertNoDiagnostics('WidgetUnknownOption.php');
    }

    public function testReportOnlyCallsThatThrowTypeError(): void
    {
        $reported = 0;

        foreach (WidgetRuntimeProvider::fixtures() as $fixture) {
            foreach ($this->fixtureErrors((string) (new ReflectionClass($fixture))->getFileName()) as $error) {
                $method = FixtureMethod::at($fixture, $error->getLine());
                $matches = [];

                self::assertSame(
                    1,
                    preg_match(
                        '/^\S+::(?:widget|begin)\(\) sets \S+::\$(\S+) to .+, but (.+) accepts only /',
                        $error->getMessage(),
                        $matches,
                    ),
                    sprintf('Diagnostic of `%s()` must name the option and the member.', $method),
                );

                $name = $matches[1] ?? '';
                $member = $matches[2] ?? '';

                [$outcome, $message] = WidgetRuntimeProvider::outcome($fixture, $method);

                self::assertSame(
                    TypeError::class,
                    $outcome,
                    sprintf('Runtime outcome of `%s()` must be a type error.', $method),
                );
                self::assertStringContainsString(
                    $member === 'the property' ? "::\${$name}" : "{$member}: Argument #1",
                    (string) $message,
                    sprintf('Type error of `%s()` must come from the reported member.', $method),
                );

                $reported++;
            }
        }

        self::assertSame(
            10,
            $reported,
            'Every reported call must be checked.',
        );
    }

    /**
     * @phpstan-param class-string $fixture
     * @phpstan-param list<array{string|null, string}> $expected
     */
    #[DataProviderExternal(WidgetInvalidOptionTypeRuleProvider::class, 'reportedProvider')]
    public function testReportRejectedValuesAtTheirKeyOrCall(string $fixture, string $method, array $expected): void
    {
        $this->assertMethodDiagnostics($fixture, $method, $expected, '::');
    }

    public function testStandAsideWhenCallConfigurationOrCreatedWidgetIsNotProven(): void
    {
        $this->assertNoDiagnostics('WidgetStandAside.php');
    }

    protected function getRule(): Rule
    {
        return new WidgetInvalidOptionTypeRule(
            self::createReflectionProvider(),
            self::getContainer()->getByType(ServiceMap::class),
        );
    }
}

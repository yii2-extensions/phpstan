<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use ReflectionClass;
use yii\base\UnknownPropertyException;
use yii2\extensions\phpstan\rules\NewObjectUnknownOptionRule;
use yii2\extensions\phpstan\ServiceMap;
use yii2\extensions\phpstan\tests\provider\{NewObjectRuntimeProvider, NewObjectUnknownOptionRuleProvider};
use yii2\extensions\phpstan\tests\support\{CreateObjectRuleTestTrait, FixtureMethod};

use function dirname;
use function preg_match;
use function sprintf;

/**
 * Unit tests for {@see NewObjectUnknownOptionRule}.
 *
 * {@see NewObjectUnknownOptionRuleProvider} for test case data providers.
 *
 * @extends RuleTestCase<NewObjectUnknownOptionRule>
 */
final class NewObjectUnknownOptionRuleTest extends RuleTestCase
{
    use CreateObjectRuleTestTrait;

    /**
     * Error identifier of the rule.
     */
    private const string IDENTIFIER = 'yii2.new.unknownOption';

    public static function getAdditionalConfigFiles(): array
    {
        return [dirname(__DIR__) . '/support/extension-create-object-test.neon'];
    }

    public function testAcceptOptionsTheConstructedClassAssigns(): void
    {
        $this->assertNoDiagnostics('NewObjectValidOption.php');
        $this->assertNoDiagnostics('NewObjectInvalidOptionType.php');
    }

    public function testReportOnlyConstructionsThatThrowUnknownPropertyException(): void
    {
        $reported = 0;

        foreach (NewObjectRuntimeProvider::fixtures() as $fixture) {
            foreach ($this->fixtureErrors((string) (new ReflectionClass($fixture))->getFileName()) as $error) {
                $method = FixtureMethod::at($fixture, $error->getLine());
                $matches = [];

                self::assertSame(
                    1,
                    preg_match('/^new (.+)\(\) sets unknown property \$(.+): /', $error->getMessage(), $matches),
                    sprintf('Diagnostic of `%s()` must name the class and the option.', $method),
                );
                $class = $matches[1] ?? '';
                $name = $matches[2] ?? '';

                self::assertSame(
                    [UnknownPropertyException::class, "Setting unknown property: {$class}::{$name}"],
                    NewObjectRuntimeProvider::outcome($fixture, $method),
                    sprintf('Runtime outcome of `%s()` must reject the reported option.', $method),
                );

                $reported++;
            }
        }

        self::assertSame(
            21,
            $reported,
            'Every reported construction must be checked.',
        );
    }

    /**
     * @phpstan-param class-string $fixture
     * @phpstan-param list<array{string|null, string}> $expected
     */
    #[DataProviderExternal(NewObjectUnknownOptionRuleProvider::class, 'reportedProvider')]
    public function testReportUnknownOptionsAtTheirKeyOrConstruction(
        string $fixture,
        string $method,
        array $expected,
    ): void {
        $this->assertMethodDiagnostics($fixture, $method, $expected, 'new ');
    }

    public function testStandAsideWhenClassArgumentsOrAssignmentIsNotProven(): void
    {
        $this->assertNoDiagnostics('NewObjectStandAside.php');
        $this->assertNoDiagnostics('NewObjectLateStaticReference.php');
        $this->assertNoDiagnostics('NewObjectFirstClassCallable.php');
    }

    protected function getRule(): Rule
    {
        return new NewObjectUnknownOptionRule(
            self::createReflectionProvider(),
            self::getContainer()->getByType(ServiceMap::class),
        );
    }
}

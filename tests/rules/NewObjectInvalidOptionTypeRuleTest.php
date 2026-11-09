<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\{DataProviderExternal, RequiresPhp};
use ReflectionClass;
use TypeError;
use yii2\extensions\phpstan\rules\NewObjectInvalidOptionTypeRule;
use yii2\extensions\phpstan\tests\provider\{
    NewObjectInvalidOptionTypeRuleProvider,
    NewObjectRuntimeProvider,
    TraitPropertyOptionProvider,
};
use yii2\extensions\phpstan\tests\support\{CreateObjectRuleTestTrait, FixtureMethod};

use function dirname;
use function preg_match;
use function sprintf;

/**
 * Unit tests for {@see NewObjectInvalidOptionTypeRule}.
 *
 * {@see NewObjectInvalidOptionTypeRuleProvider} for test case data providers.
 *
 * @extends RuleTestCase<NewObjectInvalidOptionTypeRule>
 */
final class NewObjectInvalidOptionTypeRuleTest extends RuleTestCase
{
    use CreateObjectRuleTestTrait;

    /**
     * Error identifier of the rule.
     */
    private const string IDENTIFIER = 'yii2.new.invalidOptionType';

    public static function getAdditionalConfigFiles(): array
    {
        return [dirname(__DIR__) . '/support/extension-create-object-test.neon'];
    }

    public function testAcceptValuesTheConstructedClassAssigns(): void
    {
        $this->assertNoDiagnostics('NewObjectValidOption.php');
        $this->assertNoDiagnostics('NewObjectUnknownOption.php');
        $this->assertNoDiagnostics('NewObjectSelfReference.php');
    }

    public function testReportOnlyConstructionsThatThrowTypeError(): void
    {
        $reported = 0;

        foreach (NewObjectRuntimeProvider::fixtures() as $fixture) {
            foreach ($this->fixtureErrors((string) (new ReflectionClass($fixture))->getFileName()) as $error) {
                $method = FixtureMethod::at($fixture, $error->getLine());
                $matches = [];

                self::assertSame(
                    1,
                    preg_match(
                        '/^new .+\(\) sets \$(\S+) to .+, but (.+) accepts only /',
                        $error->getMessage(),
                        $matches,
                    ),
                    sprintf('Diagnostic of `%s()` must name the option and the member.', $method),
                );

                $name = $matches[1] ?? '';
                $member = $matches[2] ?? '';

                [$outcome, $message] = NewObjectRuntimeProvider::outcome($fixture, $method);

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
            'Every reported construction must be checked.',
        );
    }

    /**
     * @phpstan-param class-string $fixture
     * @phpstan-param list<array{string|null, string}> $expected
     */
    #[DataProviderExternal(TraitPropertyOptionProvider::class, 'newObjectInvalidOptionTypeProvider')]
    #[RequiresPhp('>= 8.5.0')]
    public function testReportOnlyControlsWhenTraitRedeclaresInheritedProperty(
        string $fixture,
        string $method,
        array $expected,
    ): void {
        $this->assertMethodDiagnostics($fixture, $method, $expected, 'new ');
    }

    /**
     * @phpstan-param class-string $fixture
     * @phpstan-param list<array{string|null, string}> $expected
     */
    #[DataProviderExternal(NewObjectInvalidOptionTypeRuleProvider::class, 'reportedProvider')]
    public function testReportRejectedValuesAtTheirKeyOrConstruction(
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
        return new NewObjectInvalidOptionTypeRule(self::createReflectionProvider());
    }
}

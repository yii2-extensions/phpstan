<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use yii\base\UnknownPropertyException;
use yii2\extensions\phpstan\rules\CreateObjectUnknownOptionRule;
use yii2\extensions\phpstan\ServiceMap;
use yii2\extensions\phpstan\tests\data\rule\{CreateObjectComponentOption, CreateObjectContainerBinding};
use yii2\extensions\phpstan\tests\provider\{
    CreateObjectComponentOptionRuntimeProvider,
    CreateObjectContainerBindingRuntimeProvider,
    CreateObjectUnknownOptionRuleProvider,
};
use yii2\extensions\phpstan\tests\support\{CreateObjectRuleTestTrait, FixtureMethod};

use function dirname;
use function sprintf;
use function strlen;
use function strrpos;
use function substr;

/**
 * Unit tests for {@see CreateObjectUnknownOptionRule}.
 *
 * {@see CreateObjectUnknownOptionRuleProvider} for test case data providers.
 *
 * @extends RuleTestCase<CreateObjectUnknownOptionRule>
 */
final class CreateObjectUnknownOptionRuleTest extends RuleTestCase
{
    use CreateObjectRuleTestTrait;

    /**
     * Error identifier of the rule.
     */
    private const string IDENTIFIER = 'yii2.createObject.unknownOption';

    /**
     * Start of the message of `yii\base\UnknownPropertyException` thrown by `__set()`.
     */
    private const string RUNTIME_PREFIX = 'Setting unknown property: ';

    public static function getAdditionalConfigFiles(): array
    {
        return [dirname(__DIR__) . '/support/extension-create-object-test.neon'];
    }

    public function testAcceptConstantKnownOptionsTheClassAssigns(): void
    {
        $this->assertNoDiagnostics('CreateObjectConstantValidOption.php');
    }

    public function testAcceptPublicPropertiesSettersInheritedMembersAndUnclaimedOptions(): void
    {
        $this->assertNoDiagnostics('CreateObjectValidOption.php');
    }

    public function testReportBoundOptionsOnlyForCallsThatThrowUnknownPropertyException(): void
    {
        $errors = $this->fixtureErrors(dirname(__DIR__) . '/data/rule/CreateObjectContainerBinding.php');

        self::assertNotSame(
            [],
            $errors,
            'Fixture must produce diagnostics.',
        );

        foreach ($errors as $error) {
            $method = FixtureMethod::at(CreateObjectContainerBinding::class, $error->getLine());

            [$outcome, $message] = CreateObjectContainerBindingRuntimeProvider::outcome($method);

            self::assertSame(
                UnknownPropertyException::class,
                $outcome,
                sprintf('Runtime outcome of `%s()` must be an unknown property.', $method),
            );

            $prefix = strlen(self::RUNTIME_PREFIX);
            $class = substr($message, $prefix, (int) strrpos($message, '::') - $prefix);

            self::assertStringContainsString(
                "unknown property {$class}::\$",
                $error->getMessage(),
                sprintf('Diagnostic of `%s()` must name the class Yii builds.', $method),
            );
        }
    }

    public function testReportComponentOptionsOnlyForCallsThatThrowUnknownPropertyException(): void
    {
        $errors = $this->fixtureErrors(dirname(__DIR__) . '/data/rule/CreateObjectComponentOption.php');

        self::assertNotSame(
            [],
            $errors,
            'Fixture must produce diagnostics.',
        );

        foreach ($errors as $error) {
            $method = FixtureMethod::at(CreateObjectComponentOption::class, $error->getLine());

            [$outcome, $message] = CreateObjectComponentOptionRuntimeProvider::outcome($method);

            self::assertSame(
                UnknownPropertyException::class,
                $outcome,
                sprintf('Runtime outcome of `%s()` must be an unknown property.', $method),
            );

            $prefix = strlen(self::RUNTIME_PREFIX);
            $class = substr($message, $prefix, (int) strrpos($message, '::') - $prefix);

            self::assertStringContainsString(
                "unknown property {$class}::\$",
                $error->getMessage(),
                sprintf('Diagnostic of `%s()` must name the class Yii reports.', $method),
            );
        }
    }

    /**
     * @phpstan-param class-string $fixture
     * @phpstan-param list<array{string|null, string}> $expected
     */
    #[DataProviderExternal(CreateObjectUnknownOptionRuleProvider::class, 'reportedProvider')]
    public function testReportUnknownOptionsAtTheirKeyOrCall(string $fixture, string $method, array $expected): void
    {
        $this->assertMethodDiagnostics($fixture, $method, $expected);
    }

    public function testStandAsideWhenNativeTypesDoNotProveOneConstantConfiguration(): void
    {
        $this->assertNoDiagnostics('CreateObjectConstantStandAside.php');
    }

    public function testStandAsideWhenTargetOrAssignmentIsNotProven(): void
    {
        $this->assertNoDiagnostics('CreateObjectStandAside.php');
    }

    protected function getRule(): Rule
    {
        return new CreateObjectUnknownOptionRule(
            self::createReflectionProvider(),
            self::getContainer()->getByType(ServiceMap::class),
        );
    }
}

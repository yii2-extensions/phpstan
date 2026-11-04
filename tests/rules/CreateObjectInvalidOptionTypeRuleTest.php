<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\{DataProviderExternal, TestWith};
use TypeError;
use yii2\extensions\phpstan\rules\CreateObjectInvalidOptionTypeRule;
use yii2\extensions\phpstan\ServiceMap;
use yii2\extensions\phpstan\tests\data\rule\{
    CreateObjectComponentOption,
    CreateObjectConstructorArgument,
    CreateObjectContainerBinding,
};
use yii2\extensions\phpstan\tests\provider\{
    CreateObjectComponentOptionRuntimeProvider,
    CreateObjectConstructorArgumentRuntimeProvider,
    CreateObjectContainerBindingRuntimeProvider,
    CreateObjectInvalidOptionTypeRuleProvider,
};
use yii2\extensions\phpstan\tests\support\{CreateObjectRuleTestTrait, FixtureMethod};

use function dirname;
use function sprintf;

/**
 * Unit tests for {@see CreateObjectInvalidOptionTypeRule}.
 *
 * {@see CreateObjectInvalidOptionTypeRuleProvider} for test case data providers.
 *
 * @extends RuleTestCase<CreateObjectInvalidOptionTypeRule>
 */
final class CreateObjectInvalidOptionTypeRuleTest extends RuleTestCase
{
    use CreateObjectRuleTestTrait;

    /**
     * Error identifier of the rule.
     */
    private const string IDENTIFIER = 'yii2.createObject.invalidOptionType';

    public static function getAdditionalConfigFiles(): array
    {
        return [dirname(__DIR__) . '/support/extension-create-object-test.neon'];
    }

    public function testAcceptValuesTheClassAssignsDirectlyOrThroughCoercion(): void
    {
        $this->assertNoDiagnostics('CreateObjectValidOptionType.php');
    }

    public function testReportBoundValuesOnlyForCallsThatThrowTypeError(): void
    {
        $errors = $this->fixtureErrors(dirname(__DIR__) . '/data/rule/CreateObjectContainerBinding.php');

        self::assertNotSame(
            [],
            $errors,
            'Fixture must produce diagnostics.',
        );

        foreach ($errors as $error) {
            $method = FixtureMethod::at(CreateObjectContainerBinding::class, $error->getLine());

            [$outcome] = CreateObjectContainerBindingRuntimeProvider::outcome($method);

            self::assertSame(
                TypeError::class,
                $outcome,
                sprintf('Runtime outcome of `%s()` must be a `TypeError`.', $method),
            );
        }
    }

    public function testReportComponentValuesOnlyForCallsThatThrowTypeError(): void
    {
        $errors = $this->fixtureErrors(dirname(__DIR__) . '/data/rule/CreateObjectComponentOption.php');

        self::assertNotSame(
            [],
            $errors,
            'Fixture must produce diagnostics.',
        );

        foreach ($errors as $error) {
            $method = FixtureMethod::at(CreateObjectComponentOption::class, $error->getLine());

            [$outcome] = CreateObjectComponentOptionRuntimeProvider::outcome($method);

            self::assertSame(
                TypeError::class,
                $outcome,
                sprintf('Runtime outcome of `%s()` must be a `TypeError`.', $method),
            );
        }
    }

    public function testReportConstructorArgumentValuesOnlyForCallsThatThrowTypeError(): void
    {
        $errors = $this->fixtureErrors(dirname(__DIR__) . '/data/rule/CreateObjectConstructorArgument.php');

        self::assertNotSame(
            [],
            $errors,
            'Fixture must produce diagnostics.',
        );

        foreach ($errors as $error) {
            $method = FixtureMethod::at(CreateObjectConstructorArgument::class, $error->getLine());

            [$outcome] = CreateObjectConstructorArgumentRuntimeProvider::outcome($method);

            self::assertSame(
                TypeError::class,
                $outcome,
                sprintf('Runtime outcome of `%s()` must be a `TypeError`.', $method),
            );
        }
    }

    #[TestWith(['CreateObjectConstantStandAside.php'])]
    #[TestWith(['CreateObjectConstantUnknownOption.php'])]
    #[TestWith(['CreateObjectConstantValidOption.php'])]
    #[TestWith(['CreateObjectStandAside.php'])]
    #[TestWith(['CreateObjectStaticTarget.php'])]
    #[TestWith(['CreateObjectUnknownOption.php'])]
    #[TestWith(['CreateObjectValidOption.php'])]
    public function testReportNothingOnUnknownOptionFixtures(string $file): void
    {
        $this->assertNoDiagnostics($file);
    }

    /**
     * @phpstan-param class-string $fixture
     * @phpstan-param list<array{string|null, string}> $expected
     */
    #[DataProviderExternal(CreateObjectInvalidOptionTypeRuleProvider::class, 'reportedProvider')]
    public function testReportValuesThePropertyOrSetterNeverAccepts(string $fixture, string $method, array $expected): void
    {
        $this->assertMethodDiagnostics($fixture, $method, $expected);
    }

    public function testStandAsideWhenRejectionIsNotProven(): void
    {
        $this->assertNoDiagnostics('CreateObjectOptionTypeStandAside.php');
    }

    protected function getRule(): Rule
    {
        return new CreateObjectInvalidOptionTypeRule(
            self::createReflectionProvider(),
            self::getContainer()->getByType(ServiceMap::class),
        );
    }
}

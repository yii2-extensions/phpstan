<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use yii\base\UnknownPropertyException;
use yii2\extensions\phpstan\rules\CreateObjectUnknownOptionRule;
use yii2\extensions\phpstan\ServiceMap;
use yii2\extensions\phpstan\tests\data\rule\CreateObjectStaticTarget;
use yii2\extensions\phpstan\tests\provider\CreateObjectComponentOptionRuntimeProvider;

use function dirname;
use function sprintf;
use function str_contains;
use function strlen;
use function strrpos;
use function substr;

/**
 * Unit tests for {@see CreateObjectUnknownOptionRule}.
 *
 * @extends RuleTestCase<CreateObjectUnknownOptionRule>
 */
final class CreateObjectUnknownOptionRuleTest extends RuleTestCase
{
    /**
     * Error identifier of the rule.
     */
    private const string IDENTIFIER = 'yii2.createObject.unknownOption';

    /**
     * Start of the message of `yii\base\UnknownPropertyException` thrown by `__set()`.
     */
    private const string RUNTIME_PREFIX = 'Setting unknown property: ';

    /**
     * Namespace of the configurable target fixtures.
     */
    private const string TARGETS = 'yii2\extensions\phpstan\tests\support\createobject\\';

    public static function getAdditionalConfigFiles(): array
    {
        return [dirname(__DIR__) . '/support/extension-create-object-test.neon'];
    }

    public function testAcceptConstantKnownOptionsTheClassAssigns(): void
    {
        $this->assertDiagnostics('CreateObjectConstantValidOption.php', []);
    }

    public function testAcceptPublicPropertiesSettersInheritedMembersAndUnclaimedOptions(): void
    {
        $this->assertDiagnostics('CreateObjectValidOption.php', []);
    }

    public function testReportComponentOptionsOnlyForCallsThatThrowUnknownPropertyException(): void
    {
        $errors = $this->gatherAnalyserErrors([dirname(__DIR__) . '/data/rule/CreateObjectComponentOption.php']);

        self::assertNotSame(
            [],
            $errors,
            'Fixture must produce diagnostics.',
        );

        foreach ($errors as $error) {
            $method = CreateObjectComponentOptionRuntimeProvider::methodAt($error->getLine());

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

    public function testReportComponentOptionsThatNoBehaviorCanReceive(): void
    {
        $this->assertDiagnostics(
            'CreateObjectComponentOption.php',
            [
                [111, self::message('Yii', 'yii\base\Component', 'colour')],
                [116, self::message('Yii', 'ComponentTarget', 'colour')],
                [149, self::message('Yii', 'ComponentMembersTarget', 'colour')],
                [159, self::message('Yii', 'ComponentEventTarget', 'colour')],
                [178, self::message('Yii', 'ComponentTarget', 'colour')],
                [197, self::message('Yii', 'ComponentTarget', 'colour')],
                [222, self::message('Yii', 'ComponentMembersTarget', 'colour')],
                [230, self::message('Yii', 'ComponentMembersTarget', 'secret')],
                [251, self::message('Yii', 'ComponentMembersTarget', 'internal')],
                [296, self::message('Yii', 'ComponentMembersTarget', 'colour')],
                [297, self::message('Yii', 'ComponentMembersTarget', 'size')],
                [308, self::message('Yii', 'ComponentMembersTarget', 'colour')],
                [315, self::message('Yii', 'ComponentMembersTarget', 'colour')],
                [320, self::message('Yii', 'ComponentMembersTarget', 'As colour')],
                [326, self::message('Yii', 'ComponentMembersTarget', 'On change')],
            ],
        );
    }

    public function testReportConstantKnownUnknownOptionsAtKeyOrCallLine(): void
    {
        $this->assertDiagnostics(
            'CreateObjectConstantUnknownOption.php',
            [
                [21, self::message('Yii', 'Target', 'colour')],
                [22, self::message('Yii', 'Target', 'colour')],
                [26, self::message('Yii', 'Target', 'colour')],
                [31, self::message('Yii', 'Target', 'colour')],
                [32, self::message('Yii', 'Target', 'colour')],
                [38, self::message('Yii', 'Target', 'colour')],
                [44, self::message('Yii', 'Target', 'colour')],
                [51, self::message('Yii', 'Target', 'colour')],
                [64, self::message('Yii', 'Target', 'colour')],
                [70, self::message('Yii', 'Target', 'colour')],
                [76, self::message('Yii', 'Target', 'colour')],
                [82, self::message('Yii', 'Target', 'colour')],
                [91, self::message('Yii', 'Target', 'colour')],
                [98, self::message('Yii', 'Target', 'colour')],
                [104, self::message('Yii', 'Target', 'colour')],
                [111, self::message('Yii', 'FactoryTarget', 'colour')],
                [123, self::message('Yii', 'Target', 'colour')],
                [128, self::message('Yii', 'Target', 'colour')],
                [131, self::message('Yii', 'Target', 'colour')],
                [142, self::message('Yii', 'Target', '1')],
                [151, self::message('Yii', 'Target', 'colour')],
            ],
        );
    }

    public function testReportLateStaticClassOfFinalClassOnlyOutsideLiteralConfiguration(): void
    {
        $this->assertDiagnostics(
            'CreateObjectStaticTarget.php',
            [
                [19, self::message('Yii', CreateObjectStaticTarget::class, 'colour')],
            ],
        );
    }

    public function testReportUnknownOptionsWithIdentifierAndLine(): void
    {
        $this->assertDiagnostics(
            'CreateObjectUnknownOption.php',
            [
                [28, self::message('Yii', 'Target', 'colour')],
                [34, self::message('Yii', 'Target', 'colour')],
                [40, self::message('yii\BaseYii', 'Target', 'colour')],
                [46, self::message(self::TARGETS . 'Factory', 'Target', 'colour')],
                [52, self::message('Yii', 'Target', 'colour')],
                [58, self::message('Yii', 'Target', 'colour')],
                [65, self::message('Yii', 'Target', 'colour')],
                [71, self::message('Yii', 'Target', 'colour')],
                [82, self::message('Yii', 'Target', 'colour')],
                [88, self::message('Yii', 'Target', 'Name')],
                [94, self::message('Yii', 'Target', 'internal')],
                [95, self::message('Yii', 'Target', 'secret')],
                [101, self::message('Yii', 'Target', 'documented')],
                [102, self::message('Yii', 'Target', 'annotated')],
                [108, self::message('Yii', 'ChildTarget', 'secret')],
                [109, self::message('Yii', 'ChildTarget', 'internal')],
                [116, self::message('Yii', 'Target', 'colour')],
                [122, self::message('Yii', 'Target', 'colour')],
                [129, self::message('Yii', 'yii\base\BaseObject', 'on change')],
                [135, self::message('Yii', 'Target', 'colour')],
                [141, self::message('Yii', 'FactoryTarget', 'colour')],
                [148, self::message('Yii', 'Target', 'colour')],
                [155, self::message('Yii', 'Target', 'colour')],
            ],
        );
    }

    public function testStandAsideWhenNativeTypesDoNotProveOneConstantConfiguration(): void
    {
        $this->assertDiagnostics('CreateObjectConstantStandAside.php', []);
    }

    public function testStandAsideWhenTargetOrAssignmentIsNotProven(): void
    {
        $this->assertDiagnostics('CreateObjectStandAside.php', []);
    }

    protected function getRule(): Rule
    {
        return new CreateObjectUnknownOptionRule(
            self::createReflectionProvider(),
            self::getContainer()->getByType(ServiceMap::class),
        );
    }

    /**
     * Asserts the line, identifier and message of every diagnostic of a data file, in report order.
     *
     * @phpstan-param list<array{int, string}> $expected
     */
    private function assertDiagnostics(string $file, array $expected): void
    {
        $actual = [];

        foreach ($this->gatherAnalyserErrors([dirname(__DIR__) . '/data/rule/' . $file]) as $error) {
            $actual[] = [$error->getLine(), $error->getIdentifier(), $error->getMessage()];
        }

        $expectedDiagnostics = [];

        foreach ($expected as [$line, $message]) {
            $expectedDiagnostics[] = [$line, self::IDENTIFIER, $message];
        }

        self::assertSame(
            $expectedDiagnostics,
            $actual,
            'Lines, identifiers and messages must match.',
        );
    }

    /**
     * Returns the expected message for an option of a fixture or fully qualified class.
     */
    private static function message(string $calledClass, string $targetClass, string $option): string
    {
        $target = str_contains($targetClass, '\\') ? $targetClass : self::TARGETS . $targetClass;

        return sprintf(
            '%s::createObject() sets unknown property %s::$%s: the class has no public property or setter with that '
                . 'name.',
            $calledClass,
            $target,
            $option,
        );
    }
}

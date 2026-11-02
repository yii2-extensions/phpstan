<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use yii2\extensions\phpstan\rules\CreateObjectUnknownOptionRule;
use yii2\extensions\phpstan\ServiceMap;

use function dirname;
use function sprintf;
use function str_contains;

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
     * Namespace of the configurable target fixtures.
     */
    private const string TARGETS = 'yii2\extensions\phpstan\tests\support\createobject\\';

    public static function getAdditionalConfigFiles(): array
    {
        return [dirname(__DIR__) . '/support/extension-create-object-test.neon'];
    }

    public function testAcceptPublicPropertiesSettersInheritedMembersAndUnclaimedOptions(): void
    {
        $this->assertDiagnostics('CreateObjectValidOption.php', []);
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

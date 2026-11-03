<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\TestWith;
use TypeError;
use yii2\extensions\phpstan\rules\CreateObjectInvalidOptionTypeRule;
use yii2\extensions\phpstan\ServiceMap;
use yii2\extensions\phpstan\tests\provider\CreateObjectComponentOptionRuntimeProvider;

use function dirname;
use function sprintf;
use function str_contains;

/**
 * Unit tests for {@see CreateObjectInvalidOptionTypeRule}.
 *
 * @extends RuleTestCase<CreateObjectInvalidOptionTypeRule>
 */
final class CreateObjectInvalidOptionTypeRuleTest extends RuleTestCase
{
    /**
     * Error identifier of the rule.
     */
    private const string IDENTIFIER = 'yii2.createObject.invalidOptionType';

    /**
     * Namespace of the configurable target fixtures.
     */
    private const string TARGETS = 'yii2\extensions\phpstan\tests\support\createobject\\';

    public static function getAdditionalConfigFiles(): array
    {
        return [dirname(__DIR__) . '/support/extension-create-object-test.neon'];
    }

    public function testAcceptValuesTheClassAssignsDirectlyOrThroughCoercion(): void
    {
        $this->assertDiagnostics('CreateObjectValidOptionType.php', []);
    }

    public function testReportComponentValuesOfNativeMembersOnly(): void
    {
        $this->assertDiagnostics(
            'CreateObjectComponentOption.php',
            [
                [
                    53,
                    self::property(
                        'ComponentTarget',
                        'name',
                        'array{}',
                        'string',
                    ),
                ],
                [
                    73,
                    self::property(
                        'ComponentMembersTarget',
                        'count',
                        "'abc'",
                        'int',
                    ),
                ],
                [
                    101,
                    self::property(
                        'ComponentBehaviorsTarget',
                        'count',
                        "'abc'",
                        'int',
                    ),
                ],
                [
                    246,
                    self::setter(
                        'ComponentMembersTarget',
                        'guarded',
                        "'abc'",
                        'ComponentMembersTarget::setGuarded()',
                        'int',
                    ),
                ],
                [
                    283,
                    self::property(
                        'ComponentMembersTarget',
                        'count',
                        "'abc'",
                        'int',
                    ),
                ],
                [
                    288,
                    self::setter(
                        'ComponentMembersTarget',
                        'limit',
                        "'abc'",
                        'ComponentMembersTarget::setLimit()',
                        'int',
                    ),
                ],
            ],
        );
    }

    public function testReportComponentValuesOnlyForCallsThatThrowTypeError(): void
    {
        $errors = $this->gatherAnalyserErrors([dirname(__DIR__) . '/data/rule/CreateObjectComponentOption.php']);

        self::assertNotSame(
            [],
            $errors,
            'Fixture must produce diagnostics.',
        );

        foreach ($errors as $error) {
            $method = CreateObjectComponentOptionRuntimeProvider::methodAt($error->getLine());

            [$outcome] = CreateObjectComponentOptionRuntimeProvider::outcome($method);

            self::assertSame(
                TypeError::class,
                $outcome,
                sprintf('Runtime outcome of `%s()` must be a `TypeError`.', $method),
            );
        }
    }

    public function testReportConstantKnownValuesAtKeyOrCallLine(): void
    {
        $this->assertDiagnostics(
            'CreateObjectConstantInvalidOptionType.php',
            [
                [
                    20,
                    self::property(
                        'TypedTarget',
                        'count',
                        'array{}',
                        'int',
                    ),
                ],
                [
                    25,
                    self::property(
                        'TypedTarget',
                        'count',
                        "'abc'",
                        'int',
                    ),
                ],
                [
                    32,
                    self::property(
                        'TypedTarget',
                        'count',
                        "'abc'",
                        'int',
                    ),
                ],
                [
                    37,
                    self::property(
                        'TypedTarget',
                        'count',
                        "'abc'",
                        'int',
                    ),
                ],
                [
                    44,
                    self::setter(
                        'TypedTarget',
                        'tags',
                        "'abc'",
                        'TypedTarget::setTags()',
                        'array',
                    ),
                ],
            ],
        );
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
        $this->assertDiagnostics($file, []);
    }

    public function testReportValuesThePropertyOrSetterNeverAccepts(): void
    {
        $this->assertDiagnostics(
            'CreateObjectInvalidOptionType.php',
            [
                [
                    26,
                    self::property(
                        'TypedTarget',
                        'enabled',
                        'array{}',
                        'bool',
                    ),
                ],
                [
                    31,
                    self::property(
                        'TypedTarget',
                        'key',
                        'array{}',
                        'int|string',
                    ),
                ],
                [
                    36,
                    self::property(
                        'TypedTarget',
                        'count',
                        'array{}',
                        'int',
                    ),
                ],
                [
                    41,
                    self::setter(
                        'TypedTarget',
                        'label',
                        'array{}',
                        'TypedTarget::setLabel()',
                        'string|null',
                    ),
                ],
                [
                    46,
                    self::property(
                        'TypedTarget',
                        'both',
                        'array{}',
                        'int',
                    ),
                ],
                [
                    51,
                    self::property(
                        'TypedTarget',
                        'title',
                        'array{}',
                        'string',
                    ),
                ],
                [
                    56,
                    self::setter(
                        'TypedTarget',
                        'text',
                        'array{}',
                        'TypedTarget::setText()',
                        'string',
                    ),
                ],
                [
                    61,
                    self::property(
                        'TypedTarget',
                        'count',
                        "'abc'",
                        'int',
                    ),
                ],
                [
                    66,
                    self::property(
                        'TypedTarget',
                        'count',
                        self::TARGETS . 'TypedSuit::Hearts',
                        'int',
                    ),
                ],
                [
                    71,
                    self::property(
                        'TypedTarget',
                        'count',
                        self::TARGETS . 'TypedSuit::Hearts',
                        'int',
                    ),
                ],
                [
                    76,
                    self::property(
                        'TypedTarget',
                        'contract',
                        self::TARGETS . 'TypedValue',
                        self::TARGETS . 'TypedContract|null',
                    ),
                ],
                [
                    81,
                    self::property(
                        'TypedChildTarget',
                        'count',
                        "'abc'",
                        'int',
                    ),
                ],
                [
                    86,
                    self::property(
                        'TypedTarget',
                        'count',
                        "'abc'",
                        'int',
                    ),
                ],
                [
                    91,
                    self::property(
                        'TypedTarget',
                        'ratio',
                        "'1abc'",
                        'float',
                    ),
                ],
                [
                    99,
                    self::property(
                        'TypedTarget',
                        'count',
                        "'abc'",
                        'int',
                    ),
                ],
                [
                    107,
                    self::property(
                        'TypedTarget',
                        'count',
                        "'abc'",
                        'int',
                        'yii\BaseYii',
                    ),
                ],
                [
                    112,
                    self::property(
                        'TypedTarget',
                        'count',
                        "'abc'",
                        'int',
                    ),
                ],
                [
                    117,
                    self::property(
                        'TypedTarget',
                        'limit',
                        "'abc'",
                        'int|null',
                    ),
                ],
                [
                    122,
                    self::property(
                        'TypedTarget',
                        'count',
                        'null',
                        'int',
                    ),
                ],
                [
                    127,
                    self::property(
                        'TypedTarget',
                        'title',
                        'null',
                        'string',
                    ),
                ],
                [
                    132,
                    self::setter(
                        'TypedTarget',
                        'flag',
                        self::TARGETS . 'TypedPlain',
                        'TypedTarget::setFlag()',
                        'bool',
                    ),
                ],
                [
                    137,
                    self::property(
                        'TypedTarget',
                        'value',
                        'stdClass',
                        self::TARGETS . 'TypedValue|null',
                    ),
                ],
                [
                    142,
                    self::property(
                        'TypedTarget',
                        'count',
                        self::TARGETS . 'TypedPlain',
                        'int',
                    ),
                ],
                [
                    147,
                    self::property(
                        'TypedChildTarget',
                        'weight',
                        "'abc'",
                        'float',
                    ),
                ],
                [
                    152,
                    self::setter(
                        'TypedTarget',
                        'guardedCount',
                        "'abc'",
                        'TypedTarget::setGuardedCount()',
                        'array',
                    ),
                ],
                [
                    157,
                    self::setter(
                        'TypedTarget',
                        'shared',
                        "'abc'",
                        'TypedTarget::setShared()',
                        'array',
                    ),
                ],
                [
                    162,
                    self::setter(
                        'TypedTarget',
                        'defaultCount',
                        "'abc'",
                        'TypedTarget::setDefaultCount()',
                        'int',
                    ),
                ],
                [
                    167,
                    self::property(
                        'TypedTarget',
                        'items',
                        "'abc'",
                        'array',
                    ),
                ],
                [
                    172,
                    self::setter(
                        'TypedTarget',
                        'tags',
                        "'abc'",
                        'TypedTarget::setTags()',
                        'array',
                    ),
                ],
                [
                    177,
                    self::setter(
                        'TypedTarget',
                        'factory',
                        "'strlen'",
                        'TypedTarget::setFactory()',
                        'Closure',
                    ),
                ],
                [
                    182,
                    self::property(
                        'TypedTarget',
                        'suit',
                        "'H'",
                        self::TARGETS . 'TypedSuit',
                    ),
                ],
                [
                    187,
                    self::setter(
                        'TypedTarget',
                        'limitTo',
                        "'abc'",
                        'TypedTarget::setLimitTo()',
                        'int',
                    ),
                ],
                [
                    192,
                    self::property(
                        'TypedTarget',
                        'rows',
                        "'abc'",
                        'iterable',
                    ),
                ],
                [
                    197,
                    self::setter(
                        'TypedChildTarget',
                        'traitLevel',
                        "'abc'",
                        'TypedTarget::setTraitLevel()',
                        'int',
                    ),
                ],
                [
                    202,
                    self::setter(
                        'TypedTarget',
                        'traitLevel',
                        "'abc'",
                        'TypedTarget::setTraitLevel()',
                        'int',
                    ),
                ],
                [
                    207,
                    self::setter(
                        'TypedTarget',
                        'pair',
                        "'abc'",
                        'TypedTarget::setPair()',
                        'int',
                    ),
                ],
                [
                    212,
                    self::setter(
                        'TypedTarget',
                        'values',
                        "'abc'",
                        'TypedTarget::setValues()',
                        'int',
                    ),
                ],
                [
                    217,
                    self::setter(
                        'TypedChildTarget',
                        'limitTo',
                        "'abc'",
                        'TypedChildTarget::setLimitTo()',
                        'array|int',
                    ),
                ],
                [
                    222,
                    self::property(
                        'TypedTarget',
                        'count',
                        "''",
                        'int',
                    ),
                ],
                [
                    222,
                    self::property(
                        'TypedTarget',
                        'title',
                        'array{}',
                        'string',
                    ),
                ],
            ],
        );
    }

    public function testStandAsideWhenRejectionIsNotProven(): void
    {
        $this->assertDiagnostics('CreateObjectOptionTypeStandAside.php', []);
    }

    protected function getRule(): Rule
    {
        return new CreateObjectInvalidOptionTypeRule(
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
            $actual[] = [
                $error->getLine(),
                $error->getIdentifier(),
                $error->getMessage(),
            ];
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
     * Returns the expected message for an option whose value Yii passes to a member of a fixture class.
     */
    private static function message(
        string $calledClass,
        string $targetClass,
        string $option,
        string $value,
        string $member,
        string $expected,
    ): string {
        return sprintf(
            '%s::createObject() sets %s::$%s to %s, but %s accepts only %s, even with type coercion.',
            $calledClass,
            self::TARGETS . $targetClass,
            $option,
            $value,
            $member,
            $expected,
        );
    }

    /**
     * Returns the expected message for an option that Yii assigns to a public property.
     */
    private static function property(
        string $targetClass,
        string $option,
        string $value,
        string $expected,
        string $calledClass = 'Yii',
    ): string {
        return self::message($calledClass, $targetClass, $option, $value, 'the property', $expected);
    }

    /**
     * Returns the expected message for an option that Yii passes to a setter of a fixture class.
     */
    private static function setter(
        string $targetClass,
        string $option,
        string $value,
        string $setter,
        string $expected,
    ): string {
        $member = str_contains($setter, '\\') ? $setter : self::TARGETS . $setter;

        return self::message('Yii', $targetClass, $option, $value, $member, $expected);
    }
}

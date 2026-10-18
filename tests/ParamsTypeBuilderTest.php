<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests;

use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\Constant\{ConstantIntegerType, ConstantStringType};
use PHPStan\Type\{IntegerType, VerbosityLevel};
use PHPUnit\Framework\Attributes\DataProviderExternal;
use yii2\extensions\phpstan\ParamsTypeBuilder;
use yii2\extensions\phpstan\tests\provider\ParamsTypeBuilderProvider;

use function array_fill_keys;
use function array_keys;
use function array_map;
use function range;

/**
 * Unit tests for {@see ParamsTypeBuilder} array shape construction from configured params.
 *
 * {@see ParamsTypeBuilderProvider} for test case data providers.
 */
final class ParamsTypeBuilderTest extends PHPStanTestCase
{
    /**
     * @param array<array-key, mixed> $params
     */
    #[DataProviderExternal(ParamsTypeBuilderProvider::class, 'appendProvider')]
    public function testAppendUsesRuntimeIndex(array $params): void
    {
        $type = ParamsTypeBuilder::build($params)->setOffsetValueType(null, new IntegerType());

        $params[] = 42;
        $constantArrays = $type->getConstantArrays();

        self::assertCount(
            1,
            $constantArrays,
            'The appended shape must remain a single constant array.',
        );

        foreach ($constantArrays as $constantArray) {
            self::assertSame(
                array_keys($params),
                array_map(
                    static fn(ConstantIntegerType|ConstantStringType $key): int|string => $key->getValue(),
                    $constantArray->getKeyTypes(),
                ),
                'Appending to the inferred shape must use the same keys as PHP.',
            );
        }
    }

    public function testBuildKeepsShapeBeyondArrayCountLimit(): void
    {
        $params = array_fill_keys(range(1, 300), 'value');

        $type = ParamsTypeBuilder::build($params);

        self::assertTrue(
            $type->isConstantArray()->yes(),
            'Type must stay an array shape.',
        );
        self::assertSame(
            '300',
            $type->getArraySize()->describe(VerbosityLevel::precise()),
            'Shape must keep every key.',
        );
    }

    /**
     * @param array<array-key, mixed> $params
     */
    #[DataProviderExternal(ParamsTypeBuilderProvider::class, 'paramsProvider')]
    public function testBuildReturnsExpectedShape(array $params, string $expected): void
    {
        self::assertSame(
            $expected,
            ParamsTypeBuilder::build($params)->describe(VerbosityLevel::precise()),
            'Shape must match the configured values.',
        );
    }
}

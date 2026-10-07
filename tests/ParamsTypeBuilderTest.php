<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests;

use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\VerbosityLevel;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use yii2\extensions\phpstan\ParamsTypeBuilder;
use yii2\extensions\phpstan\tests\provider\ParamsTypeBuilderProvider;

use function array_fill_keys;
use function range;

/**
 * Unit tests for {@see ParamsTypeBuilder} array shape construction from configured params.
 *
 * {@see ParamsTypeBuilderProvider} for test case data providers.
 */
final class ParamsTypeBuilderTest extends PHPStanTestCase
{
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

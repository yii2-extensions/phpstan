<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\rules;

use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\{ArrayType, IntegerType, MixedType, StringType, Type, TypeCombinator, VerbosityLevel};
use PHPStan\Type\Constant\{ConstantArrayTypeBuilder, ConstantIntegerType, ConstantStringType};
use yii2\extensions\phpstan\rules\ObjectConfigurationReader;

use function array_map;

/**
 * Unit tests for {@see ObjectConfigurationReader::optionsFromType()}.
 */
final class ObjectConfigurationReaderTest extends PHPStanTestCase
{
    public function testReadEveryStringKeyOfOneSealedConstantArrayAtTheGivenLine(): void
    {
        $options = ObjectConfigurationReader::optionsFromType(
            self::constantArray(['class' => new ConstantStringType('Probe\Target'), 'count' => new IntegerType()]),
            12,
        );

        self::assertNotNull(
            $options,
            'Sealed constant array must be read.',
        );
        self::assertSame(
            [['class', 12, "'Probe\\\\Target'"], ['count', 12, 'int']],
            array_map(
                static fn(array $option): array => [
                    $option[0],
                    $option[1],
                    $option[2]->describe(VerbosityLevel::precise()),
                ],
                $options,
            ),
            'Names, lines and value types must match in key order.',
        );
    }

    public function testRefuseTypesThatDoNotProveOneConfiguration(): void
    {
        $optional = ConstantArrayTypeBuilder::createEmpty();

        $optional->setOffsetValueType(new ConstantStringType('count'), new IntegerType(), true);

        $integerKey = ConstantArrayTypeBuilder::createEmpty();

        $integerKey->setOffsetValueType(new ConstantIntegerType(0), new IntegerType());

        $refused = [
            'optional key' => $optional->getArray(),
            'integer key' => $integerKey->getArray(),
            'union of two arrays' => TypeCombinator::union(
                self::constantArray(['count' => new IntegerType()]),
                self::constantArray(['name' => new StringType()]),
            ),
            'general array' => new ArrayType(new StringType(), new MixedType()),
            'string' => new ConstantStringType('Probe\Target'),
        ];

        foreach ($refused as $case => $type) {
            self::assertNull(
                ObjectConfigurationReader::optionsFromType($type, 1),
                "Case `{$case}` must be refused.",
            );
        }
    }

    /**
     * @param array<string, Type> $values
     */
    private static function constantArray(array $values): Type
    {
        $builder = ConstantArrayTypeBuilder::createEmpty();

        foreach ($values as $key => $value) {
            $builder->setOffsetValueType(new ConstantStringType($key), $value);
        }

        return $builder->getArray();
    }
}

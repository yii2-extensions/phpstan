<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

/**
 * Data provider for {@see \yii2\extensions\phpstan\tests\ValidatorInvalidDefinitionResultCacheIntegrationTest} test
 * cases.
 */
final class ValidatorInvalidDefinitionResultCacheIntegrationProvider
{
    /**
     * @phpstan-return iterable<string, array{'BaseModel'|'Types', string}>
     */
    public static function changeProvider(): iterable
    {
        yield 'grandparent adds createValidators()' => [
            'BaseModel',
            "abstract class BaseModel extends \\yii\\base\\Model\n{\n    /**\n"
                . "     * @return \\ArrayObject<int, \\yii\\validators\\Validator>\n     */\n"
                . "    public function createValidators(): \\ArrayObject\n    {\n        return new \\ArrayObject();\n"
                . "    }\n}",
        ];
        yield 'grandparent adds getValidators()' => [
            'BaseModel',
            "abstract class BaseModel extends \\yii\\base\\Model\n{\n    /**\n"
                . "     * @return \\ArrayObject<int, \\yii\\validators\\Validator>\n     */\n"
                . "    public function getValidators(): \\ArrayObject\n    {\n        return new \\ArrayObject();\n"
                . "    }\n}",
        ];
        yield 'validator type becomes a Closure' => [
            'Types',
            "final class Types\n{\n    public static function definition(): \\Closure\n    {\n"
                . "        return static fn(): bool => true;\n    }\n}",
        ];
    }
}

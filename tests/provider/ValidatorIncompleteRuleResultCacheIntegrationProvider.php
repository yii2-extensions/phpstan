<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

/**
 * Data provider for {@see \yii2\extensions\phpstan\tests\ValidatorIncompleteRuleResultCacheIntegrationTest} test
 * cases.
 */
final class ValidatorIncompleteRuleResultCacheIntegrationProvider
{
    /**
     * @phpstan-return iterable<string, array{string}>
     */
    public static function overrideProvider(): iterable
    {
        yield 'createValidators()' => [
            "/**\n     * @return \\ArrayObject<int, \\yii\\validators\\Validator>\n     */\n"
                . "    public function createValidators(): \\ArrayObject\n    {\n        return new \\ArrayObject();\n    }",
        ];
        yield 'getValidators()' => [
            "/**\n     * @return \\ArrayObject<int, \\yii\\validators\\Validator>\n     */\n"
                . "    public function getValidators(): \\ArrayObject\n    {\n        return new \\ArrayObject();\n    }",
        ];
    }
}

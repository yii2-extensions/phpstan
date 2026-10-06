<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

/**
 * Data provider for {@see \yii2\extensions\phpstan\tests\ServiceMapServiceTest} test cases.
 */
final class ServiceMapServiceProvider
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function unresolvableConfigProvider(): iterable
    {
        yield 'definition array without class' => [
            'definitions-unsupported-type-array-invalid.php',
            'unsupported-array-invalid',
        ];
        yield 'definition closure without return type' => [
            'definitions-closure-not-return-type.php',
            'closure-not-return-type',
        ];
        yield 'definition empty array' => [
            'definitions-unsupported-empty-array.php',
            'unsupported-empty-array',
        ];
        yield 'singleton array without class' => [
            'singletons-unsupported-type-array-invalid.php',
            'unsupported-array-invalid',
        ];
        yield 'singleton closure without return type' => [
            'singletons-closure-not-return-type.php',
            'closure-not-return-type',
        ];
        yield 'singleton empty array' => [
            'singletons-unsupported-empty-array.php',
            'unsupported-empty-array',
        ];
    }
}

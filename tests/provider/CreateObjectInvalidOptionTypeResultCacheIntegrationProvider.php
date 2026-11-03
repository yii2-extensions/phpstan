<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

/**
 * Data provider for {@see \yii2\extensions\phpstan\tests\CreateObjectInvalidOptionTypeResultCacheIntegrationTest}
 * test cases.
 *
 * Provides the declaration that changes, its native types before and after the change, and the number of files the
 * changed run reanalyses.
 */
final class CreateObjectInvalidOptionTypeResultCacheIntegrationProvider
{
    /**
     * @phpstan-return iterable<string, array{string, array{string, string, string}, array{string, string, string}, int}>
     */
    public static function declarationProvider(): iterable
    {
        yield 'property type accepts the value' => [
            'src/Target.php',
            ['int', 'int', 'final class'],
            ['string', 'int', 'final class'],
            4,
        ];
        yield 'property type rejects the value' => [
            'src/Target.php',
            ['string', 'int', 'final class'],
            ['int', 'int', 'final class'],
            4,
        ];
        yield 'inherited setter type accepts the value' => [
            'src/Base.php',
            ['string', 'int', 'final class'],
            ['string', 'string', 'final class'],
            5,
        ];
        yield 'property class becomes an interface' => [
            'src/Shape.php',
            ['string', 'int', 'final class'],
            ['string', 'int', 'interface'],
            5,
        ];
    }
}

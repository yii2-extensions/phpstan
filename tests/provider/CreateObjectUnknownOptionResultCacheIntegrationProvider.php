<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

/**
 * Data provider for {@see \yii2\extensions\phpstan\tests\CreateObjectUnknownOptionResultCacheIntegrationTest} test
 * cases.
 *
 * Provides the container ID bound before and after the change: the created class `Probe\Target` or an unrelated ID.
 */
final class CreateObjectUnknownOptionResultCacheIntegrationProvider
{
    /**
     * @phpstan-return iterable<string, array{string, string}>
     */
    public static function bindingProvider(): iterable
    {
        yield 'binding added' => ['Probe\Placeholder', 'Probe\Target'];
        yield 'binding removed' => ['Probe\Target', 'Probe\Placeholder'];
    }
}

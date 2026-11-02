<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

/**
 * Data provider for {@see \yii2\extensions\phpstan\tests\BehaviorResultCacheIntegrationTest} test cases.
 *
 * Provides the directories of the behavior method and property readers, so that each one is analysed first in turn.
 */
final class BehaviorResultCacheIntegrationProvider
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function readerOrderProvider(): iterable
    {
        yield 'method reader first' => [
            'early',
            'late',
        ];
        yield 'property reader first' => [
            'late',
            'early',
        ];
    }
}

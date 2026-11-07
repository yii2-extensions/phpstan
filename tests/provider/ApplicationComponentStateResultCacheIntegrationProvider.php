<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

/**
 * Data provider for {@see \yii2\extensions\phpstan\tests\ApplicationComponentStateResultCacheIntegrationTest} test
 * cases.
 *
 * Provides the initial and changed definitions of the non-core `changing` component, the diagnostics of its locator
 * and property readers in each state, and the files the unchanged warm run after the change may reanalyse: none, or
 * for a missing component the two application property readers, whose class dependency on the configured application
 * PHPStan 2.3.4 hashes with a file path that the autoloader registered by Yii 2.0.55's `Yii.php` spells differently.
 */
final class ApplicationComponentStateResultCacheIntegrationProvider
{
    /**
     * @phpstan-return iterable<
     *   string,
     *   array{string, list<string>, list<array{string, int, string, string|null}>, list<array{string, int, string, string|null}>, list<int>}
     * >
     */
    public static function definitionChangeProvider(): iterable
    {
        yield 'class definition removed' => [
            "    'changing' => ['class' => 'yii\\\\caching\\\\FileCache'],",
            [],
            [
                ['src/ChangingLocatorConsumer.php', 11, 'phpstan.dumpType', 'yii\caching\FileCache'],
                ['src/ChangingLocatorConsumer.php', 12, 'phpstan.dumpType', 'string'],
                ['src/ChangingPropertyConsumer.php', 11, 'phpstan.dumpType', 'yii\caching\FileCache'],
                ['src/ChangingPropertyConsumer.php', 12, 'phpstan.dumpType', 'string'],
            ],
            [
                ['src/ChangingLocatorConsumer.php', 11, 'phpstan.dumpType', 'object'],
                ['src/ChangingLocatorConsumer.php', 12, 'phpstan.dumpType', '*ERROR*'],
                ['src/ChangingLocatorConsumer.php', 12, 'property.notFound', null],
                ['src/ChangingPropertyConsumer.php', 11, 'phpstan.dumpType', '*ERROR*'],
                ['src/ChangingPropertyConsumer.php', 11, 'property.notFound', null],
                ['src/ChangingPropertyConsumer.php', 12, 'phpstan.dumpType', 'mixed'],
                ['src/ChangingPropertyConsumer.php', 12, 'property.nonObject', null],
                ['src/ChangingPropertyConsumer.php', 12, 'property.notFound', null],
            ],
            [0, 2],
        ];
        yield 'unresolved definition gains class' => [
            "    'changing' => static fn() => new \\yii\\caching\\FileCache(),",
            ["    'changing' => ['class' => 'yii\\\\caching\\\\FileCache'],"],
            [
                ['src/ChangingLocatorConsumer.php', 11, 'phpstan.dumpType', 'object'],
                ['src/ChangingLocatorConsumer.php', 12, 'phpstan.dumpType', '*ERROR*'],
                ['src/ChangingLocatorConsumer.php', 12, 'property.notFound', null],
                ['src/ChangingPropertyConsumer.php', 11, 'phpstan.dumpType', 'object'],
                ['src/ChangingPropertyConsumer.php', 12, 'phpstan.dumpType', '*ERROR*'],
                ['src/ChangingPropertyConsumer.php', 12, 'property.notFound', null],
            ],
            [
                ['src/ChangingLocatorConsumer.php', 11, 'phpstan.dumpType', 'yii\caching\FileCache'],
                ['src/ChangingLocatorConsumer.php', 12, 'phpstan.dumpType', 'string'],
                ['src/ChangingPropertyConsumer.php', 11, 'phpstan.dumpType', 'yii\caching\FileCache'],
                ['src/ChangingPropertyConsumer.php', 12, 'phpstan.dumpType', 'string'],
            ],
            [0],
        ];
    }
}

<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

/**
 * Data provider for {@see \yii2\extensions\phpstan\tests\ApplicationComponentStateResultCacheIntegrationTest} test
 * cases.
 */
final class ApplicationComponentStateResultCacheIntegrationProvider
{
    /**
     * @phpstan-return iterable<string, array{string, list<array{string, int, string, string|null}>}>
     */
    public static function aliasTargetChangeProvider(): iterable
    {
        yield 'alias target changes class' => [
            "    'cache.target' => ['class' => 'yii\\\\caching\\\\ArrayCache'],",
            [
                ['src/ChangingLocatorConsumer.php', 11, 'phpstan.dumpType', 'yii\caching\ArrayCache'],
                ['src/ChangingLocatorConsumer.php', 12, 'phpstan.dumpType', '*ERROR*'],
                ['src/ChangingLocatorConsumer.php', 12, 'property.notFound', null],
                ['src/ChangingPropertyConsumer.php', 11, 'phpstan.dumpType', 'yii\caching\ArrayCache'],
                ['src/ChangingPropertyConsumer.php', 12, 'phpstan.dumpType', '*ERROR*'],
                ['src/ChangingPropertyConsumer.php', 12, 'property.notFound', null],
            ],
        ];
        yield 'alias target becomes unresolved' => [
            "    'cache.target' => static fn() => new \\yii\\caching\\FileCache(),",
            [
                ['src/ChangingLocatorConsumer.php', 11, 'phpstan.dumpType', 'object'],
                ['src/ChangingLocatorConsumer.php', 12, 'phpstan.dumpType', '*ERROR*'],
                ['src/ChangingLocatorConsumer.php', 12, 'property.notFound', null],
                ['src/ChangingPropertyConsumer.php', 11, 'phpstan.dumpType', 'object'],
                ['src/ChangingPropertyConsumer.php', 12, 'phpstan.dumpType', '*ERROR*'],
                ['src/ChangingPropertyConsumer.php', 12, 'property.notFound', null],
            ],
        ];
    }

    /**
     * @phpstan-return iterable<
     *   string,
     *   array{
     *     string,
     *     string,
     *     list<array{string, int, string, string|null}>,
     *     list<array{string, int, string, string|null}>
     *   }
     * >
     */
    public static function classlessCoreDefinitionChangeProvider(): iterable
    {
        yield 'classless core definition gains class' => [
            "    'request' => ['cookieValidationKey' => 'probe'],",
            "    'request' => ['class' => 'yii\\\\web\\\\Request', 'cookieValidationKey' => 'probe'],",
            [
                ['src/RequestLocatorConsumer.php', 11, 'phpstan.dumpType', 'object'],
                ['src/RequestLocatorConsumer.php', 12, 'phpstan.dumpType', '*ERROR*'],
                ['src/RequestLocatorConsumer.php', 12, 'property.notFound', null],
                ['src/RequestPropertyConsumer.php', 11, 'phpstan.dumpType', 'yii\web\Request'],
                ['src/RequestPropertyConsumer.php', 12, 'phpstan.dumpType', 'string'],
            ],
            [
                ['src/RequestLocatorConsumer.php', 11, 'phpstan.dumpType', 'yii\web\Request'],
                ['src/RequestLocatorConsumer.php', 12, 'phpstan.dumpType', 'string'],
                ['src/RequestPropertyConsumer.php', 11, 'phpstan.dumpType', 'yii\web\Request'],
                ['src/RequestPropertyConsumer.php', 12, 'phpstan.dumpType', 'string'],
            ],
        ];
        yield 'core class definition loses class' => [
            "    'request' => ['class' => 'yii\\\\web\\\\Request', 'cookieValidationKey' => 'probe'],",
            "    'request' => ['cookieValidationKey' => 'probe'],",
            [
                ['src/RequestLocatorConsumer.php', 11, 'phpstan.dumpType', 'yii\web\Request'],
                ['src/RequestLocatorConsumer.php', 12, 'phpstan.dumpType', 'string'],
                ['src/RequestPropertyConsumer.php', 11, 'phpstan.dumpType', 'yii\web\Request'],
                ['src/RequestPropertyConsumer.php', 12, 'phpstan.dumpType', 'string'],
            ],
            [
                ['src/RequestLocatorConsumer.php', 11, 'phpstan.dumpType', 'object'],
                ['src/RequestLocatorConsumer.php', 12, 'phpstan.dumpType', '*ERROR*'],
                ['src/RequestLocatorConsumer.php', 12, 'property.notFound', null],
                ['src/RequestPropertyConsumer.php', 11, 'phpstan.dumpType', 'yii\web\Request'],
                ['src/RequestPropertyConsumer.php', 12, 'phpstan.dumpType', 'string'],
            ],
        ];
    }

    /**
     * @phpstan-return iterable<
     *   string,
     *   array{
     *     string,
     *     list<string>,
     *     list<array{string, int, string, string|null}>,
     *     list<array{string, int, string, string|null}>,
     *     list<int>
     *   }
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
        yield 'untyped factory gains return type' => [
            "    'changing' => static fn() => new \\yii\\caching\\FileCache(),",
            ["    'changing' => static fn(): \\yii\\caching\\FileCache => new \\yii\\caching\\FileCache(),"],
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
        yield 'typed factory return type changes class' => [
            "    'changing' => static fn(): \\yii\\caching\\FileCache => new \\yii\\caching\\FileCache(),",
            ["    'changing' => static fn(): \\yii\\caching\\ArrayCache => new \\yii\\caching\\ArrayCache(),"],
            [
                ['src/ChangingLocatorConsumer.php', 11, 'phpstan.dumpType', 'yii\caching\FileCache'],
                ['src/ChangingLocatorConsumer.php', 12, 'phpstan.dumpType', 'string'],
                ['src/ChangingPropertyConsumer.php', 11, 'phpstan.dumpType', 'yii\caching\FileCache'],
                ['src/ChangingPropertyConsumer.php', 12, 'phpstan.dumpType', 'string'],
            ],
            [
                ['src/ChangingLocatorConsumer.php', 11, 'phpstan.dumpType', 'yii\caching\ArrayCache'],
                ['src/ChangingLocatorConsumer.php', 12, 'phpstan.dumpType', '*ERROR*'],
                ['src/ChangingLocatorConsumer.php', 12, 'property.notFound', null],
                ['src/ChangingPropertyConsumer.php', 11, 'phpstan.dumpType', 'yii\caching\ArrayCache'],
                ['src/ChangingPropertyConsumer.php', 12, 'phpstan.dumpType', '*ERROR*'],
                ['src/ChangingPropertyConsumer.php', 12, 'property.notFound', null],
            ],
            [0],
        ];
    }
}

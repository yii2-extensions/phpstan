<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;
use yii\caching\ArrayCache;
use yii2\extensions\phpstan\ServiceMap;
use yii2\extensions\phpstan\tests\provider\ServiceMapConstructionProvider;

use const DIRECTORY_SEPARATOR;

/**
 * Unit tests for {@see ServiceMap::getConstructedClassById()}.
 *
 * {@see ServiceMapConstructionProvider} for test case data providers.
 */
final class ServiceMapConstructionTest extends TestCase
{
    /**
     * Base path for configuration files used in tests.
     */
    private const string BASE_PATH = __DIR__ . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR;

    public function testFollowDefinitionThatReplacesEarlierSingleton(): void
    {
        $serviceMap = new ServiceMap(self::BASE_PATH . 'container-singletons-first-config.php');

        self::assertSame(
            ArrayCache::class,
            $serviceMap->getConstructedClassById('definitionClassOverride'),
            'Later definition must replace the singleton.',
        );
        self::assertNull(
            $serviceMap->getConstructedClassById('definitionClosureOverride'),
            'Later factory must replace the singleton.',
        );
    }

    public function testReturnIdWhenNoConfigurationIsGiven(): void
    {
        self::assertSame(
            'app\Feed',
            (new ServiceMap())->getConstructedClassById('\app\Feed'),
            'Unbound ID must be built itself.',
        );
    }

    #[DataProviderExternal(ServiceMapConstructionProvider::class, 'constructionProvider')]
    public function testReturnTheClassYiiBuildsAndConfigures(string $id, string|null $expected): void
    {
        self::assertSame(
            $expected,
            (new ServiceMap(self::BASE_PATH . 'construction-config.php'))->getConstructedClassById($id),
            'Constructed class must match the container chain.',
        );
    }
}

<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;
use yii2\extensions\phpstan\ServiceMap;
use yii2\extensions\phpstan\tests\provider\ServiceMapDefinitionProvider;

/**
 * Unit tests for {@see ServiceMap} class resolution of every component and container definition form Yii accepts.
 *
 * {@see ServiceMapDefinitionProvider} for test case data providers.
 */
final class ServiceMapDefinitionTest extends TestCase
{
    /**
     * Configuration file declaring one component and one service per definition form.
     */
    private const CONFIG_PATH = __DIR__ . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR
        . 'definition-forms-config.php';

    /**
     * Configuration file listing `container.singletons` before `container.definitions` with overlapping IDs.
     */
    private const SINGLETONS_FIRST_CONFIG_PATH = __DIR__ . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR
        . 'container-singletons-first-config.php';

    #[DataProviderExternal(ServiceMapDefinitionProvider::class, 'componentWithoutClassProvider')]
    public function testFlagComponentDefinedWithoutClass(string $id, bool $expectedWithoutClass): void
    {
        $serviceMap = new ServiceMap(self::CONFIG_PATH);

        self::assertSame(
            $expectedWithoutClass,
            $serviceMap->isComponentWithoutClass($id),
            'Only arrays without a class key must be flagged.',
        );
    }

    #[DataProviderExternal(ServiceMapDefinitionProvider::class, 'componentProvider')]
    public function testFlagComponentDefinedWithUnknownClass(string $id, string|null $expectedClass): void
    {
        $serviceMap = new ServiceMap(self::CONFIG_PATH);

        self::assertSame(
            $expectedClass === null && $id !== 'null',
            $serviceMap->isUnresolvedComponent($id),
            'Only defined components without a class must be flagged.',
        );
    }

    #[DataProviderExternal(ServiceMapDefinitionProvider::class, 'unresolvedServiceProvider')]
    public function testFlagServiceDefinedWithUnknownClass(string $id, bool $expectedUnresolved): void
    {
        $serviceMap = new ServiceMap(self::CONFIG_PATH);

        self::assertSame(
            $expectedUnresolved,
            $serviceMap->isUnresolvedService($id),
            'Only defined services without a class must be flagged.',
        );
    }

    public function testNotFlagComponentAbsentFromConfig(): void
    {
        $serviceMap = new ServiceMap(self::CONFIG_PATH);

        self::assertFalse(
            $serviceMap->isUnresolvedComponent('absent'),
            'Absent component must not be flagged.',
        );
    }

    #[DataProviderExternal(ServiceMapDefinitionProvider::class, 'componentProvider')]
    public function testResolveComponentClass(string $id, string|null $expectedClass): void
    {
        $serviceMap = new ServiceMap(self::CONFIG_PATH);

        self::assertSame(
            $expectedClass,
            $serviceMap->getComponentClassById($id),
            'Component class must match.',
        );
    }

    #[DataProviderExternal(ServiceMapDefinitionProvider::class, 'serviceProvider')]
    public function testResolveServiceClass(string $id, string|null $expectedClass): void
    {
        $serviceMap = new ServiceMap(self::CONFIG_PATH);

        self::assertSame(
            $expectedClass,
            $serviceMap->getServiceById($id),
            'Service class must match.',
        );
    }

    #[DataProviderExternal(ServiceMapDefinitionProvider::class, 'configOrderServiceProvider')]
    public function testResolveServiceClassFromSubsectionListedLast(string $id, string|null $expectedClass): void
    {
        $serviceMap = new ServiceMap(self::SINGLETONS_FIRST_CONFIG_PATH);

        self::assertSame(
            $expectedClass,
            $serviceMap->getServiceById($id),
            'Later `definitions` entry must replace the singleton.',
        );
    }

    public function testReturnComponentDefinitionWithoutClassKeys(): void
    {
        $serviceMap = new ServiceMap(self::CONFIG_PATH);

        self::assertSame(
            ['title' => 'Home'],
            $serviceMap->getComponentDefinitionById('arrayDunderClass'),
            'Definition must exclude the `__class` key.',
        );
        self::assertSame(
            [],
            $serviceMap->getComponentDefinitionById('closureClass'),
            'Non-array definitions must not be kept.',
        );
        self::assertSame(
            [],
            $serviceMap->getComponentDefinitionById('containerArrayAliasUnresolvable'),
            'Definition of an unresolvable class must not be kept.',
        );
    }
}

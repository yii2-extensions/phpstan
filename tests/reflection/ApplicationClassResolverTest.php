<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\reflection;

use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use RuntimeException;
use yii2\extensions\phpstan\exception\Message;
use yii2\extensions\phpstan\reflection\ApplicationClassResolver;
use yii2\extensions\phpstan\ServiceMap;
use yii2\extensions\phpstan\tests\provider\ApplicationClassResolverProvider;

/**
 * Unit tests for {@see ApplicationClassResolver} validation of the configured application type.
 *
 * {@see ApplicationClassResolverProvider} for test case data providers.
 */
final class ApplicationClassResolverTest extends PHPStanTestCase
{
    /**
     * Base path for configuration files used in tests.
     */
    private const BASE_PATH = __DIR__
        . DIRECTORY_SEPARATOR
        . '..'
        . DIRECTORY_SEPARATOR
        . 'config'
        . DIRECTORY_SEPARATOR;

    #[DataProviderExternal(ApplicationClassResolverProvider::class, 'acceptedApplicationTypeProvider')]
    public function testResolveAcceptedApplicationType(string $config, string $expected): void
    {
        $serviceMap = new ServiceMap($config !== '' ? self::BASE_PATH . $config : '');

        self::assertSame(
            $expected,
            ApplicationClassResolver::resolve($serviceMap->getApplicationType(), self::createReflectionProvider())
                ?->getName(),
            'Class reflection must match the configured application class.',
        );
    }

    #[DataProviderExternal(ApplicationClassResolverProvider::class, 'rejectedApplicationTypeProvider')]
    public function testThrowRuntimeExceptionForRejectedApplicationType(
        string $config,
        string $applicationType,
        string $kind,
    ): void {
        $serviceMap = new ServiceMap(self::BASE_PATH . $config);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            Message::APPLICATION_TYPE_NOT_APPLICATION_CLASS->getMessage($applicationType, $kind),
        );

        ApplicationClassResolver::resolve($serviceMap->getApplicationType(), self::createReflectionProvider());
    }
}

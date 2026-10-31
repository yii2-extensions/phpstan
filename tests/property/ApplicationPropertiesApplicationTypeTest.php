<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\property;

use PHPStan\Reflection\Annotations\AnnotationsPropertiesClassReflectionExtension;
use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use RuntimeException;
use yii\web\Application;
use yii2\extensions\phpstan\exception\Message;
use yii2\extensions\phpstan\property\ApplicationPropertiesClassReflectionExtension;
use yii2\extensions\phpstan\ServiceMap;
use yii2\extensions\phpstan\tests\provider\ApplicationClassResolverProvider;
use yii2\extensions\phpstan\tests\support\stub\RecordingDeclarationDependencyTracker;

/**
 * Unit tests for {@see ApplicationPropertiesClassReflectionExtension} rejection of an invalid configured application
 * type before any application property is resolved.
 *
 * {@see ApplicationClassResolverProvider} for test case data providers.
 */
final class ApplicationPropertiesApplicationTypeTest extends PHPStanTestCase
{
    /**
     * Base path for configuration files used in tests.
     */
    private const BASE_PATH = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config'
        . DIRECTORY_SEPARATOR;

    public function testThrowRuntimeExceptionWhenGetPropertyResolvesInvalidApplicationType(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            Message::APPLICATION_TYPE_NOT_APPLICATION_CLASS->getMessage('yii\web\IdentityInterface', 'an interface'),
        );

        self::createExtension('phpstan-invalid-app-interface-config.php')->getProperty(
            self::createReflectionProvider()->getClass(Application::class),
            'request',
        );
    }

    #[DataProviderExternal(ApplicationClassResolverProvider::class, 'rejectedApplicationTypeProvider')]
    public function testThrowRuntimeExceptionWhenHasPropertyResolvesInvalidApplicationType(
        string $config,
        string $applicationType,
        string $kind,
    ): void {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            Message::APPLICATION_TYPE_NOT_APPLICATION_CLASS->getMessage($applicationType, $kind),
        );

        self::createExtension($config)->hasProperty(
            self::createReflectionProvider()->getClass(Application::class),
            'request',
        );
    }

    /**
     * Creates the extension under test for the given configuration file.
     */
    private static function createExtension(string $config): ApplicationPropertiesClassReflectionExtension
    {
        return new ApplicationPropertiesClassReflectionExtension(
            self::getContainer()->getByType(AnnotationsPropertiesClassReflectionExtension::class),
            self::createReflectionProvider(),
            new ServiceMap(self::BASE_PATH . $config),
            new RecordingDeclarationDependencyTracker(),
        );
    }
}

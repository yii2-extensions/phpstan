<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests;

use PHPUnit\Framework\Attributes\{DataProviderExternal, RequiresOperatingSystem};
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SplFileInfo;
use SplObjectStorage;
use SplStack;
use yii\base\InvalidArgumentException;
use yii2\extensions\phpstan\ServiceMap;
use yii2\extensions\phpstan\tests\provider\ServiceMapServiceProvider;
use yii2\extensions\phpstan\tests\support\stub\MyActiveRecord;

use function file_put_contents;
use function symlink;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

/**
 * Test suite for {@see ServiceMap} service resolution and container definition behavior.
 *
 * Validates correct mapping and retrieval of service classes and definitions from configuration files, ensuring robust
 * error handling for invalid service structures.
 *
 * The tests cover scenarios including valid and invalid service IDs, class resolution, definitions whose class can't
 * be determined being skipped, and exception handling for malformed configuration sections and scalar definitions.
 *
 * {@see ServiceMapServiceProvider} for test case data providers.
 */
final class ServiceMapServiceTest extends TestCase
{
    /**
     * Base path for configuration files used in tests.
     */
    private const BASE_PATH = __DIR__ . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR;

    public function testAllowServiceMapWhenConfigPathEmpty(): void
    {
        $this->expectNotToPerformAssertions();

        new ServiceMap();
    }

    public function testAllowServiceMapWhenConfigPathEmptyString(): void
    {
        $this->expectNotToPerformAssertions();

        new ServiceMap('');
    }

    public function testAllowServiceMapWhenContainerEmpty(): void
    {
        $this->expectNotToPerformAssertions();

        new ServiceMap(self::BASE_PATH . 'config-container-empty.php');
    }

    public function testReturnNullWhenServiceNonExistent(): void
    {
        $serviceMap = new ServiceMap(self::BASE_PATH . 'phpstan-config.php');

        self::assertNull(
            $serviceMap->getServiceById('non-existent-service'),
            "ServiceMap should return 'null' for a non-existent service.",
        );
    }

    public function testReturnServiceClassWhenClosureValid(): void
    {
        $serviceMap = new ServiceMap(self::BASE_PATH . 'phpstan-config.php');

        self::assertSame(
            SplStack::class,
            $serviceMap->getServiceById('closure'),
            "ServiceMap should resolve 'closure' to 'SplStack::class'.",
        );
    }

    public function testReturnServiceClassWhenNestedValid(): void
    {
        $serviceMap = new ServiceMap(self::BASE_PATH . 'phpstan-config.php');

        self::assertSame(
            SplFileInfo::class,
            $serviceMap->getServiceById('nested-service-class'),
            "ServiceMap should resolve 'nested-service-class' to 'SplFileInfo::class'.",
        );
    }

    public function testReturnServiceClassWhenServiceValid(): void
    {
        $serviceMap = new ServiceMap(self::BASE_PATH . 'phpstan-config.php');

        self::assertSame(
            SplObjectStorage::class,
            $serviceMap->getServiceById('service'),
            "ServiceMap should resolve 'service' to 'SplObjectStorage::class'.",
        );
    }

    public function testReturnServiceClassWhenSingletonClassNameValid(): void
    {
        $serviceMap = new ServiceMap(self::BASE_PATH . 'phpstan-config.php');

        self::assertSame(
            MyActiveRecord::class,
            $serviceMap->getServiceById(MyActiveRecord::class),
            "ServiceMap should resolve 'MyActiveRecord::class' as a singleton 'string' service.",
        );
    }

    public function testReturnServiceClassWhenSingletonClosureValid(): void
    {
        $serviceMap = new ServiceMap(self::BASE_PATH . 'phpstan-config.php');

        self::assertSame(
            SplStack::class,
            $serviceMap->getServiceById('singleton-closure'),
            "ServiceMap should resolve 'singleton-closure' to 'SplStack::class'.",
        );
    }

    public function testReturnServiceClassWhenSingletonNestedValid(): void
    {
        $serviceMap = new ServiceMap(self::BASE_PATH . 'phpstan-config.php');

        self::assertSame(
            SplFileInfo::class,
            $serviceMap->getServiceById('singleton-nested-service-class'),
            "ServiceMap should resolve 'singleton-nested-service-class' to 'SplFileInfo::class'.",
        );
    }

    public function testReturnServiceClassWhenSingletonServiceValid(): void
    {
        $serviceMap = new ServiceMap(self::BASE_PATH . 'phpstan-config.php');

        self::assertSame(
            SplObjectStorage::class,
            $serviceMap->getServiceById('singleton-service'),
            "ServiceMap should resolve 'singleton-service' to 'SplObjectStorage::class'.",
        );
    }

    public function testReturnServiceClassWhenSingletonStringValid(): void
    {
        $serviceMap = new ServiceMap(self::BASE_PATH . 'phpstan-config.php');

        self::assertSame(
            MyActiveRecord::class,
            $serviceMap->getServiceById('singleton-string'),
            "ServiceMap should resolve 'singleton-string' to 'MyActiveRecord::class'.",
        );
    }

    #[DataProviderExternal(ServiceMapServiceProvider::class, 'unresolvableConfigProvider')]
    public function testSkipServiceWhenClassUnresolvable(string $configFile, string $id): void
    {
        $serviceMap = new ServiceMap(self::BASE_PATH . $configFile);

        self::assertNull(
            $serviceMap->getServiceById($id),
            'Unresolvable service must be unknown.',
        );
        self::assertSame(
            SplObjectStorage::class,
            $serviceMap->getServiceById('service'),
            'Remaining services must still be registered.',
        );
    }

    public function testThrowInvalidArgumentExceptionWhenConfigPathInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "Provided config path 'invalid-path' must be a readable PHP file.",
        );

        new ServiceMap('invalid-path');
    }

    #[RequiresOperatingSystem('Linux|Darwin')]
    public function testThrowInvalidArgumentExceptionWhenConfigPathIsSymlinkToNonPhpFile(): void
    {
        $target = tempnam(sys_get_temp_dir(), 'phpstan-config-');

        self::assertNotFalse(
            $target,
            'Temporary target file must be created.',
        );

        file_put_contents($target, "secret-credentials\n");

        $symlink = $target . '.php';

        self::assertTrue(
            symlink($target, $symlink),
            'Symlink to the non-PHP target must be created.',
        );

        try {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage(
                "Provided config path '{$symlink}' must be a readable PHP file.",
            );

            new ServiceMap($symlink);
        } finally {
            unlink($symlink);
            unlink($target);
        }
    }

    public function testThrowRuntimeExceptionWhenConfigNotArray(): void
    {
        $configPath = self::BASE_PATH . 'config-unsupported-is-not-array.php';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            "Configuration file '{$configPath}' must return an array.",
        );

        new ServiceMap($configPath);
    }

    public function testThrowRuntimeExceptionWhenContainerDefinitionsNotArray(): void
    {
        $configPath = self::BASE_PATH . 'definitions-unsupported-is-not-array.php';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            "Configuration file '{$configPath}' must contain a valid 'container.definitions' 'array'.",
        );

        new ServiceMap($configPath);
    }

    public function testThrowRuntimeExceptionWhenContainerNotArray(): void
    {
        $configPath = self::BASE_PATH . 'config-container-unsupported-type-array-invalid.php';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            "Configuration file '{$configPath}' must contain a valid 'container' 'array'.",
        );

        new ServiceMap($configPath);
    }

    public function testThrowRuntimeExceptionWhenContainerSingletonsNotArray(): void
    {
        $configPath = self::BASE_PATH . 'singletons-unsupported-is-not-array.php';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            "Configuration file '{$configPath}' must contain a valid 'container.singletons' 'array'.",
        );

        new ServiceMap($configPath);
    }

    public function testThrowRuntimeExceptionWhenDefinitionIdNotString(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            "'Definition': 'ID' must be a 'string', got 'integer'.",
        );

        new ServiceMap(self::BASE_PATH . 'definitions-unsupported-id-not-string.php');
    }

    public function testThrowRuntimeExceptionWhenDefinitionNotArray(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            "Unsupported definition for 'unsupported-type-integer'.",
        );

        new ServiceMap(self::BASE_PATH . 'definitions-unsupported-type-integer.php');
    }

    public function testThrowRuntimeExceptionWhenSingletonIdNotString(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            "'Singleton': 'ID' must be a 'string', got 'integer'.",
        );

        new ServiceMap(self::BASE_PATH . 'singletons-unsupported-id-not-string.php');
    }

    public function testThrowRuntimeExceptionWhenSingletonNotArray(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            "Unsupported definition for 'unsupported-type-integer'.",
        );

        new ServiceMap(self::BASE_PATH . 'singletons-unsupported-type-integer.php');
    }
}

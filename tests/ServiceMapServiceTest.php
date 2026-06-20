<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests;

use PHPUnit\Framework\TestCase;
use ReflectionException;
use RuntimeException;
use SplFileInfo;
use SplObjectStorage;
use SplStack;
use yii\base\InvalidArgumentException;
use yii2\extensions\phpstan\ServiceMap;
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
 * error handling for invalid or unsupported service structures.
 *
 * The tests cover scenarios including valid and invalid service IDs, class resolution, definition extraction, and
 * exception handling for misconfigured or malformed service arrays.
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

    /**
     * @throws ReflectionException if the service definition is invalid or can't be resolved.
     */
    public function testAllowServiceMapWhenContainerEmpty(): void
    {
        $this->expectNotToPerformAssertions();

        new ServiceMap(self::BASE_PATH . 'config-container-empty.php');
    }

    /**
     * @throws ReflectionException if the service definition is invalid or can't be resolved.
     */
    public function testReturnNullWhenServiceNonExistent(): void
    {
        $serviceMap = new ServiceMap(self::BASE_PATH . 'phpstan-config.php');

        self::assertNull(
            $serviceMap->getServiceById('non-existent-service'),
            'ServiceMap should return \'null\' for a non-existent service.',
        );
    }

    /**
     * @throws ReflectionException if the service definition is invalid or can't be resolved.
     */
    public function testReturnServiceClassWhenClosureValid(): void
    {
        $serviceMap = new ServiceMap(self::BASE_PATH . 'phpstan-config.php');

        self::assertSame(
            SplStack::class,
            $serviceMap->getServiceById('closure'),
            'ServiceMap should resolve \'closure\' to \'SplStack::class\'.',
        );
    }

    /**
     * @throws ReflectionException if the service definition is invalid or can't be resolved.
     */
    public function testReturnServiceClassWhenNestedValid(): void
    {
        $serviceMap = new ServiceMap(self::BASE_PATH . 'phpstan-config.php');

        self::assertSame(
            SplFileInfo::class,
            $serviceMap->getServiceById('nested-service-class'),
            'ServiceMap should resolve \'nested-service-class\' to \'SplFileInfo::class\'.',
        );
    }

    /**
     * @throws ReflectionException if the service definition is invalid or can't be resolved.
     */
    public function testReturnServiceClassWhenServiceValid(): void
    {
        $serviceMap = new ServiceMap(self::BASE_PATH . 'phpstan-config.php');

        self::assertSame(
            SplObjectStorage::class,
            $serviceMap->getServiceById('service'),
            'ServiceMap should resolve \'service\' to \'SplObjectStorage::class\'.',
        );
    }

    /**
     * @throws ReflectionException if the service definition is invalid or can't be resolved.
     */
    public function testReturnServiceClassWhenSingletonClassNameValid(): void
    {
        $serviceMap = new ServiceMap(self::BASE_PATH . 'phpstan-config.php');

        self::assertSame(
            MyActiveRecord::class,
            $serviceMap->getServiceById(MyActiveRecord::class),
            'ServiceMap should resolve \'MyActiveRecord::class\' as a singleton \'string\' service.',
        );
    }

    /**
     * @throws ReflectionException if the service definition is invalid or can't be resolved.
     */
    public function testReturnServiceClassWhenSingletonClosureValid(): void
    {
        $serviceMap = new ServiceMap(self::BASE_PATH . 'phpstan-config.php');

        self::assertSame(
            SplStack::class,
            $serviceMap->getServiceById('singleton-closure'),
            'ServiceMap should resolve \'singleton-closure\' to \'SplStack::class\'.',
        );
    }

    /**
     * @throws ReflectionException if the service definition is invalid or can't be resolved.
     */
    public function testReturnServiceClassWhenSingletonNestedValid(): void
    {
        $serviceMap = new ServiceMap(self::BASE_PATH . 'phpstan-config.php');

        self::assertSame(
            SplFileInfo::class,
            $serviceMap->getServiceById('singleton-nested-service-class'),
            'ServiceMap should resolve \'singleton-nested-service-class\' to \'SplFileInfo::class\'.',
        );
    }

    /**
     * @throws ReflectionException if the service definition is invalid or can't be resolved.
     */
    public function testReturnServiceClassWhenSingletonServiceValid(): void
    {
        $serviceMap = new ServiceMap(self::BASE_PATH . 'phpstan-config.php');

        self::assertSame(
            SplObjectStorage::class,
            $serviceMap->getServiceById('singleton-service'),
            'ServiceMap should resolve \'singleton-service\' to \'SplObjectStorage::class\'.',
        );
    }

    /**
     * @throws ReflectionException if the service definition is invalid or can't be resolved.
     */
    public function testReturnServiceClassWhenSingletonStringValid(): void
    {
        $serviceMap = new ServiceMap(self::BASE_PATH . 'phpstan-config.php');

        self::assertSame(
            MyActiveRecord::class,
            $serviceMap->getServiceById('singleton-string'),
            'ServiceMap should resolve \'singleton-string\' to \'MyActiveRecord::class\'.',
        );
    }

    public function testThrowInvalidArgumentExceptionWhenConfigPathInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Provided config path 'invalid-path' must be a readable PHP file.");

        new ServiceMap('invalid-path');
    }

    public function testThrowInvalidArgumentExceptionWhenConfigPathIsSymlinkToNonPhpFile(): void
    {
        $target = tempnam(sys_get_temp_dir(), 'phpstan-config-');

        self::assertNotFalse($target, 'Temporary target file must be created.');

        file_put_contents($target, "secret-credentials\n");

        $symlink = $target . '.php';

        if (@symlink($target, $symlink) === false) {
            unlink($target);

            self::markTestSkipped('Symlinks are not supported in this environment.');
        }

        try {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage("Provided config path '{$symlink}' must be a readable PHP file.");

            new ServiceMap($symlink);
        } finally {
            unlink($symlink);
            unlink($target);
        }
    }

    /**
     * @throws ReflectionException if the service definition is invalid or can't be resolved.
     */
    public function testThrowRuntimeExceptionWhenConfigNotArray(): void
    {
        $configPath = self::BASE_PATH . 'config-unsupported-is-not-array.php';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Configuration file '{$configPath}' must return an array.");

        new ServiceMap($configPath);
    }

    /**
     * @throws ReflectionException if the service definition is invalid or can't be resolved.
     */
    public function testThrowRuntimeExceptionWhenContainerDefinitionsNotArray(): void
    {
        $configPath = self::BASE_PATH . 'definitions-unsupported-is-not-array.php';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            "Configuration file '{$configPath}' must contain a valid 'container.definitions' 'array'.",
        );

        new ServiceMap($configPath);
    }

    /**
     * @throws ReflectionException if the service definition is invalid or can't be resolved.
     */
    public function testThrowRuntimeExceptionWhenContainerNotArray(): void
    {
        $configPath = self::BASE_PATH . 'config-container-unsupported-type-array-invalid.php';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Configuration file '{$configPath}' must contain a valid 'container' 'array'.");

        new ServiceMap($configPath);
    }

    /**
     * @throws ReflectionException if the service definition is invalid or can't be resolved.
     */
    public function testThrowRuntimeExceptionWhenContainerSingletonsNotArray(): void
    {
        $configPath = self::BASE_PATH . 'singletons-unsupported-is-not-array.php';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            "Configuration file '{$configPath}' must contain a valid 'container.singletons' 'array'.",
        );

        new ServiceMap($configPath);
    }

    /**
     * @throws ReflectionException if the service definition is invalid or can't be resolved.
     */
    public function testThrowRuntimeExceptionWhenDefinitionArrayInvalid(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported definition for \'unsupported-array-invalid\'.');

        new ServiceMap(self::BASE_PATH . 'definitions-unsupported-type-array-invalid.php');
    }

    /**
     * @throws ReflectionException if the service definition is invalid or can't be resolved.
     */
    public function testThrowRuntimeExceptionWhenDefinitionClosureMissingReturnType(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Please provide return type for \'closure-not-return-type\' service closure.');

        new ServiceMap(self::BASE_PATH . 'definitions-closure-not-return-type.php');
    }

    /**
     * @throws ReflectionException if the service definition is invalid or can't be resolved.
     */
    public function testThrowRuntimeExceptionWhenDefinitionEmptyArray(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported definition for \'unsupported-empty-array\'.');

        new ServiceMap(self::BASE_PATH . 'definitions-unsupported-empty-array.php');
    }

    /**
     * @throws ReflectionException if the service definition is invalid or can't be resolved.
     */
    public function testThrowRuntimeExceptionWhenDefinitionIdNotString(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('\'Definition\': \'ID\' must be a \'string\', got \'integer\'.');

        new ServiceMap(self::BASE_PATH . 'definitions-unsupported-id-not-string.php');
    }

    /**
     * @throws ReflectionException if the service definition is invalid or can't be resolved.
     */
    public function testThrowRuntimeExceptionWhenDefinitionNotArray(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported definition for \'unsupported-type-integer\'.');

        new ServiceMap(self::BASE_PATH . 'definitions-unsupported-type-integer.php');
    }

    /**
     * @throws ReflectionException if the service definition is invalid or can't be resolved.
     */
    public function testThrowRuntimeExceptionWhenSingletonArrayInvalid(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported definition for \'unsupported-array-invalid\'.');

        new ServiceMap(self::BASE_PATH . 'singletons-unsupported-type-array-invalid.php');
    }

    /**
     * @throws ReflectionException if the service definition is invalid or can't be resolved.
     */
    public function testThrowRuntimeExceptionWhenSingletonClosureMissingReturnType(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Please provide return type for \'closure-not-return-type\' service closure.');

        new ServiceMap(self::BASE_PATH . 'singletons-closure-not-return-type.php');
    }

    /**
     * @throws ReflectionException if the service definition is invalid or can't be resolved.
     */
    public function testThrowRuntimeExceptionWhenSingletonEmptyArray(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported definition for \'unsupported-empty-array\'.');

        new ServiceMap(self::BASE_PATH . 'singletons-unsupported-empty-array.php');
    }

    /**
     * @throws ReflectionException if the service definition is invalid or can't be resolved.
     */
    public function testThrowRuntimeExceptionWhenSingletonIdNotString(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('\'Singleton\': \'ID\' must be a \'string\', got \'integer\'.');

        new ServiceMap(self::BASE_PATH . 'singletons-unsupported-id-not-string.php');
    }
}

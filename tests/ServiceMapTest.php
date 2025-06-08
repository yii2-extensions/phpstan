<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests;

use PhpParser\Node\Scalar\String_;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use RuntimeException;
use SplFileInfo;
use SplObjectStorage;
use SplStack;
use yii\base\InvalidArgumentException;
use yii2\extensions\phpstan\ServiceMap;
use yii2\extensions\phpstan\tests\stub\MyActiveRecord;

/**
 * Test suite for {@see ServiceMap} class functionality and behavior.
 *
 * Validates the ability of the ServiceMap to parse Yii application configuration files and resolve service/component
 * class names for static analysis.
 *
 * Ensures correct handling of valid and invalid service/component definitions, exception scenarios, and edge cases in
 * configuration parsing.
 *
 * These tests guarantee that the ServiceMap provides accurate class resolution for PHPStan reflection extensions,
 * and throws descriptive exceptions for misconfigurations, missing files, or unsupported definitions.
 *
 * Test coverage.
 * - Allows configuration files without singletons.
 * - Loads and resolves services and components from valid configuration.
 * - Throws exceptions for closure services without return types.
 * - Throws exceptions for invalid component values.
 * - Throws exceptions for missing configuration files.
 * - Throws exceptions for unsupported array and type service definitions.
 *
 * @copyright Copyright (C) 2023 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class ServiceMapTest extends TestCase
{
    /**
     * @throws ReflectionException if the service definition is invalid or can't be resolved.
     */
    public function testItAllowsContainerWithoutDefinitionsAndSingletons(): void
    {
        $this->expectNotToPerformAssertions();

        $ds = DIRECTORY_SEPARATOR;

        new ServiceMap(__DIR__ . "{$ds}fixture{$ds}config-container-empty.php");
    }

    /**
     * @throws ReflectionException if the service definition is invalid or can't be resolved.
     */
    public function testItLoadsServicesAndComponents(): void
    {
        $ds = DIRECTORY_SEPARATOR;
        $fixturePath = __DIR__ . "{$ds}fixture{$ds}config.php";

        $serviceMap = new ServiceMap($fixturePath);

        self::assertNull(
            $serviceMap->getServiceClassFromNode(new String_('non-existent-service')),
            'ServiceMap should return \'null\' for a \'non-existent service\'.',
        );
        self::assertSame(
            MyActiveRecord::class,
            $serviceMap->getServiceClassFromNode(new String_('singleton-string')),
            'ServiceMap should resolve \'singleton-string\' to \'MyActiveRecord::class\'.',
        );
        self::assertSame(
            MyActiveRecord::class,
            $serviceMap->getServiceClassFromNode(new String_(MyActiveRecord::class)),
            'ServiceMap should resolve \'MyActiveRecord::class\' as a singleton string service.',
        );
        self::assertSame(
            SplStack::class,
            $serviceMap->getServiceClassFromNode(new String_('singleton-closure')),
            'ServiceMap should resolve \'singleton-closure\' to \'SplStack::class\'.',
        );
        self::assertSame(
            SplObjectStorage::class,
            $serviceMap->getServiceClassFromNode(new String_('singleton-service')),
            'ServiceMap should resolve \'singleton-service\' to \'SplObjectStorage::class\'.',
        );
        self::assertSame(
            SplFileInfo::class,
            $serviceMap->getServiceClassFromNode(new String_('singleton-nested-service-class')),
            'ServiceMap should resolve \'singleton-nested-service-class\' to \'SplFileInfo::class\'.',
        );
        self::assertSame(
            SplStack::class,
            $serviceMap->getServiceClassFromNode(new String_('closure')),
            'ServiceMap should resolve \'closure\' to \'SplStack::class\'.',
        );
        self::assertSame(
            SplObjectStorage::class,
            $serviceMap->getServiceClassFromNode(new String_('service')),
            'ServiceMap should resolve \'service\' to \'SplObjectStorage::class\'.',
        );
        self::assertSame(
            SplFileInfo::class,
            $serviceMap->getServiceClassFromNode(new String_('nested-service-class')),
            'ServiceMap should resolve \'nested-service-class\' to \'SplFileInfo::class\'.',
        );
        self::assertSame(
            MyActiveRecord::class,
            $serviceMap->getComponentClassById('customComponent'),
            'ServiceMap should resolve component id \'customComponent\' to \'MyActiveRecord::class\'.',
        );
        self::assertNull(
            $serviceMap->getComponentClassById('nonExistentComponent'),
            'ServiceMap should return \'null\' for a \'non-existent\' component id.',
        );
        self::assertSame(
            MyActiveRecord::class,
            $serviceMap->getComponentClassById('customInitializedComponent'),
            'ServiceMap should resolve component id \'customInitializedComponent\' to \'MyActiveRecord::class\'.',
        );
        self::assertNull(
            $serviceMap->getComponentClassById('assetManager'),
            'ServiceMap should return \'null\' for \'assetManager\' component id as it is not a class but an array.',
        );
    }

    public function testItAllowsWithoutEmptyConfigPath(): void
    {
        $this->expectNotToPerformAssertions();

        new ServiceMap();
    }

    public function testItAllowsWithoutEmptyConfigPathStringValue(): void
    {
        $this->expectNotToPerformAssertions();

        new ServiceMap('');
    }

    /**
     * @throws ReflectionException if the service definition is invalid or can't be resolved.
     */
    public function testThrowRuntimeExceptionWhenComponentsHasUnsupportedIsNotArrayValue(): void
    {
        $ds = DIRECTORY_SEPARATOR;
        $fixturePath = __DIR__ . "{$ds}fixture{$ds}components-unsupported-is-not-array.php";

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Configuration file '{$fixturePath}' must contain a valid 'components' 'array'.");

        new ServiceMap($fixturePath);
    }

    /**
     * @throws ReflectionException if the service definition is invalid or can't be resolved.
     */
    public function testThrowRuntimeExceptionWhenComponentsHasUnsupportedIdNotStringValue(): void
    {
        $ds = DIRECTORY_SEPARATOR;
        $fixturePath = __DIR__ . "{$ds}fixture{$ds}components-unsupported-id-not-string.php";

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('\'Component\': ID must be a string, got \'integer\'.');

        new ServiceMap($fixturePath);
    }

    public function testThrowRuntimeExceptionWhenConfigPathFileDoesNotExist(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Provided config path \'invalid-path\' must be a readable file.');

        new ServiceMap('invalid-path');
    }

    /**
     * @throws ReflectionException if the service definition is invalid or can't be resolved.
     */
    public function testThrowRuntimeExceptionWhenConfigHasUnsupportedIsNotArrayValue(): void
    {
        $ds = DIRECTORY_SEPARATOR;
        $fixturePath = __DIR__ . "{$ds}fixture{$ds}config-unsupported-is-not-array.php";

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Configuration file '{$fixturePath}' must return an array.");

        new ServiceMap($fixturePath);
    }

    /**
     * @throws ReflectionException if the service definition is invalid or can't be resolved.
     */
    public function testThrowRuntimeExceptionWhenConfigContainerDefinitionsHasUnsupportedIsNotArrayValue(): void
    {
        $ds = DIRECTORY_SEPARATOR;
        $fixturePath = __DIR__ . "{$ds}fixture{$ds}definitions-unsupported-is-not-array.php";

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            "Configuration file '{$fixturePath}' must contain a valid 'container.definitions' 'array'.",
        );

        new ServiceMap($fixturePath);
    }

    /**
     * @throws ReflectionException if the service definition is invalid or can't be resolved.
     */
    public function testThrowRuntimeExceptionWhenConfigContainerHasUnsupportedIsNotArrayValue(): void
    {
        $ds = DIRECTORY_SEPARATOR;
        $fixturePath = __DIR__ . "{$ds}fixture{$ds}config-container-unsupported-type-array-invalid.php";

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Configuration file '{$fixturePath}' must contain a valid 'container' 'array'.");

        new ServiceMap($fixturePath);
    }

    /**
     * @throws ReflectionException if the service definition is invalid or can't be resolved.
     */
    public function testThrowRuntimeExceptionWhenConfigContainerSingletonsHasUnsupportedIsNotArrayValue(): void
    {
        $ds = DIRECTORY_SEPARATOR;
        $fixturePath = __DIR__ . "{$ds}fixture{$ds}singletons-unsupported-is-not-array.php";

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            "Configuration file '{$fixturePath}' must contain a valid 'container.singletons' 'array'.",
        );

        new ServiceMap($fixturePath);
    }

    /**
     * @throws ReflectionException if the service definition is invalid or can't be resolved.
     */
    public function testThrowRuntimeExceptionWhenContainerDefinitionsHasClosureForMissingReturnType(): void
    {
        $ds = DIRECTORY_SEPARATOR;
        $fixturePath = __DIR__ . "{$ds}fixture{$ds}definitions-closure-not-return-type.php";

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Please provide return type for \'closure-not-return-type\' service closure.');

        new ServiceMap($fixturePath);
    }

    /**
     * @throws ReflectionException if the service definition is invalid or can't be resolved.
     */
    public function testThrowRuntimeExceptionWhenContainerDefinitionsHasUnsupportedIdNotStringValue(): void
    {
        $ds = DIRECTORY_SEPARATOR;
        $fixturePath = __DIR__ . "{$ds}fixture{$ds}definitions-unsupported-id-not-string.php";

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('\'Definition\': ID must be a string, got \'integer\'.');

        new ServiceMap($fixturePath);
    }

    /**
     * @throws ReflectionException if the service definition is invalid or can't be resolved.
     */
    public function testThrowRuntimeExceptionWhenContainerDefinitionsHasUnsupportedIsNotArrayValue(): void
    {
        $ds = DIRECTORY_SEPARATOR;
        $fixturePath = __DIR__ . "{$ds}fixture{$ds}definitions-unsupported-type-integer.php";

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported definition for \'unsupported-type-integer\'.');

        new ServiceMap($fixturePath);
    }

    /**
     * @throws ReflectionException if the service definition is invalid or can't be resolved.
     */
    public function testThrowRuntimeExceptionWhenContainerDefinitionsHasUnsupportedTypeArrayInvalidValue(): void
    {
        $ds = DIRECTORY_SEPARATOR;
        $fixturePath = __DIR__ . "{$ds}fixture{$ds}definitions-unsupported-type-array-invalid.php";

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported definition for \'unsupported-array-invalid\'.');

        new ServiceMap($fixturePath);
    }

    /**
     * @throws ReflectionException if the service definition is invalid or can't be resolved.
     */
    public function testThrowRuntimeExceptionWhenContainerDefinitionsHasUnsupportedTypeEmptyArrayValue(): void
    {
        $ds = DIRECTORY_SEPARATOR;
        $fixturePath = __DIR__ . "{$ds}fixture{$ds}definitions-unsupported-empty-array.php";

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported definition for \'unsupported-empty-array\'.');

        new ServiceMap($fixturePath);
    }

    /**
     * @throws ReflectionException if the service definition is invalid or can't be resolved.
     */
    public function testThrowRuntimeExceptionWhenContainerDefinitionsHasUnsupportedTypeIntegerValue(): void
    {
        $ds = DIRECTORY_SEPARATOR;
        $fixturePath = __DIR__ . "{$ds}fixture{$ds}definitions-unsupported-type-integer.php";

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported definition for \'unsupported-type-integer\'.');

        new ServiceMap($fixturePath);
    }

    /**
     * @throws ReflectionException if the service definition is invalid or can't be resolved.
     */
    public function testThrowRuntimeExceptionWhenContainerSingletonsHasClosureForMissingReturnType(): void
    {
        $ds = DIRECTORY_SEPARATOR;
        $fixturePath = __DIR__ . "{$ds}fixture{$ds}singletons-closure-not-return-type.php";

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Please provide return type for \'closure-not-return-type\' service closure.');

        new ServiceMap($fixturePath);
    }

    /**
     * @throws ReflectionException if the service definition is invalid or can't be resolved.
     */
    public function testThrowRuntimeExceptionWhenContainerSingletonsHasUnsupportedIdNotStringValue(): void
    {
        $ds = DIRECTORY_SEPARATOR;
        $fixturePath = __DIR__ . "{$ds}fixture{$ds}singletons-unsupported-id-not-string.php";

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('\'Singleton\': ID must be a string, got \'integer\'.');

        new ServiceMap($fixturePath);
    }

    /**
     * @throws ReflectionException if the service definition is invalid or can't be resolved.
     */
    public function testThrowRuntimeExceptionWhenContainerSingletonsHasUnsupportedTypeArrayInvalidValue(): void
    {
        $ds = DIRECTORY_SEPARATOR;
        $fixturePath = __DIR__ . "{$ds}fixture{$ds}singletons-unsupported-type-array-invalid.php";

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported definition for \'unsupported-array-invalid\'.');

        new ServiceMap($fixturePath);
    }

    /**
     * @throws ReflectionException if the service definition is invalid or can't be resolved.
     */
    public function testThrowRuntimeExceptionWhenContainerSingletonsHasUnsupportedTypeEmptyArrayValue(): void
    {
        $ds = DIRECTORY_SEPARATOR;
        $fixturePath = __DIR__ . "{$ds}fixture{$ds}singletons-unsupported-empty-array.php";

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported definition for \'unsupported-empty-array\'.');

        new ServiceMap($fixturePath);
    }
}

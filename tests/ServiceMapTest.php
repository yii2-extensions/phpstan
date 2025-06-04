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
    public function testItAllowsConfigWithoutSingletons(): void
    {
        $this->expectNotToPerformAssertions();

        new ServiceMap(
            __DIR__ . DIRECTORY_SEPARATOR . 'fixture' . DIRECTORY_SEPARATOR . 'yii-config-no-singletons.php',
        );
    }

    /**
     * @throws ReflectionException if the service definition is invalid or can't be resolved.
     */
    public function testItLoadsServicesAndComponents(): void
    {
        $fixturePath = __DIR__ . DIRECTORY_SEPARATOR . 'fixture' . DIRECTORY_SEPARATOR . 'yii-config-valid.php';

        $serviceMap = new ServiceMap($fixturePath);

        $this->assertNull(
            $serviceMap->getServiceClassFromNode(new String_('non-existent-service')),
            'ServiceMap should return \'null\' for a \'non-existent service\'.',
        );
        $this->assertSame(
            MyActiveRecord::class,
            $serviceMap->getServiceClassFromNode(new String_('singleton-string')),
            'ServiceMap should resolve \'singleton-string\' to \'MyActiveRecord::class\'.',
        );
        $this->assertSame(
            MyActiveRecord::class,
            $serviceMap->getServiceClassFromNode(new String_(MyActiveRecord::class)),
            'ServiceMap should resolve \'MyActiveRecord::class\' as a singleton string service.',
        );
        // Assert: Singleton closure service resolves to SplStack::class.
        $this->assertSame(
            SplStack::class,
            $serviceMap->getServiceClassFromNode(new String_('singleton-closure')),
            'ServiceMap should resolve \'singleton-closure\' to \'SplStack::class\'.',
        );
        $this->assertSame(
            SplObjectStorage::class,
            $serviceMap->getServiceClassFromNode(new String_('singleton-service')),
            'ServiceMap should resolve \'singleton-service\' to \'SplObjectStorage::class\'.',
        );
        $this->assertSame(
            SplFileInfo::class,
            $serviceMap->getServiceClassFromNode(new String_('singleton-nested-service-class')),
            'ServiceMap should resolve \'singleton-nested-service-class\' to \'SplFileInfo::class\'.',
        );
        $this->assertSame(
            SplStack::class,
            $serviceMap->getServiceClassFromNode(new String_('closure')),
            'ServiceMap should resolve \'closure\' to \'SplStack::class\'.',
        );
        $this->assertSame(
            SplObjectStorage::class,
            $serviceMap->getServiceClassFromNode(new String_('service')),
            'ServiceMap should resolve \'service\' to \'SplObjectStorage::class\'.',
        );
        $this->assertSame(
            SplFileInfo::class,
            $serviceMap->getServiceClassFromNode(new String_('nested-service-class')),
            'ServiceMap should resolve \'nested-service-class\' to \'SplFileInfo::class\'.',
        );
        $this->assertSame(
            MyActiveRecord::class,
            $serviceMap->getComponentClassById('customComponent'),
            'ServiceMap should resolve component id \'customComponent\' to \'MyActiveRecord::class\'.',
        );
        $this->assertNull(
            $serviceMap->getComponentClassById('nonExistentComponent'),
            'ServiceMap should return \'null\' for a \'non-existent\' component id.',
        );
        $this->assertSame(
            MyActiveRecord::class,
            $serviceMap->getComponentClassById('customInitializedComponent'),
            'ServiceMap should resolve component id \'customInitializedComponent\' to \'MyActiveRecord::class\'.',
        );
    }

    /**
     * @throws ReflectionException if the service definition is invalid or can't be resolved.
     */
    public function testItAllowsWithoutEmptyConfigPath(): void
    {
        $this->expectNotToPerformAssertions();

        new ServiceMap();
    }

    /**
     * @throws ReflectionException if the service definition is invalid or can't be resolved.
     */
    public function testItAllowsWithoutEmptyConfigPathStringValue(): void
    {
        $this->expectNotToPerformAssertions();

        new ServiceMap('');
    }

    /**
     * @throws ReflectionException if the service definition is invalid or can't be resolved.
     */
    public function testThrowRuntimeExceptionWhenClosureServiceHasMissingReturnType(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Please provide return type for no-return-type service closure');

        $fixturePath = __DIR__ . DIRECTORY_SEPARATOR . 'fixture' . DIRECTORY_SEPARATOR . 'yii-config-invalid.php';

        new ServiceMap($fixturePath);
    }

    /**
     * @throws ReflectionException if the service definition is invalid or can't be resolved.
     */
    public function testThrowRuntimeExceptionWhenComponentHasInvalidValue(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid value for component with id customComponent. Expected object or array.');

        $fixturePath = __DIR__ . DIRECTORY_SEPARATOR . 'fixture' . DIRECTORY_SEPARATOR .
            'yii-config-invalid-component.php';

        new ServiceMap($fixturePath);
    }

    public function testThrowRuntimeExceptionWhenConfigurationFileDoesNotExist(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Provided config path invalid-path must exist');

        new ServiceMap('invalid-path');
    }

    /**
     * @throws ReflectionException if the service definition is invalid or can't be resolved.
     */
    public function testThrowRuntimeExceptionWhenServiceHasUnsupportedArray(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot guess service definition for unsupported-array');

        $fixturePath = __DIR__ . DIRECTORY_SEPARATOR . 'fixture' . DIRECTORY_SEPARATOR .
            'yii-config-invalid-unsupported-array.php';

        new ServiceMap($fixturePath);
    }

    /**
     * @throws ReflectionException if the service definition is invalid or can't be resolved.
     */
    public function testThrowRuntimeExceptionWhenServiceHasUnsupportedType(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported service definition for unsupported-type');

        $fixturePath = __DIR__ . DIRECTORY_SEPARATOR . 'fixture' . DIRECTORY_SEPARATOR .
            'yii-config-invalid-unsupported-type.php';

        new ServiceMap($fixturePath);
    }
}

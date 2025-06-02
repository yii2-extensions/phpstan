<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests;

use InvalidArgumentException;
use PhpParser\Node\Scalar\String_;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use RuntimeException;
use SplFileInfo;
use SplObjectStorage;
use SplStack;
use yii2\extensions\phpstan\ServiceMap;
use yii2\extensions\phpstan\tests\yii\MyActiveRecord;

final class ServiceMapTest extends TestCase
{
    public function testThrowExceptionWhenConfigurationFileDoesNotExist(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Provided config path invalid-path must exist');

        new ServiceMap('invalid-path');
    }

    /**
     * @throws ReflectionException
     */
    public function testThrowExceptionWhenClosureServiceHasMissingReturnType(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Please provide return type for no-return-type service closure');

        $fixturePath = __DIR__ . DIRECTORY_SEPARATOR . 'fixture' . DIRECTORY_SEPARATOR . 'yii-config-invalid.php';

        new ServiceMap($fixturePath);
    }

    /**
     * @throws ReflectionException
     */
    public function testThrowExceptionWhenServiceHasUnsupportedType(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported service definition for unsupported-type');

        $fixturePath = __DIR__ . DIRECTORY_SEPARATOR . 'fixture' . DIRECTORY_SEPARATOR .
            'yii-config-invalid-unsupported-type.php';

        new ServiceMap($fixturePath);
    }

    /**
     * @throws ReflectionException
     */
    public function testThrowExceptionWhenServiceHasUnsupportedArray(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot guess service definition for unsupported-array');

        $fixturePath = __DIR__ . DIRECTORY_SEPARATOR . 'fixture' . DIRECTORY_SEPARATOR .
            'yii-config-invalid-unsupported-array.php';

        new ServiceMap($fixturePath);
    }

    /**
     * @throws ReflectionException
     */
    public function testThrowExceptionWhenComponentHasInvalidValue(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid value for component with id customComponent. Expected object or array.');

        $fixturePath = __DIR__ . DIRECTORY_SEPARATOR . 'fixture' . DIRECTORY_SEPARATOR .
            'yii-config-invalid-component.php';

        new ServiceMap($fixturePath);
    }

    /**
     * @throws ReflectionException
     */
    public function testItLoadsServicesAndComponents(): void
    {
        $fixturePath = __DIR__ . DIRECTORY_SEPARATOR . 'fixture' . DIRECTORY_SEPARATOR . 'yii-config-valid.php';

        $serviceMap = new ServiceMap($fixturePath);

        $this->assertSame(
            MyActiveRecord::class,
            $serviceMap->getServiceClassFromNode(new String_('singleton-string')),
        );
        $this->assertSame(
            MyActiveRecord::class,
            $serviceMap->getServiceClassFromNode(new String_(MyActiveRecord::class)),
        );
        $this->assertSame(
            SplStack::class,
            $serviceMap->getServiceClassFromNode(new String_('singleton-closure')),
        );
        $this->assertSame(
            SplObjectStorage::class,
            $serviceMap->getServiceClassFromNode(new String_('singleton-service')),
        );
        $this->assertSame(
            SplFileInfo::class,
            $serviceMap->getServiceClassFromNode(new String_('singleton-nested-service-class')),
        );

        $this->assertSame(
            SplStack::class,
            $serviceMap->getServiceClassFromNode(new String_('closure')),
        );
        $this->assertSame(
            SplObjectStorage::class,
            $serviceMap->getServiceClassFromNode(new String_('service')),
        );
        $this->assertSame(
            SplFileInfo::class,
            $serviceMap->getServiceClassFromNode(new String_('nested-service-class')),
        );

        $this->assertSame(MyActiveRecord::class, $serviceMap->getComponentClassById('customComponent'));
        $this->assertSame(MyActiveRecord::class, $serviceMap->getComponentClassById('customInitializedComponent'));
    }

    /**
     * @throws ReflectionException
     */
    public function testItAllowsConfigWithoutSingletons(): void
    {
        $this->expectNotToPerformAssertions();

        new ServiceMap(__DIR__ . DIRECTORY_SEPARATOR . 'fixture' . DIRECTORY_SEPARATOR . 'yii-config-no-singletons.php');
    }
}

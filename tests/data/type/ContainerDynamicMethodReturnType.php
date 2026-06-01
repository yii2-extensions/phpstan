<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\type;

use Exception;
use yii\base\InvalidConfigException;
use yii\di\{Container, NotInstantiableException};
use yii2\extensions\phpstan\tests\support\stub\MyActiveRecord;

use function PHPStan\Testing\assertType;
use function random_int;

/**
 * Type assertion fixture for {@see Container::get()} return types in PHPStan analysis.
 *
 * Verifies type inference for container lookups, covering class-string and string identifiers, service definitions,
 * closures, singletons, nested services, parameterized instantiation, and the `mixed` fallback for unknown identifiers.
 */
final class ContainerDynamicMethodReturnType
{
    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     * @throws NotInstantiableException if a class or service can't be instantiated.
     */
    public function testReturnClassWhenGetByClassName(): void
    {
        $container = new Container();

        $activeRecord = $container->get(MyActiveRecord::class);

        assertType(
            'yii2\extensions\phpstan\tests\support\stub\MyActiveRecord',
            $activeRecord,
        );
        assertType(
            'bool',
            $activeRecord->flag,
        );
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     * @throws NotInstantiableException if a class or service can't be instantiated.
     */
    public function testReturnClassWhenGetByClassNameString(): void
    {
        $container = new Container();
        $className = 'yii2\extensions\phpstan\tests\support\stub\MyActiveRecord';

        assertType(
            'yii2\extensions\phpstan\tests\support\stub\MyActiveRecord',
            $container->get($className),
        );
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     * @throws NotInstantiableException if a class or service can't be instantiated.
     */
    public function testReturnMixedWhenGetWithUnknownId(): void
    {
        $container = new Container();

        assertType(
            'mixed',
            $container->get('unknown-service'),
        );
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     * @throws NotInstantiableException if a class or service can't be instantiated.
     */
    public function testReturnServiceWhenGetDefinitionClosure(): void
    {
        $container = new Container();

        assertType(
            'SplStack',
            $container->get('closure'),
        );
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     * @throws NotInstantiableException if a class or service can't be instantiated.
     */
    public function testReturnServiceWhenGetDefinitionService(): void
    {
        $container = new Container();

        assertType(
            'SplObjectStorage',
            $container->get('service'),
        );
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     * @throws NotInstantiableException if a class or service can't be instantiated.
     */
    public function testReturnServiceWhenGetNestedService(): void
    {
        $container = new Container();

        assertType(
            'SplFileInfo',
            $container->get('nested-service-class'),
        );
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     * @throws NotInstantiableException if a class or service can't be instantiated.
     */
    public function testReturnServiceWhenGetNestedSingleton(): void
    {
        $container = new Container();

        assertType(
            'SplFileInfo',
            $container->get('singleton-nested-service-class'),
        );
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     * @throws NotInstantiableException if a class or service can't be instantiated.
     */
    public function testReturnServiceWhenGetRealClassNotInServiceMap(): void
    {
        $container = new Container();

        assertType(
            'Exception',
            $container->get(Exception::class),
        );
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     * @throws NotInstantiableException if a class or service can't be instantiated.
     */
    public function testReturnServiceWhenGetServiceMapWithStringConstant(): void
    {
        $container = new Container();

        assertType(
            'yii2\extensions\phpstan\tests\support\stub\MyActiveRecord',
            $container->get('yii2\extensions\phpstan\tests\support\stub\MyActiveRecord'),
        );
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     * @throws NotInstantiableException if a class or service can't be instantiated.
     */
    public function testReturnServiceWhenGetSingletonClosure(): void
    {
        $container = new Container();

        assertType(
            'SplStack',
            $container->get('singleton-closure'),
        );
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     * @throws NotInstantiableException if a class or service can't be instantiated.
     */
    public function testReturnServiceWhenGetSingletonService(): void
    {
        $container = new Container();

        assertType(
            'SplObjectStorage',
            $container->get('singleton-service'),
        );
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     * @throws NotInstantiableException if a class or service can't be instantiated.
     */
    public function testReturnServiceWhenGetSingletonString(): void
    {
        $container = new Container();

        assertType(
            'yii2\extensions\phpstan\tests\support\stub\MyActiveRecord',
            $container->get('singleton-string'),
        );
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     * @throws NotInstantiableException if a class or service can't be instantiated.
     */
    public function testReturnServiceWhenGetWithConditional(): void
    {
        $container = new Container();

        $useService = (bool) random_int(0, 1);
        $result = $useService ? $container->get('singleton-service') : $container->get('closure');

        assertType(
            'SplObjectStorage|SplStack',
            $result,
        );
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     * @throws NotInstantiableException if a class or service can't be instantiated.
     */
    public function testReturnServiceWhenGetWithParameters(): void
    {
        $container = new Container();

        $params = ['flag' => true];

        assertType(
            'yii2\extensions\phpstan\tests\support\stub\MyActiveRecord',
            $container->get(MyActiveRecord::class, $params),
        );
    }
}

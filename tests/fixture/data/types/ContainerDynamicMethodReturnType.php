<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\fixture\data\types;

use yii\base\InvalidConfigException;
use yii\di\Container;
use yii\di\NotInstantiableException;
use yii2\extensions\phpstan\tests\stub\MyActiveRecord;

use function PHPStan\Testing\assertType;
use function random_int;

/**
 * Test suite for dynamic return types of {@see Container::get()} in Yii DI scenarios.
 *
 * Validates type inference and return types for the dependency injection container {@see Container::get()} method,
 * covering scenarios with class-string, string identifiers, service definitions, closures, and parameterized
 * instantiation.
 *
 * These tests ensure that PHPStan correctly infers the result types for container lookups, including class-based
 * resolution, service aliases, singleton and non-singleton services, closures, and unknown service identifiers.
 *
 * Key features.
 * - Conditional service resolution and union type assertions.
 * - Coverage for singleton, closure, and nested service definitions.
 * - Mixed type handling for unknown or dynamic service IDs.
 * - Parameterized instantiation and property assertions.
 * - Type assertions for class-string and string service identifiers.
 *
 * @copyright Copyright (C) 2023 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
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

        assertType('yii2\extensions\phpstan\tests\stub\MyActiveRecord', $activeRecord);
        assertType('bool', $activeRecord->flag);
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     * @throws NotInstantiableException if a class or service can't be instantiated.
     */
    public function testReturnClassWhenGetByClassNameString(): void
    {
        $container = new Container();
        $className = 'yii2\extensions\phpstan\tests\stub\MyActiveRecord';

        $instance = $container->get($className);

        assertType('yii2\extensions\phpstan\tests\stub\MyActiveRecord', $instance);
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

        assertType('SplObjectStorage|SplStack', $result);
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     * @throws NotInstantiableException if a class or service can't be instantiated.
     */
    public function testReturnServiceWhenGetWithParameters(): void
    {
        $container = new Container();

        $params = ['flag' => true];

        $instance = $container->get(MyActiveRecord::class, $params);

        assertType('yii2\extensions\phpstan\tests\stub\MyActiveRecord', $instance);
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     * @throws NotInstantiableException if a class or service can't be instantiated.
     */
    public function testReturnServiceWhenGetDefinitionClosure(): void
    {
        $container = new Container();

        $closure = $container->get('closure');

        assertType('SplStack', $closure);
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     * @throws NotInstantiableException if a class or service can't be instantiated.
     */
    public function testReturnServiceWhenGetNestedService(): void
    {
        $container = new Container();

        $nestedService = $container->get('nested-service-class');

        assertType('SplFileInfo', $nestedService);
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     * @throws NotInstantiableException if a class or service can't be instantiated.
     */
    public function testReturnServiceWhenGetDefinitionService(): void
    {
        $container = new Container();

        $service = $container->get('service');

        assertType('SplObjectStorage', $service);
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     * @throws NotInstantiableException if a class or service can't be instantiated.
     */
    public function testReturnMixedWhenGetWithUnknownId(): void
    {
        $container = new Container();

        $unknown = $container->get('unknown-service');

        assertType('mixed', $unknown);
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     * @throws NotInstantiableException if a class or service can't be instantiated.
     */
    public function testReturnServiceWhenGetSingletonClosure(): void
    {
        $container = new Container();

        $singletonClosure = $container->get('singleton-closure');

        assertType('SplStack', $singletonClosure);
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     * @throws NotInstantiableException if a class or service can't be instantiated.
     */
    public function testReturnServiceWhenGetNestedSingleton(): void
    {
        $container = new Container();

        $nestedService = $container->get('singleton-nested-service-class');

        assertType('SplFileInfo', $nestedService);
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     * @throws NotInstantiableException if a class or service can't be instantiated.
     */
    public function testReturnServiceWhenGetSingletonService(): void
    {
        $container = new Container();

        $singletonService = $container->get('singleton-service');

        assertType('SplObjectStorage', $singletonService);
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     * @throws NotInstantiableException if a class or service can't be instantiated.
     */
    public function testReturnServiceWhenGetSingletonString(): void
    {
        $container = new Container();

        $singletonString = $container->get('singleton-string');

        assertType('yii2\extensions\phpstan\tests\stub\MyActiveRecord', $singletonString);
    }
}

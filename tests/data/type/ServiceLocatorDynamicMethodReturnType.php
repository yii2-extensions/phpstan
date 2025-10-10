<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\type;

use yii\base\InvalidConfigException;
use yii\base\Module;
use yii\di\ServiceLocator;
use yii\web\{Application, Request, Response, Session, User};
use yii2\extensions\phpstan\tests\support\stub\MyActiveRecord;

use function PHPStan\Testing\assertType;

/**
 * Test suite for dynamic return types of {@see ServiceLocator::get()} in Yii component scenarios.
 *
 * Validates type inference and return types for the service locator {@see ServiceLocator::get()} method, covering
 * scenarios with component IDs, class names, module components, application components, and various service resolution
 * patterns.
 *
 * These tests ensure that PHPStan correctly infers the result types for ServiceLocator lookups, including
 * component-based resolution, service aliases, known Yii components, and unknown component identifiers.
 *
 * Key features:
 * - Component resolution from ServiceMap configuration.
 * - Mixed type handling for unknown or dynamic component IDs.
 * - Module and Application component access patterns.
 * - Priority testing: ServiceMap components > ServiceMap services > Real classes > Unknown.
 * - Type assertions for various Yii2 built-in components.
 *
 * @copyright Copyright (C) 2023 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class ServiceLocatorDynamicMethodReturnType
{
    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     */
    public function testReturnClassWhenGetByClassName(): void
    {
        $locator = new ServiceLocator();

        assertType('yii2\extensions\phpstan\tests\support\stub\MyActiveRecord', $locator->get(MyActiveRecord::class));
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     */
    public function testReturnClassWhenGetByClassNameString(): void
    {
        $locator = new ServiceLocator();

        $className = 'yii2\extensions\phpstan\tests\support\stub\MyActiveRecord';

        assertType(MyActiveRecord::class, $locator->get($className));
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     */
    public function testReturnMixedWhenGetWithUnknownId(): void
    {
        $locator = new ServiceLocator();

        assertType('mixed', $locator->get('unknown-component'));
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     */
    public function testReturnServicesWhenGetByBuiltInClassNames(): void
    {
        $locator = new ServiceLocator();

        assertType(User::class, $locator->get(User::class));
        assertType(Request::class, $locator->get(Request::class));
        assertType(Response::class, $locator->get(Response::class));
        assertType(Session::class, $locator->get(Session::class));
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     */
    public function testReturnServiceWhenGetByComponentId(): void
    {
        $locator = new ServiceLocator();

        assertType(User::class, $locator->get('user'));
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     */
    public function testReturnServiceWhenGetByComponentIdWithForceCreate(): void
    {
        $locator = new ServiceLocator();

        assertType(User::class, $locator->get('user'));
        assertType(User::class, $locator->get('user', false));
        assertType(User::class, $locator->get(User::class));
        assertType(User::class, $locator->get(User::class, false));
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     */
    public function testReturnServiceWhenGetFromApplicationByIdOrClassName(): void
    {
        $application = new Application();

        assertType(User::class, $application->get('user'));
        assertType(User::class, $application->get(User::class));
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     */
    public function testReturnServiceWhenGetFromModuleByIdOrClassName(): void
    {
        $module = new Module('test');

        assertType(User::class, $module->get('user'));
        assertType(User::class, $module->get(User::class));
    }
}

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
 * Type assertion fixture for {@see ServiceLocator::get()} return types in PHPStan analysis.
 *
 * Verifies type inference for component resolution by ID and class name across {@see ServiceLocator}, {@see Module},
 * and {@see Application}, including built-in Yii components and the `mixed` fallback for unknown identifiers.
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

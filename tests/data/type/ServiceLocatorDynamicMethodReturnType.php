<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\type;

use Yii;
use yii\base\InvalidConfigException;
use yii\base\Module;
use yii\di\ServiceLocator;
use yii\web\{Application, Request, Response, Session, User};
use yii2\extensions\phpstan\tests\support\stub\MyActiveRecord;

use function PHPStan\Testing\assertType;

/**
 * Type assertion fixture for {@see ServiceLocator::get()} return types in PHPStan analysis.
 *
 * Verifies type inference for component resolution by ID across {@see ServiceLocator}, {@see Module}, and
 * {@see Application}, including named arguments, nullability driven by `$throwException`, and the `object` type declared
 * by Yii for class names that aren't component IDs and for unknown, non-constant, and union identifiers.
 */
final class ServiceLocatorDynamicMethodReturnType
{
    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     */
    public function testReturnComponentClassWhenGetThrowExceptionIsUnpacked(): void
    {
        $locator = new ServiceLocator();

        assertType('yii\web\User|null', $locator->get('user', ...[false]));
        assertType('yii\web\User', $locator->get('user', ...[true]));
        assertType('yii\web\View|null', Yii::$app->get('view', ...[false]));
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     */
    public function testReturnObjectWhenGetByBuiltInClassNames(): void
    {
        $locator = new ServiceLocator();

        assertType('object', $locator->get(User::class));
        assertType('object', $locator->get(Request::class));
        assertType('object', $locator->get(Response::class));
        assertType('object', $locator->get(Session::class));
        assertType('object|null', $locator->get(User::class, false));
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     */
    public function testReturnObjectWhenGetByClassNameOfContainerService(): void
    {
        $locator = new ServiceLocator();

        $className = 'yii2\extensions\phpstan\tests\support\stub\MyActiveRecord';

        assertType('object', $locator->get(MyActiveRecord::class));
        assertType('object', $locator->get($className));
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     */
    public function testReturnObjectWhenGetIdIsUnpacked(): void
    {
        $locator = new ServiceLocator();

        assertType('object', $locator->get(...['user']));
    }

    public function testReturnObjectWhenGetWithNonConstantId(string $id): void
    {
        $locator = new ServiceLocator();

        assertType('object', $locator->get($id));
        assertType('object|null', $locator->get($id, false));
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     */
    public function testReturnObjectWhenGetWithUnionOfComponentIds(bool $useUser): void
    {
        $locator = new ServiceLocator();

        assertType('object', $locator->get($useUser ? 'user' : 'view'));
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     */
    public function testReturnObjectWhenGetWithUnknownId(): void
    {
        $locator = new ServiceLocator();

        assertType('object', $locator->get('unknown-component'));
        assertType('object|null', $locator->get('unknown-component', false));
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
    public function testReturnServiceWhenGetByComponentIdWithThrowException(bool $throwException): void
    {
        $locator = new ServiceLocator();

        assertType(User::class, $locator->get('user'));
        assertType(User::class, $locator->get('user', true));
        assertType('yii\web\User|null', $locator->get('user', false));
        assertType('yii\web\User|null', $locator->get('user', $throwException));
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     */
    public function testReturnServiceWhenGetByNamedArguments(): void
    {
        $locator = new ServiceLocator();

        assertType(User::class, $locator->get(id: 'user'));
        assertType(User::class, $locator->get(id: 'user', throwException: true));
        assertType('yii\web\User|null', $locator->get(id: 'user', throwException: false));
        assertType('yii\web\User|null', $locator->get(throwException: false, id: 'user'));
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     */
    public function testReturnServiceWhenGetFromApplicationById(): void
    {
        $application = new Application();

        assertType(User::class, $application->get('user'));
        assertType('object', $application->get(User::class));
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     */
    public function testReturnServiceWhenGetFromModuleById(): void
    {
        $module = new Module('test');

        assertType(User::class, $module->get('user'));
        assertType('object', $module->get(User::class));
    }
}

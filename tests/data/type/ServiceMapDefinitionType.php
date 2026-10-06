<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\type;

use Yii;
use yii\base\InvalidConfigException;
use yii\di\NotInstantiableException;

use function PHPStan\Testing\assertType;

/**
 * Type assertion fixture for components and container services declared by closures and class name strings.
 *
 * Verifies that a closure with a class return type resolves to that class, even when that class is an aliased container
 * ID, that a class name string resolves with or without a leading backslash, that components with an unknown class are
 * `object`, that a core component configured without a class keeps the type declared by the application, and that
 * unresolvable services, and services naming one, keep Yii's type.
 */
final class ServiceMapDefinitionType
{
    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     */
    public function testReturnApplicationDeclaredTypeForCoreComponentWithoutClass(): void
    {
        assertType('yii\web\Request', Yii::$app->request);
        assertType('object', Yii::$app->get('request'));
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     */
    public function testReturnClosureReturnClassForComponent(): void
    {
        assertType('yii\web\View', Yii::$app->closureView);
        assertType('yii\web\View', Yii::$app->get('closureView'));
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     * @throws NotInstantiableException if a class or service can't be instantiated.
     */
    public function testReturnClosureReturnClassForService(): void
    {
        assertType('SplStack', Yii::$container->get('typedService'));
        assertType('yii\caching\CacheInterface', Yii::$container->get('closureCache'));
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     */
    public function testReturnObjectForUnresolvableComponent(): void
    {
        assertType('object', Yii::$app->get('untypedClosure'));
        assertType('object', Yii::$app->untypedClosure);
        assertType('object', Yii::$app->get('instanceComponent'));
        assertType('object', Yii::$app->instanceComponent);
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     * @throws NotInstantiableException if a class or service can't be instantiated.
     */
    public function testReturnResolvedClassForServiceNamingAnotherId(): void
    {
        assertType('yii\web\View', Yii::$container->get('mailer.alias'));
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     */
    public function testReturnStringClassForComponent(): void
    {
        assertType('yii\web\View', Yii::$app->stringView);
        assertType('yii\web\View', Yii::$app->get('stringView'));
        assertType('yii\web\View', Yii::$app->prefixedView);
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     * @throws NotInstantiableException if a class or service can't be instantiated.
     */
    public function testReturnYiiDeclaredTypeForUnresolvableService(): void
    {
        assertType('object', Yii::$container->get('untypedService'));
        assertType('object', Yii::$container->get('instanceService'));
        assertType('object', Yii::$container->get('untypedServiceAlias'));
    }
}

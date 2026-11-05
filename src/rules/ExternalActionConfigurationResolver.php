<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\rules;

use PhpParser\Node\Expr;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\{DependencyTracker, Scope};
use PHPStan\Reflection\{ClassReflection, MethodReflection, ReflectionProvider};
use yii\base\Controller;
use yii2\extensions\phpstan\ServiceMap;

use function strtolower;

/**
 * Resolves the external action definitions of a {@see Controller::actions()} return statement whose assignment path
 * Yii makes certain.
 *
 * Requires the `actions()` method of a concrete, non-anonymous controller that inherits
 * {@see Controller::createAction()}, outside closures and traits, and a map with string action IDs proven as
 * {@see ObjectConfigurationReader::options()} describes; an empty ID is left out. Each array definition is resolved as
 * `Yii::createObject($definition, [$id, $this])` under {@see CreateObjectConstructionContract::externalAction()}.
 */
final class ExternalActionConfigurationResolver
{
    /**
     * Class through which {@see Controller::createAction()} calls `createObject()`.
     */
    private const string CREATE_OBJECT_CLASS = 'Yii';

    /**
     * Resolves the `Yii::createObject()` configuration of each definition.
     */
    private readonly CreateObjectConfigurationResolver $createObjectResolver;

    /**
     * @param ReflectionProvider $reflectionProvider Provides class reflection for the created actions.
     * @param ServiceMap $serviceMap Service and component map for Yii Application static analysis.
     */
    public function __construct(ReflectionProvider $reflectionProvider, ServiceMap $serviceMap)
    {
        $this->createObjectResolver = new CreateObjectConfigurationResolver($reflectionProvider, $serviceMap);
    }

    /**
     * Returns the proven definitions of the returned map, in map order; other definitions are left out.
     *
     * Records the declaration dependency of the controller and those of
     * {@see CreateObjectConfigurationResolver::resolveTypeOptions()}.
     *
     * @param Return_ $node Return statement to inspect.
     * @param DependencyTracker&Scope $scope Current PHPStan analysis scope.
     *
     * @return list<ExternalActionConfiguration>
     */
    public function resolve(Return_ $node, Scope $scope): array
    {
        $controller = self::controller($scope);

        if ($node->expr === null || $controller === null) {
            return [];
        }

        $definitions = ObjectConfigurationReader::options($node->expr, $node->getStartLine(), $scope);

        if ($definitions === null) {
            return [];
        }

        $contract = CreateObjectConstructionContract::externalAction();
        $configurations = [];

        foreach ($definitions as [$actionId, $line, $definition]) {
            // `createAction('')` looks up the default action instead, a runtime setting
            if ($actionId === '') {
                continue;
            }

            $options = $definition instanceof Expr
                ? ObjectConfigurationReader::options($definition, $line, $scope)
                : ObjectConfigurationReader::optionsFromType($definition, $line);

            $configuration = $options === null
                ? null
                : $this->createObjectResolver->resolveTypeOptions(
                    self::CREATE_OBJECT_CLASS,
                    $options,
                    $line,
                    $scope,
                    $contract,
                );

            if ($configuration !== null) {
                $configurations[] = new ExternalActionConfiguration(
                    $controller->getDisplayName(),
                    $actionId,
                    $configuration,
                );
            }
        }

        return $configurations;
    }

    /**
     * Returns the controller whose own `actions()` method contains the statement, when it is a concrete, non-anonymous
     * {@see Controller} subclass that inherits `createAction()` from {@see Controller}, outside closures and traits.
     *
     * @param DependencyTracker&Scope $scope Current PHPStan analysis scope.
     */
    private static function controller(Scope $scope): ClassReflection|null
    {
        if ($scope->isInAnonymousFunction() || $scope->isInTrait()) {
            return null;
        }

        $function = $scope->getFunction();
        $controller = $scope->getClassReflection();

        if (
            $function instanceof MethodReflection === false
            || $controller === null
            || strtolower($function->getName()) !== 'actions'
            || $controller->isClass() === false
            || $controller->isAbstract()
            || $controller->isAnonymous()
            || $controller->getName() === Controller::class
            || $controller->is(Controller::class) === false
        ) {
            return null;
        }

        $scope->trackClassDependency($controller->getName());

        return $controller->hasNativeMethod('createAction')
            && $controller->getNativeMethod('createAction')->getDeclaringClass()->getName() === Controller::class
            ? $controller
            : null;
    }
}

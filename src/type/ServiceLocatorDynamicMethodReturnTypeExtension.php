<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\type;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\{DependencyTracker, Scope};
use PHPStan\Reflection\{MethodReflection, ParametersAcceptorSelector};
use PHPStan\Type\{DynamicMethodReturnTypeExtension, ObjectType, ObjectWithoutClassType, Type, TypeCombinator};
use yii\di\ServiceLocator;
use yii2\extensions\phpstan\{ServiceMap, ServiceMapResultCacheValueExtension};

use function count;

/**
 * Resolves {@see ServiceLocator::get()} calls for component IDs configured in the Yii application to their class.
 *
 * A component whose class can't be determined is `object`. Only component IDs are resolved, since the service locator
 * neither reads the DI container nor treats an ID as a class name: any other ID throws at runtime. Nullability follows
 * the `$throwException` conditional return type declared by Yii, as resolved by PHPStan for the call. Unknown,
 * non-constant, and unpacked IDs are left to PHPStan. Applies to {@see ServiceLocator} subclasses, such as modules and
 * applications.
 *
 * {@see DynamicMethodReturnTypeExtension} for PHPStan dynamic return type extension contract.
 * {@see ServiceMap} for service and component map for Yii Application static analysis.
 */
final class ServiceLocatorDynamicMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    /**
     * @param ServiceMap $serviceMap Service and component map for Yii Application static analysis.
     */
    public function __construct(private readonly ServiceMap $serviceMap) {}

    /**
     * Returns the Yii service locator class supported by this extension.
     *
     * @phpstan-return class-string
     */
    public function getClass(): string
    {
        return ServiceLocator::class;
    }

    /**
     * Returns the type of the component with a single constant ID, made nullable when the declared return type allows
     * `null`.
     *
     * @param MethodReflection $methodReflection Reflection of the called method.
     * @param MethodCall $methodCall Method call with arguments already normalized by PHPStan.
     * @param DependencyTracker&Scope $scope Current PHPStan analysis scope.
     *
     * @return Type|null Component class, `object` for a component whose class can't be determined, or `null` to defer to
     * the return type declared by Yii.
     */
    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): Type|null {
        $args = $methodCall->getArgs();

        if (isset($args[0]) === false || $args[0]->unpack) {
            return null;
        }

        $constantStrings = $scope->getType($args[0]->value)->getConstantStrings();

        if (count($constantStrings) !== 1) {
            return null;
        }

        $id = $constantStrings[0]->getValue();

        $scope->trackValueDependency(
            ServiceMapResultCacheValueExtension::class,
            ServiceMapResultCacheValueExtension::componentKey($id),
        );

        $resolvedType = $this->resolveType($id);

        if ($resolvedType === null) {
            return null;
        }

        $declaredType = ParametersAcceptorSelector::selectFromArgs(
            $scope,
            $args,
            $methodReflection->getVariants(),
        )->getReturnType();

        return $declaredType->isNull()->no() ? $resolvedType : TypeCombinator::addNull($resolvedType);
    }

    /**
     * Returns whether the reflected method is {@see ServiceLocator::get()}.
     */
    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'get';
    }

    /**
     * Resolves a component ID to the configured component class.
     *
     * @param string $id Component ID passed to {@see ServiceLocator::get()}.
     *
     * @return Type|null Component class, `object` for a component whose class can't be determined, or `null` when no
     * component has the ID.
     */
    private function resolveType(string $id): Type|null
    {
        $componentClass = $this->serviceMap->getComponentClassById($id);

        if ($componentClass !== null) {
            return new ObjectType($componentClass);
        }

        return $this->serviceMap->isUnresolvedComponent($id) ? new ObjectWithoutClassType() : null;
    }
}

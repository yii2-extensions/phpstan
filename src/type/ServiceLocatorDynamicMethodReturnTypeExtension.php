<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\type;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\{DependencyTracker, Scope};
use PHPStan\Reflection\{MethodReflection, ParametersAcceptorSelector, ReflectionProvider};
use PHPStan\Type\{DynamicMethodReturnTypeExtension, ObjectType, ObjectWithoutClassType, Type, TypeCombinator};
use yii\di\ServiceLocator;
use yii2\extensions\phpstan\{ServiceMap, ServiceMapResultCacheValueExtension};

use function count;

/**
 * Resolves {@see ServiceLocator::get()} calls for component IDs, service IDs, and class names to the matching class.
 *
 * A component or service whose class can't be determined is `object`, also under a class name ID. Nullability follows
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
     * @param ReflectionProvider $reflectionProvider Reflection provider used to recognize class-name IDs.
     * @param ServiceMap $serviceMap Service and component map for Yii Application static analysis.
     */
    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
        private readonly ServiceMap $serviceMap,
    ) {}

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
     * Returns the type resolved for a single constant ID, made nullable when the declared return type allows `null`.
     *
     * @param MethodReflection $methodReflection Reflection of the called method.
     * @param MethodCall $methodCall Method call with arguments already normalized by PHPStan.
     * @param DependencyTracker&Scope $scope Current PHPStan analysis scope.
     *
     * @return Type|null Resolved class, `object` for an ID whose class can't be determined, or `null` to defer to the
     * return type declared by Yii.
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
        $scope->trackValueDependency(
            ServiceMapResultCacheValueExtension::class,
            ServiceMapResultCacheValueExtension::serviceKey($id),
        );

        $resolvedType = $this->resolveType($id, $scope);

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
     * Resolves an ID to a component class, a service class, or an existing class with the same name.
     *
     * A component or service whose class can't be determined resolves to `object` before any later candidate, since
     * Yii returns whatever its definition yields.
     *
     * @param string $id Component ID, service ID, or class name passed to {@see ServiceLocator::get()}.
     * @param DependencyTracker&Scope $scope Scope recording a dependency on the class named by the ID, when the ID is
     * used as a class.
     *
     * @return Type|null Resolved class, `object` for an ID whose class can't be determined, or `null` when the ID is
     * unknown.
     */
    private function resolveType(string $id, Scope $scope): Type|null
    {
        $componentClass = $this->serviceMap->getComponentClassById($id);

        if ($componentClass !== null) {
            return new ObjectType($componentClass);
        }

        if ($this->serviceMap->isUnresolvedComponent($id)) {
            return new ObjectWithoutClassType();
        }

        $serviceClass = $this->serviceMap->getServiceById($id);

        if ($serviceClass !== null) {
            return new ObjectType($serviceClass);
        }

        if ($this->serviceMap->isUnresolvedService($id)) {
            return new ObjectWithoutClassType();
        }

        // the fallback depends on whether the class exists, which a new class file can change
        $scope->trackClassDependency($id);

        return $this->reflectionProvider->hasClass($id) ? new ObjectType($id) : null;
    }
}

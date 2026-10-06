<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\type;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\{DependencyTracker, Scope};
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\{DynamicMethodReturnTypeExtension, ObjectType, Type};
use yii\di\Container;
use yii2\extensions\phpstan\{ServiceMap, ServiceMapResultCacheValueExtension};

use function count;

/**
 * Resolves {@see Container::get()} calls for service IDs defined in the Yii configuration to their configured class.
 *
 * Class-string, unknown, non-constant, and unpacked IDs are left to PHPStan, which applies the `@template` conditional
 * return type declared by Yii.
 *
 * {@see DynamicMethodReturnTypeExtension} for PHPStan dynamic return type extension contract.
 * {@see ServiceMap} for service and component map for Yii Application static analysis.
 */
final class ContainerDynamicMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    /**
     * @param ServiceMap $serviceMap Service and component map for Yii Application static analysis.
     */
    public function __construct(private readonly ServiceMap $serviceMap) {}

    /**
     * Returns the Yii DI container class supported by this extension.
     *
     * @phpstan-return class-string
     */
    public function getClass(): string
    {
        return Container::class;
    }

    /**
     * Returns the class configured for a single constant service ID.
     *
     * @param MethodReflection $methodReflection Reflection of the called method.
     * @param MethodCall $methodCall Method call with arguments already normalized by PHPStan.
     * @param Scope&DependencyTracker $scope Current PHPStan analysis scope.
     *
     * @return Type|null Configured service class, or `null` to defer to the return type declared by Yii.
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
            ServiceMapResultCacheValueExtension::serviceKey($id),
        );

        $serviceClass = $this->serviceMap->getServiceById($id);

        return $serviceClass !== null ? new ObjectType($serviceClass) : null;
    }

    /**
     * Returns whether the reflected method is {@see Container::get()}.
     */
    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'get';
    }
}

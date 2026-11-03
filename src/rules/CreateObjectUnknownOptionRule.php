<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\rules;

use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\{DependencyTracker, Scope};
use PHPStan\Reflection\{ClassReflection, ReflectionProvider};
use PHPStan\Rules\{IdentifierRuleError, Rule, RuleErrorBuilder};
use yii\base\{BaseObject, Component};
use yii\BaseYii;
use yii2\extensions\phpstan\exception\Message;
use yii2\extensions\phpstan\{ServiceMap, ServiceMapResultCacheValueExtension};

use function in_array;
use function str_starts_with;

/**
 * Reports {@see BaseYii::createObject()} options that Yii can't assign to the created object.
 *
 * For a {@see Component} subclass, `on ...` and `as ...` options are never reported, and other options only when no
 * behavior can receive them.
 *
 * {@see Rule} for PHPStan rule contract.
 * {@see CreateObjectConfigurationResolver} for the configurations and classes the rule analyses.
 *
 * @implements Rule<StaticCall>
 */
final class CreateObjectUnknownOptionRule implements Rule
{
    /**
     * Option name prefix with which `Component::__set()` attaches a behavior.
     */
    private const string BEHAVIOR_PREFIX = 'as ';

    /**
     * Option name prefix with which `Component::__set()` attaches an event handler.
     */
    private const string EVENT_PREFIX = 'on ';

    /**
     * Error identifier reported by this rule.
     */
    private const string IDENTIFIER = 'yii2.createObject.unknownOption';

    /**
     * Resolves the configurations whose assignment path is certain.
     */
    private readonly CreateObjectConfigurationResolver $configurationResolver;

    /**
     * Initializes the rule with class reflection and the analysis configuration.
     *
     * @param ReflectionProvider $reflectionProvider Provides class reflection for the created class.
     * @param ServiceMap $serviceMap Service and component map for Yii Application static analysis.
     */
    public function __construct(ReflectionProvider $reflectionProvider, private readonly ServiceMap $serviceMap)
    {
        $this->configurationResolver = new CreateObjectConfigurationResolver($reflectionProvider, $serviceMap);
    }

    public function getNodeType(): string
    {
        return StaticCall::class;
    }

    /**
     * Returns one error per distinct option that the created class can't assign.
     *
     * @param StaticCall $node Static call to inspect.
     * @param DependencyTracker&Scope $scope Current PHPStan analysis scope.
     *
     * @return list<IdentifierRuleError> Errors reported at the first key of each unknown option, or at the call line
     * when the configuration doesn't locate the key.
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $configuration = $this->configurationResolver->resolve($node, $scope);

        if ($configuration === null) {
            return [];
        }

        $classReflection = $configuration->classReflection;

        $isComponent = self::isDeclaredBy($classReflection, '__set', Component::class);
        $withoutBehaviors = $isComponent === false || $this->isWithoutBehaviors($configuration, $scope);

        $codeRan = false;
        $errors = [];

        foreach ($configuration->options as [$name, $line]) {
            $isUnknown = $isComponent
                ? $withoutBehaviors && $codeRan === false && self::isUnknownComponentOption($classReflection, $name)
                : self::isUnknownOption($classReflection, $name);

            $codeRan = $codeRan || ($isComponent && self::mayRunCode($classReflection, $name));

            if ($isUnknown) {
                $errors[] = RuleErrorBuilder::message(
                    Message::CREATE_OBJECT_UNKNOWN_OPTION->getMessage(
                        $configuration->calledClass,
                        $classReflection->getDisplayName(),
                        $name,
                    ),
                )
                    ->identifier(self::IDENTIFIER)
                    ->line($line)
                    ->build();
            }
        }

        return $errors;
    }

    /**
     * Returns whether the native method of the class, possibly inherited, is declared by the given class.
     */
    private static function isDeclaredBy(
        ClassReflection $classReflection,
        string $methodName,
        string $declaringClass,
    ): bool {
        return $classReflection->hasNativeMethod($methodName)
            && $classReflection->getNativeMethod($methodName)->getDeclaringClass()->getName() === $declaringClass;
    }

    /**
     * Returns whether {@see isUnknownOption()} holds for a name that isn't an `on ...` event, compared case-sensitively
     * as Yii does.
     */
    private static function isUnknownComponentOption(ClassReflection $classReflection, string $name): bool
    {
        return str_starts_with($name, self::EVENT_PREFIX) === false && self::isUnknownOption($classReflection, $name);
    }

    /**
     * Returns whether assigning the option from outside the class reaches `BaseObject::__set()` and throws
     * `yii\base\UnknownPropertyException`.
     *
     * A public property is assigned directly. A protected or private property is inaccessible from
     * {@see BaseYii::configure()}, so `__set()` handles it like an absent one. `__set()` calls any `set` method,
     * whatever its visibility, and rejects a name with only a `get` method as read-only; both are left alone, as are
     * empty names and static, read-only and hooked properties.
     */
    private static function isUnknownOption(ClassReflection $classReflection, string $name): bool
    {
        if ($name === '' || in_array($name, CreateObjectConfigurationResolver::RESERVED_OPTIONS, true)) {
            return false;
        }

        if ($classReflection->hasNativeProperty($name)) {
            $property = $classReflection->getNativeProperty($name);

            if ($property->isPublic() || $property->isStatic() || $property->isReadOnly() || $property->isHooked()) {
                return false;
            }
        }

        return $classReflection->hasNativeMethod('set' . $name) === false
            && $classReflection->hasNativeMethod('get' . $name) === false;
    }

    /**
     * Returns whether no behavior is attached when Yii configures the {@see Component} subclass: `behaviors()` and
     * `ensureBehaviors()` inherited from {@see Component}, no `as ...` option and no map entry for any ancestor.
     *
     * Records the `behaviors` value dependency of every ancestor before deciding.
     *
     * @param DependencyTracker&Scope $scope Current PHPStan analysis scope.
     */
    private function isWithoutBehaviors(CreateObjectConfiguration $configuration, Scope $scope): bool
    {
        $classReflection = $configuration->classReflection;

        if (
            self::isDeclaredBy($classReflection, 'behaviors', Component::class) === false
            || self::isDeclaredBy($classReflection, 'ensureBehaviors', Component::class) === false
        ) {
            return false;
        }

        foreach ($configuration->options as [$name]) {
            if (str_starts_with($name, self::BEHAVIOR_PREFIX)) {
                return false;
            }
        }

        $withoutBehaviors = true;

        foreach ($classReflection->getAncestors() as $ancestor) {
            $scope->trackValueDependency(
                ServiceMapResultCacheValueExtension::class,
                ServiceMapResultCacheValueExtension::behaviorsKey($ancestor->getName()),
            );

            if ($this->serviceMap->getBehaviorsByClassName($ancestor->getName()) !== []) {
                $withoutBehaviors = false;
            }
        }

        return $withoutBehaviors;
    }

    /**
     * Returns whether assigning the option may run application code that attaches a behavior for a later option: a set
     * hook, a `set` method of any visibility, or an `on ...` event with an overridden `on()`.
     */
    private static function mayRunCode(ClassReflection $classReflection, string $name): bool
    {
        if (in_array($name, CreateObjectConfigurationResolver::RESERVED_OPTIONS, true)) {
            return false;
        }

        if ($classReflection->hasNativeProperty($name)) {
            $property = $classReflection->getNativeProperty($name);

            if ($property->isPublic() && $property->isStatic() === false) {
                return $property->isHooked();
            }
        }

        if ($classReflection->hasNativeMethod('set' . $name)) {
            return true;
        }

        return str_starts_with($name, self::EVENT_PREFIX)
            && self::isDeclaredBy($classReflection, 'on', Component::class) === false;
    }
}

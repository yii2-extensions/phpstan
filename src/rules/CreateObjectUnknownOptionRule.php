<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\rules;

use PhpParser\Node;
use PhpParser\Node\{ArrayItem, Expr, Identifier, Name};
use PhpParser\Node\Expr\{Array_, ClassConstFetch, StaticCall};
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\{DependencyTracker, Scope};
use PHPStan\Reflection\{ClassReflection, ReflectionProvider};
use PHPStan\Rules\{IdentifierRuleError, Rule, RuleErrorBuilder};
use yii\base\BaseObject;
use yii\BaseYii;
use yii2\extensions\phpstan\exception\Message;
use yii2\extensions\phpstan\{ServiceMap, ServiceMapResultCacheValueExtension};

use function count;
use function in_array;
use function ltrim;

/**
 * Reports literal {@see BaseYii::createObject()} options that Yii can't assign to the created object.
 *
 * Applies only to a literal configuration of a class without container binding that inherits the constructor and
 * `__set()` of {@see BaseObject}; any other call is left alone.
 *
 * {@see Rule} for PHPStan rule contract.
 * {@see ServiceMap} for service and component map for Yii Application static analysis.
 *
 * @implements Rule<StaticCall>
 */
final class CreateObjectUnknownOptionRule implements Rule
{
    /**
     * Error identifier reported by this rule.
     */
    private const string IDENTIFIER = 'yii2.createObject.unknownOption';

    /**
     * Parameters of {@see BaseYii::createObject()} in declaration order.
     */
    private const array PARAMETERS = ['type', 'params'];

    /**
     * Options that {@see BaseYii::createObject()} removes before configuring the object.
     */
    private const array RESERVED_OPTIONS = ['__class', 'class'];

    /**
     * Initializes the rule with class reflection and the analysis configuration.
     *
     * @param ReflectionProvider $reflectionProvider Provides class reflection for the created class.
     * @param ServiceMap $serviceMap Service and component map for Yii Application static analysis.
     */
    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
        private readonly ServiceMap $serviceMap,
    ) {}

    public function getNodeType(): string
    {
        return StaticCall::class;
    }

    /**
     * Returns one error per distinct literal option that the created class can't assign.
     *
     * @param StaticCall $node Static call to inspect.
     * @param DependencyTracker&Scope $scope Current PHPStan analysis scope.
     *
     * @return list<IdentifierRuleError> Errors reported at the line of the first occurrence of each unknown option.
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $calledClass = $this->resolveCreateObjectClass($node, $scope);

        if ($calledClass === null) {
            return [];
        }

        $options = self::literalOptions($node);

        if ($options === null) {
            return [];
        }

        $id = self::targetClassId($options, $scope);

        if ($id === null) {
            return [];
        }

        $className = ltrim($id, '\\');

        $scope->trackValueDependency(
            ServiceMapResultCacheValueExtension::class,
            ServiceMapResultCacheValueExtension::serviceKey($id),
        );
        $scope->trackClassDependency($className);

        if ($this->serviceMap->getServiceById($id) !== null || $this->serviceMap->isUnresolvedService($id)) {
            return [];
        }

        $classReflection = $this->resolveConfigurableClass($className);

        if ($classReflection === null) {
            return [];
        }

        $errors = [];

        foreach ($options as [$name, $item]) {
            if (self::isUnknownOption($classReflection, $name)) {
                $errors[] = RuleErrorBuilder::message(
                    Message::CREATE_OBJECT_UNKNOWN_OPTION->getMessage(
                        $calledClass,
                        $classReflection->getDisplayName(),
                        $name,
                    ),
                )
                    ->identifier(self::IDENTIFIER)
                    ->line($item->getStartLine())
                    ->build();
            }
        }

        return $errors;
    }

    /**
     * Returns whether the class itself declares the method as {@see BaseObject} does.
     */
    private static function isDeclaredByBaseObject(ClassReflection $classReflection, string $methodName): bool
    {
        return $classReflection->hasNativeMethod($methodName)
            && $classReflection->getNativeMethod($methodName)->getDeclaringClass()->getName() === BaseObject::class;
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
        if ($name === '' || in_array($name, self::RESERVED_OPTIONS, true)) {
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
     * Returns the literal class name an expression holds, as written for a string and resolved for `::class`.
     */
    private static function literalClassName(Expr $expr, Scope $scope): string|null
    {
        if ($expr instanceof String_) {
            return $expr->value === '' ? null : $expr->value;
        }

        if (
            $expr instanceof ClassConstFetch
            && $expr->class instanceof Name
            && $expr->class->toLowerString() !== 'static'
            && $expr->name instanceof Identifier
            && $expr->name->toLowerString() === 'class'
        ) {
            return $scope->resolveName($expr->class);
        }

        return null;
    }

    /**
     * Returns the distinct literal options of the configuration array passed as `$type`, in first-occurrence order, each
     * with its first item and its last value, as PHP builds the array.
     *
     * @return list<array{string, ArrayItem, Expr}>|null Name, first item and effective value of each option, or `null`
     * when the arguments or the configuration aren't fully literal: unpacking, unknown or repeated named arguments, extra
     * or non-empty `$params`, or a key that isn't a literal `string`.
     */
    private static function literalOptions(StaticCall $node): array|null
    {
        $arguments = [];

        foreach ($node->getArgs() as $position => $arg) {
            if ($arg->unpack) {
                return null;
            }

            $name = $arg->name === null ? self::PARAMETERS[$position] ?? null : $arg->name->toString();

            if ($name === null || in_array($name, self::PARAMETERS, true) === false || isset($arguments[$name])) {
                return null;
            }

            $arguments[$name] = $arg->value;
        }

        $type = $arguments['type'] ?? null;
        $params = $arguments['params'] ?? null;

        if (
            $type instanceof Array_ === false
            || ($params !== null && ($params instanceof Array_ === false || $params->items !== []))
        ) {
            return null;
        }

        $options = [];
        $positions = [];

        foreach ($type->items as $item) {
            if ($item->unpack || $item->key instanceof String_ === false) {
                return null;
            }

            $name = $item->key->value;
            $position = $positions[$name] ?? null;

            if ($position !== null && isset($options[$position])) {
                $options[$position] = [$options[$position][0], $options[$position][1], $item->value];
            } else {
                $positions[$name] = count($options);
                $options[] = [$name, $item, $item->value];
            }
        }

        return $options;
    }

    /**
     * Returns the effective value of an option, or `null` when the configuration doesn't contain it.
     *
     * @param list<array{string, ArrayItem, Expr}> $options Literal options of the configuration.
     */
    private static function optionValue(array $options, string $name): Expr|null
    {
        foreach ($options as [$optionName, , $value]) {
            if ($optionName === $name) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Returns the class when Yii assigns every option through `BaseObject::__set()` semantics: an instantiable class
     * whose constructor and `__set()` are both declared by {@see BaseObject}, which makes it that class or a subclass.
     */
    private function resolveConfigurableClass(string $className): ClassReflection|null
    {
        if ($this->reflectionProvider->hasClass($className) === false) {
            return null;
        }

        $classReflection = $this->reflectionProvider->getClass($className);

        if ($classReflection->isClass() === false || $classReflection->isAbstract()) {
            return null;
        }

        return self::isDeclaredByBaseObject($classReflection, '__construct')
            && self::isDeclaredByBaseObject($classReflection, '__set')
            ? $classReflection
            : null;
    }

    /**
     * Returns the called class name when the call is `createObject()` as declared by {@see BaseYii}, through `Yii`,
     * `BaseYii`, an imported alias of either, or a subclass that doesn't override it.
     */
    private function resolveCreateObjectClass(StaticCall $node, Scope $scope): string|null
    {
        if (
            $node->class instanceof Name === false
            || $node->class->toLowerString() === 'static'
            || $node->name instanceof Identifier === false
            || $node->name->toLowerString() !== 'createobject'
            || $node->isFirstClassCallable()
        ) {
            return null;
        }

        $className = $scope->resolveName($node->class);

        if ($this->reflectionProvider->hasClass($className) === false) {
            return null;
        }

        $classReflection = $this->reflectionProvider->getClass($className);

        return $classReflection->hasNativeMethod('createObject')
            && $classReflection->getNativeMethod('createObject')->getDeclaringClass()->getName() === BaseYii::class
            ? $classReflection->getDisplayName()
            : null;
    }

    /**
     * Returns the container ID {@see BaseYii::createObject()} passes to the container: the literal `__class` value when
     * the key is present, else the literal `class` value.
     *
     * @param list<array{string, ArrayItem, Expr}> $options Literal options of the configuration.
     *
     * @return string|null Class name as written, or `null` when the selected value isn't a literal class name, no class
     * key is present, or `__construct()` supplies constructor arguments.
     */
    private static function targetClassId(array $options, Scope $scope): string|null
    {
        if (self::optionValue($options, '__construct()') !== null) {
            return null;
        }

        $selected = self::optionValue($options, '__class') ?? self::optionValue($options, 'class');

        return $selected === null ? null : self::literalClassName($selected, $scope);
    }
}

<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\rules;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\{Array_, MethodCall};
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\{DependencyTracker, Scope};
use PHPStan\Reflection\{ClassReflection, MethodReflection, ReflectionProvider};
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\{Type, VerbosityLevel};
use yii\base\{BaseObject, Behavior, Component};
use yii2\extensions\phpstan\{ServiceMap, ServiceMapResultCacheValueExtension};

use function count;
use function in_array;
use function is_int;
use function is_string;
use function ltrim;
use function max;
use function strtolower;

/**
 * Finds behavior definitions that {@see Component} can't attach, in `behaviors()` returns and in `attachBehavior()` and
 * `attachBehaviors()` arguments.
 *
 * Yii passes each definition that isn't a {@see Behavior} instance to `Yii::createObject()` and calls `attach()` on the
 * result without a type check. A definition is reported only when that is certain to fail:
 *
 * - its class string or configuration class resolves, through the container definitions, to a concrete class that is
 *   not a {@see Behavior}, has no public `attach()` (static or not) and has no `__call()` other than the one declared
 *   by {@see BaseObject}, which always throws;
 * - it is a configuration array with no non-null `__class` or `class` element that `Yii::createObject()` can't invoke
 *   as a callable, unless the declared element type of the `behaviors()` return already rejects it.
 *
 * Definitions are read from literal lists, with their item lines, or from lists proven as one constant array by native
 * types, at the return or argument line. Container factories, singletons, unknown classes, closures, invocable objects,
 * callable arrays, unions, unpacked or dynamic lists, abstract classes, interfaces and {@see Component} results stand
 * aside, as do owners overriding `ensureBehaviors()` and receivers overriding the attach method.
 */
final class BehaviorDefinitionResolver
{
    /**
     * Methods whose arguments the resolver reads, in lower case, with the parameter that holds the definitions.
     */
    private const array ATTACH_METHODS = ['attachbehavior' => 'behavior', 'attachbehaviors' => 'behaviors'];

    /**
     * @param ReflectionProvider $reflectionProvider Provides class reflection for owners and resolved classes.
     * @param ServiceMap $serviceMap Service and component map for Yii Application static analysis.
     */
    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
        private readonly ServiceMap $serviceMap,
    ) {}

    /**
     * Returns the definitions of an `attachBehavior()` or `attachBehaviors()` call that can't be attached.
     *
     * Records the `construction:<id>` value dependency and the class dependency of each resolved definition.
     *
     * @param MethodCall $node Method call to inspect.
     * @param DependencyTracker&Scope $scope Current PHPStan analysis scope.
     *
     * @return list<InvalidBehaviorDefinition>
     */
    public function resolveAttachCall(MethodCall $node, Scope $scope): array
    {
        if ($node->name instanceof Identifier === false || $node->isFirstClassCallable()) {
            return [];
        }

        $method = strtolower($node->name->toString());

        $parameter = self::ATTACH_METHODS[$method] ?? null;

        if ($parameter === null) {
            return [];
        }

        $receiverType = $scope->getType($node->var);
        $receivers = $receiverType->getObjectClassReflections();

        if ($receivers === []) {
            return [];
        }

        foreach ($receivers as $receiver) {
            if (
                $receiver->is(Component::class) === false
                || self::isDeclaredByComponent($receiver, $method) === false
            ) {
                return [];
            }
        }

        $arguments = self::arguments($node, $method === 'attachbehavior' ? ['name', 'behavior'] : ['behaviors']);

        $definitions = $arguments[$parameter] ?? null;

        if ($definitions === null) {
            return [];
        }

        $owner = $receiverType->describe(VerbosityLevel::typeOnly());

        if ($method === 'attachbehaviors') {
            return $this->resolveList($definitions, $node->getStartLine(), $owner, 'attachBehaviors', null, $scope);
        }

        $nameType = isset($arguments['name']) ? $scope->getNativeType($arguments['name']) : null;
        $names = $nameType?->getConstantScalarValues() ?? [];
        $name = count($names) === 1 && (is_int($names[0]) || is_string($names[0])) ? $names[0] : null;

        $invalid = $this->resolveDefinition(
            $definitions,
            $definitions->getStartLine(),
            $owner,
            'attachBehavior',
            $name,
            null,
            $scope,
        );

        return $invalid === null ? [] : [$invalid];
    }

    /**
     * Returns the definitions of a `behaviors()` return statement that can't be attached.
     *
     * Records the declaration dependency of the owner, and the `construction:<id>` value dependency and the class
     * dependency of each resolved definition.
     *
     * @param Return_ $node Return statement to inspect.
     * @param DependencyTracker&Scope $scope Current PHPStan analysis scope.
     *
     * @return list<InvalidBehaviorDefinition>
     */
    public function resolveBehaviorsReturn(Return_ $node, Scope $scope): array
    {
        if ($node->expr === null || $scope->isInAnonymousFunction() || $scope->isInTrait()) {
            return [];
        }

        $function = $scope->getFunction();
        $owner = $scope->getClassReflection();

        if (
            $function instanceof MethodReflection === false
            || $owner === null
            || strtolower($function->getName()) !== 'behaviors'
            || $owner->isClass() === false
            || $owner->isAnonymous()
            || $owner->getName() === Component::class
            || $owner->is(Component::class) === false
        ) {
            return [];
        }

        $scope->trackClassDependency($owner->getName());

        if (self::isDeclaredByComponent($owner, 'ensureBehaviors') === false) {
            return [];
        }

        return $this->resolveList(
            $node->expr,
            $node->getStartLine(),
            $owner->getDisplayName(),
            'behaviors',
            $function->getReturnType()->getIterableValueType(),
            $scope,
        );
    }

    /**
     * Returns the named arguments of a call by parameter name, or `null` when an argument is unpacked or unknown.
     *
     * @param list<string> $parameters Parameter names in declaration order.
     *
     * @return array<string, Expr>|null
     */
    private static function arguments(MethodCall $node, array $parameters): array|null
    {
        $arguments = [];

        foreach ($node->getArgs() as $position => $arg) {
            $name = $arg->name === null ? $parameters[$position] ?? null : $arg->name->toString();

            if ($arg->unpack || $name === null || in_array($name, $parameters, true) === false) {
                return null;
            }

            $arguments[$name] = $arg->value;
        }

        return $arguments;
    }

    /**
     * Returns whether a class fails to attach: not a {@see Behavior}, concrete, without public `attach()` and without a
     * `__call()` that could provide it.
     */
    private static function cannotAttach(ClassReflection $class): bool
    {
        if ($class->is(Behavior::class) || $class->isClass() === false || $class->isAbstract()) {
            return false;
        }

        if ($class->hasNativeMethod('attach') && $class->getNativeMethod('attach')->isPublic()) {
            return false;
        }

        // `BaseObject::__call()` always throws; any other `__call()`, such as `Component`'s, may provide `attach()`
        return $class->hasNativeMethod('__call') === false
            || $class->getNativeMethod('__call')->getDeclaringClass()->getName() === BaseObject::class;
    }

    /**
     * Returns the class that `Yii::createObject()` reads from a configuration array, `false` when there is none, or
     * `null` when it isn't certain.
     *
     * Follows `createObject()`: a non-null `__class` wins over `class`; an element whose value is `null` doesn't count.
     */
    private static function configurationClass(ConstantArrayType $array): string|false|null
    {
        $values = [];

        foreach ($array->getKeyTypes() as $keyType) {
            $values[$keyType->getValue()] = $array->getOffsetValueType($keyType);
        }

        foreach (['__class', 'class'] as $key) {
            $value = $values[$key] ?? null;

            if ($value === null || $value->isNull()->yes()) {
                continue;
            }

            if ($value->isNull()->no() === false) {
                return null;
            }

            $class = ObjectConfigurationReader::constantString($value);

            return $class === null || $class === '' ? null : $class;
        }

        return false;
    }

    /**
     * Returns the entries of a definition list as `[name, line, definition]`, in list order, or `null` when the list
     * isn't certain.
     *
     * Literal items keep their lines, with PHP's key normalization and last-duplicate semantics; other lists must be
     * one constant array and use the given line.
     *
     * @return list<array{int|string, int, Expr|Type}>|null
     */
    private static function entries(Expr $list, int $line, Scope $scope): array|null
    {
        if ($list instanceof Array_ === false) {
            return self::typeEntries($scope->getNativeType($list), $line);
        }

        $entries = [];
        $positions = [];
        $next = 0;

        foreach ($list->items as $item) {
            $key = $item->key === null ? $next : self::literalKey($scope->getNativeType($item->key));

            if ($item->unpack || $key === null) {
                return self::typeEntries($scope->getNativeType($list), $line);
            }

            if (is_int($key)) {
                $next = max($next, $key + 1);
            }

            $entry = [$key, $item->getStartLine(), $item->value];

            if (isset($positions[$key])) {
                $entries[$positions[$key]] = $entry;
            } else {
                $positions[$key] = count($entries);
                $entries[] = $entry;
            }
        }

        return $entries;
    }

    /**
     * Returns whether `is_callable($definition, true)` may accept a configuration array: exactly the keys `0` and `1`,
     * with a possible string method name and a possible class name or object.
     */
    private static function isCallableShape(ConstantArrayType $array): bool
    {
        $values = [];

        foreach ($array->getKeyTypes() as $keyType) {
            $values[$keyType->getValue()] = $array->getOffsetValueType($keyType);
        }

        if (count($values) !== 2 || isset($values[0], $values[1]) === false) {
            return false;
        }

        return $values[1]->isString()->no() === false
            && ($values[0]->isString()->no() === false || $values[0]->isObject()->no() === false);
    }

    /**
     * Returns whether {@see Component} declares the method that the class inherits.
     */
    private static function isDeclaredByComponent(ClassReflection $class, string $method): bool
    {
        return $class->hasNativeMethod($method)
            && $class->getNativeMethod($method)->getDeclaringClass()->getName() === Component::class;
    }

    /**
     * Returns the array key that PHP stores for a literal key, or `null` when it isn't one `int` or `string` constant.
     */
    private static function literalKey(Type $type): int|string|null
    {
        $values = $type->getConstantScalarValues();

        if (count($values) !== 1 || (is_int($values[0]) === false && is_string($values[0]) === false)) {
            return null;
        }

        $key = $values[0];

        return is_string($key) && (string) (int) $key === $key ? (int) $key : $key;
    }

    /**
     * Returns the class that a definition resolves to through the container, or `null` when it isn't known.
     *
     * Records the `construction:<id>` value dependency and, for a resolved class, its class dependency.
     *
     * @param DependencyTracker&Scope $scope Current PHPStan analysis scope.
     */
    private function resolvedClass(string $id, Scope $scope): ClassReflection|null
    {
        $scope->trackValueDependency(
            ServiceMapResultCacheValueExtension::class,
            ServiceMapResultCacheValueExtension::constructionKey($id),
        );

        $class = $this->serviceMap->getConfiguredClassById($id);

        if ($class === null) {
            return null;
        }

        $scope->trackClassDependency($class);

        return $this->reflectionProvider->hasClass($class) ? $this->reflectionProvider->getClass($class) : null;
    }

    /**
     * Returns the definition when it can't be attached, otherwise `null`.
     *
     * @param Type|null $declaredType Declared element type of the `behaviors()` return, or `null` for attach calls.
     * @param DependencyTracker&Scope $scope Current PHPStan analysis scope.
     */
    private function resolveDefinition(
        Expr|Type $definition,
        int $line,
        string $owner,
        string $method,
        int|string|null $name,
        Type|null $declaredType,
        Scope $scope,
    ): InvalidBehaviorDefinition|null {
        $type = $definition instanceof Expr ? $scope->getNativeType($definition) : $definition;

        $id = ObjectConfigurationReader::constantString($type);

        if ($id === null) {
            $array = ObjectConfigurationReader::constantArray($type);

            if ($array === null || self::isCallableShape($array)) {
                return null;
            }

            $id = self::configurationClass($array);

            if ($id === false) {
                // the declared `behaviors()` element type already makes PHPStan report the array
                return $declaredType === null || $declaredType->isSuperTypeOf($array)->yes()
                    ? new InvalidBehaviorDefinition($owner, $method, $name, $line, null, null)
                    : null;
            }
        }

        if ($id === null || $id === '') {
            return null;
        }

        $class = $this->resolvedClass($id, $scope);

        return $class !== null && self::cannotAttach($class)
            ? new InvalidBehaviorDefinition($owner, $method, $name, $line, ltrim($id, '\\'), $class->getDisplayName())
            : null;
    }

    /**
     * Returns the definitions of a list that can't be attached, in list order.
     *
     * @param Type|null $declaredType Declared element type of the `behaviors()` return, or `null` for attach calls.
     * @param DependencyTracker&Scope $scope Current PHPStan analysis scope.
     *
     * @return list<InvalidBehaviorDefinition>
     */
    private function resolveList(
        Expr $list,
        int $line,
        string $owner,
        string $method,
        Type|null $declaredType,
        Scope $scope,
    ): array {
        $invalid = [];

        foreach (self::entries($list, $line, $scope) ?? [] as [$name, $entryLine, $definition]) {
            $definitionError = $this->resolveDefinition(
                $definition,
                $entryLine,
                $owner,
                $method,
                $name,
                $declaredType,
                $scope,
            );

            if ($definitionError !== null) {
                $invalid[] = $definitionError;
            }
        }

        return $invalid;
    }

    /**
     * Returns the entries of a list proven as one constant array, all at the given line, or `null` otherwise.
     *
     * @return list<array{int|string, int, Type}>|null
     */
    private static function typeEntries(Type $type, int $line): array|null
    {
        $array = ObjectConfigurationReader::constantArray($type);

        if ($array === null) {
            return null;
        }

        $entries = [];

        foreach ($array->getKeyTypes() as $keyType) {
            $entries[] = [$keyType->getValue(), $line, $array->getOffsetValueType($keyType)];
        }

        return $entries;
    }
}

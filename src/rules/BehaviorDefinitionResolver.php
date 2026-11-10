<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\rules;

use ArrayAccess;
use Closure;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\{Array_, MethodCall, StaticCall};
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\{DependencyTracker, Scope};
use PHPStan\Reflection\{ClassReflection, MethodReflection, ReflectionProvider};
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\{ObjectType, Type, VerbosityLevel};
use yii\base\{BaseObject, Behavior, Component};
use yii2\extensions\phpstan\{ServiceMap, ServiceMapResultCacheValueExtension};

use function count;
use function in_array;
use function is_int;
use function is_string;
use function ltrim;
use function str_starts_with;
use function strtolower;

/**
 * Finds behavior definitions that {@see Component} can't attach, and behavior configuration options that the created
 * {@see Behavior} can't assign, in `behaviors()` returns, `attachBehavior()` and `attachBehaviors()` arguments and `as`
 * options of `Yii::createObject()` configurations.
 *
 * A definition or option is reported only when Yii certainly fails on it; dynamic, uncertain or overridden paths stand
 * aside. Options are classified by {@see ObjectOptionAssignment::unknownOptions()}.
 */
final class BehaviorDefinitionResolver
{
    /**
     * Methods whose arguments the resolver reads, in lower case, with the parameter that holds the definitions.
     */
    private const array ATTACH_METHODS = ['attachbehavior' => 'behavior', 'attachbehaviors' => 'behaviors'];
    /**
     * Option name prefix with which `Component::__set()` attaches a behavior.
     */
    private const string BEHAVIOR_PREFIX = 'as ';
    /**
     * Class through which {@see Component} calls `createObject()` for a behavior definition.
     */
    private const string CREATE_OBJECT_CLASS = 'Yii';

    /**
     * Resolves the `Yii::createObject()` configurations whose assignment path is certain.
     */
    private readonly CreateObjectConfigurationResolver $configurationResolver;

    /**
     * @param ReflectionProvider $reflectionProvider Provides class reflection for owners and resolved classes.
     * @param ServiceMap $serviceMap Service and component map for Yii Application static analysis.
     */
    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
        private readonly ServiceMap $serviceMap,
    ) {
        $this->configurationResolver = new CreateObjectConfigurationResolver($reflectionProvider, $serviceMap);
    }

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
        $call = self::attachCallEntries($node, $scope);

        if ($call === null) {
            return [];
        }

        [$owner, $method, $entries] = $call;

        return $this->invalidDefinitions($entries, $owner, $method, null, $scope);
    }

    /**
     * Returns the options of the behavior configurations of an `attachBehavior()` or `attachBehaviors()` call that the
     * created behavior can't assign, in definition and configuration order.
     *
     * Records the dependencies of {@see CreateObjectConfigurationResolver::resolveTypeOptions()} for each
     * configuration.
     *
     * @param MethodCall $node Method call to inspect.
     * @param DependencyTracker&Scope $scope Current PHPStan analysis scope.
     *
     * @return list<UnknownBehaviorOption>
     */
    public function resolveAttachCallUnknownOptions(MethodCall $node, Scope $scope): array
    {
        $call = self::attachCallEntries($node, $scope);

        if ($call === null) {
            return [];
        }

        [$owner, $method, $entries] = $call;

        return $this->unknownEntryOptions($entries, $owner, $method, $scope);
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
        $return = self::behaviorsReturnEntries($node, $scope);

        if ($return === null) {
            return [];
        }

        [$owner, $entries, $declaredType] = $return;

        return $this->invalidDefinitions($entries, $owner, 'behaviors', $declaredType, $scope);
    }

    /**
     * Returns the options of the behavior configurations of a `behaviors()` return statement that the created behavior
     * can't assign, in definition and configuration order.
     *
     * Records the declaration dependency of the owner and the dependencies of
     * {@see CreateObjectConfigurationResolver::resolveTypeOptions()} for each configuration.
     *
     * @param Return_ $node Return statement to inspect.
     * @param DependencyTracker&Scope $scope Current PHPStan analysis scope.
     *
     * @return list<UnknownBehaviorOption>
     */
    public function resolveBehaviorsReturnUnknownOptions(Return_ $node, Scope $scope): array
    {
        $return = self::behaviorsReturnEntries($node, $scope);

        if ($return === null) {
            return [];
        }

        [$owner, $entries] = $return;

        return $this->unknownEntryOptions($entries, $owner, 'behaviors', $scope);
    }

    /**
     * Returns the `as` options of a `Yii::createObject()` call that the created component rejects or can't attach, in
     * configuration order.
     *
     * Records the dependencies of {@see CreateObjectConfigurationResolver::resolve()}, the class dependency of each
     * named class and, for an accepted class, the `construction:<id>` value dependency and the class dependency of its
     * container result.
     *
     * @param StaticCall $node Static call to inspect.
     * @param DependencyTracker&Scope $scope Current PHPStan analysis scope.
     *
     * @return list<InvalidBehaviorAsDefinition>
     */
    public function resolveCreateObjectAsOptions(StaticCall $node, Scope $scope): array
    {
        $configuration = $this->configurationResolver->resolve($node, $scope);

        if ($configuration === null) {
            return [];
        }

        $owner = $configuration->classReflection;

        if (self::isDeclaredByComponent($owner, '__set') === false) {
            return [];
        }

        $invalid = [];

        foreach ($configuration->options as [$option, $line, $value]) {
            if (str_starts_with($option, self::BEHAVIOR_PREFIX) === false) {
                continue;
            }

            $type = $value instanceof Expr ? $scope->getNativeType($value) : $value;

            $rejection = $this->asRejection($type, $owner, $scope);

            if ($rejection !== null) {
                $invalid[] = new InvalidBehaviorAsDefinition(
                    $configuration->calledClass,
                    $owner->getDisplayName(),
                    $option,
                    $line,
                    ...$rejection,
                );
            }
        }

        return $invalid;
    }

    /**
     * Returns the options of the behavior configurations in the `as` options of a `Yii::createObject()` call that the
     * created behavior can't assign, in configuration order.
     *
     * Only configurations whose class `Component::__set()` certainly accepts are read, since it checks the supplied
     * class before `Yii::createObject()` assigns any option.
     *
     * Records the dependencies of {@see CreateObjectConfigurationResolver::resolve()}, the class dependency of each
     * accepted class and the dependencies of {@see CreateObjectConfigurationResolver::resolveTypeOptions()} for each
     * configuration.
     *
     * @param StaticCall $node Static call to inspect.
     * @param DependencyTracker&Scope $scope Current PHPStan analysis scope.
     *
     * @return list<UnknownBehaviorOption>
     */
    public function resolveCreateObjectAsUnknownOptions(StaticCall $node, Scope $scope): array
    {
        $configuration = $this->configurationResolver->resolve($node, $scope);

        if ($configuration === null) {
            return [];
        }

        $owner = $configuration->classReflection;

        if (self::isDeclaredByComponent($owner, '__set') === false) {
            return [];
        }

        $unknown = [];

        foreach ($configuration->options as [$option, $line, $value]) {
            $type = $value instanceof Expr ? $scope->getNativeType($value) : $value;

            if (
                str_starts_with($option, self::BEHAVIOR_PREFIX) === false
                || $this->isAcceptedAsConfiguration($type, $scope) === false
            ) {
                continue;
            }

            foreach ($this->unknownOptions($value, $line, $scope) as [$behaviorClass, $name, $optionLine]) {
                $unknown[] = new UnknownBehaviorOption(
                    $owner->getDisplayName(),
                    'createObject',
                    $option,
                    $optionLine,
                    $behaviorClass,
                    $name,
                    $configuration->calledClass,
                );
            }
        }

        return $unknown;
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
     * Returns the defined and resolved classes of an `as` class name or configuration class that the component
     * rejects or can't attach, or `null` when it is accepted or not certain.
     *
     * `Component::__set()` rejects a class that isn't a strict subclass of {@see Behavior}, whatever the container
     * binds it to. It passes an accepted class to `Yii::createObject()` and the result to `attachBehavior()`, which
     * can't attach a concrete non-{@see Behavior} object without `__invoke()`.
     *
     * @param DependencyTracker&Scope $scope Current PHPStan analysis scope.
     *
     * @return array{string, string|null, null}|null
     */
    private function asClassRejection(string $id, ClassReflection $owner, Scope $scope): array|null
    {
        $className = ltrim($id, '\\');

        if ($className === '') {
            return null;
        }

        $scope->trackClassDependency($className);

        // a class unknown to the analysis may still exist at runtime
        if ($this->reflectionProvider->hasClass($className) === false) {
            return null;
        }

        $class = $this->reflectionProvider->getClass($className);

        if ($class->getName() === Behavior::class || $class->is(Behavior::class) === false) {
            return [$class->getDisplayName(), null, null];
        }

        if (self::isDeclaredByComponent($owner, 'attachBehavior') === false) {
            return null;
        }

        $resolved = $this->resolvedClass($id, $scope);

        return $resolved !== null
            && $resolved->isClass()
            && $resolved->isAbstract() === false
            && $resolved->is(Behavior::class) === false
            && $resolved->hasNativeMethod('__invoke') === false
            ? [$class->getDisplayName(), $resolved->getDisplayName(), null]
            : null;
    }

    /**
     * Returns the defined class, resolved class and value description of an `as` value that the component rejects or
     * can't attach, or `null` when it is accepted or not certain.
     *
     * Follows `Component::__set()`: a {@see Behavior} instance or a `Closure` is accepted, a configuration array is
     * checked by its non-null `__class`, else `class`, element, and any other value except a string is rejected.
     *
     * @param DependencyTracker&Scope $scope Current PHPStan analysis scope.
     *
     * @return array{string|null, string|null, string|null}|null
     */
    private function asRejection(Type $type, ClassReflection $owner, Scope $scope): array|null
    {
        foreach ($type->getReferencedClasses() as $className) {
            $scope->trackClassDependency($className);
        }

        if ($type->isObject()->no() === false) {
            // a possible `ArrayAccess` object may pass the class element check
            return $type->isObject()->yes()
                && (new ObjectType(Behavior::class))->isSuperTypeOf($type)->no()
                && (new ObjectType(Closure::class))->isSuperTypeOf($type)->no()
                && (new ObjectType(ArrayAccess::class))->isSuperTypeOf($type)->no()
                ? [null, null, $type->describe(VerbosityLevel::typeOnly())]
                : null;
        }

        $id = ObjectConfigurationReader::constantString($type);

        if ($id === null) {
            $array = ObjectConfigurationReader::constantArray($type);

            if ($array === null) {
                return $type->isString()->no() && $type->isArray()->no()
                    ? [null, null, $type->describe(VerbosityLevel::typeOnly())]
                    : null;
            }

            $id = self::configurationClass($array);

            if ($id === false) {
                return [null, null, null];
            }
        }

        return $id === null ? null : $this->asClassRejection($id, $owner, $scope);
    }

    /**
     * Returns the receiver type, the method and the definition entries of an `attachBehavior()` or `attachBehaviors()`
     * call whose every receiver class inherits the method from {@see Component}, or `null` otherwise.
     *
     * @return array{string, string, list<array{int|string|null, int, Expr|Type}>}|null
     */
    private static function attachCallEntries(MethodCall $node, Scope $scope): array|null
    {
        if ($node->name instanceof Identifier === false || $node->isFirstClassCallable()) {
            return null;
        }

        $method = strtolower($node->name->toString());

        $parameter = self::ATTACH_METHODS[$method] ?? null;

        if ($parameter === null) {
            return null;
        }

        $receiverType = $scope->getType($node->var);
        $receivers = $receiverType->getObjectClassReflections();

        if ($receivers === []) {
            return null;
        }

        foreach ($receivers as $receiver) {
            if (
                $receiver->is(Component::class) === false
                || self::isDeclaredByComponent($receiver, $method) === false
            ) {
                return null;
            }
        }

        $arguments = self::arguments($node, $method === 'attachbehavior' ? ['name', 'behavior'] : ['behaviors']);

        $definitions = $arguments[$parameter] ?? null;

        if ($definitions === null) {
            return null;
        }

        $owner = $receiverType->describe(VerbosityLevel::typeOnly());

        if ($method === 'attachbehaviors') {
            return [$owner, 'attachBehaviors', self::entries($definitions, $node->getStartLine(), $scope) ?? []];
        }

        $nameType = isset($arguments['name']) ? $scope->getNativeType($arguments['name']) : null;

        $names = $nameType?->getConstantScalarValues() ?? [];

        $name = count($names) === 1 && (is_int($names[0]) || is_string($names[0])) ? $names[0] : null;

        return [$owner, 'attachBehavior', [[$name, $definitions->getStartLine(), $definitions]]];
    }

    /**
     * Returns the owner, the definition entries and the declared element type of a `behaviors()` return statement of
     * a {@see Component} subclass that inherits `ensureBehaviors()` from {@see Component}, or `null` otherwise.
     *
     * Records the declaration dependency of the owner.
     *
     * @param DependencyTracker&Scope $scope Current PHPStan analysis scope.
     *
     * @return array{string, list<array{int|string, int, Expr|Type}>, Type}|null
     */
    private static function behaviorsReturnEntries(Return_ $node, Scope $scope): array|null
    {
        if ($node->expr === null || $scope->isInAnonymousFunction() || $scope->isInTrait()) {
            return null;
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
            return null;
        }

        $scope->trackClassDependency($owner->getName());

        if (self::isDeclaredByComponent($owner, 'ensureBehaviors') === false) {
            return null;
        }

        return [
            $owner->getDisplayName(),
            self::entries($node->expr, $node->getStartLine(), $scope) ?? [],
            $function->getReturnType()->getIterableValueType(),
        ];
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

            // a possibly null value is no single constant string either
            $class = ObjectConfigurationReader::constantString($value);

            return $class === null || $class === '' ? null : $class;
        }

        return false;
    }

    /**
     * Returns the entries of a definition list as `[name, line, definition]`, in list order, or `null` when the list
     * isn't certain.
     *
     * Literal items keep their lines, with PHP's key normalization, implicit keys (after a negative key, its successor)
     * and last-duplicate semantics; other lists must be one constant array and use the given line.
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
        $next = null;

        foreach ($list->items as $item) {
            $key = $item->key === null ? $next ?? 0 : self::literalKey($scope->getNativeType($item->key));

            if ($item->unpack || $key === null) {
                return self::typeEntries($scope->getNativeType($list), $line);
            }

            // since PHP 8.3 the next implicit key follows the first integer key, even a negative one
            if (is_int($key) && ($next === null || $key >= $next)) {
                $next = $key + 1;
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
     * Returns the definitions of a list that can't be attached, in list order.
     *
     * @param list<array{int|string|null, int, Expr|Type}> $entries Name, line and definition of each entry.
     * @param Type|null $declaredType Declared element type of the `behaviors()` return, or `null` for attach calls.
     * @param DependencyTracker&Scope $scope Current PHPStan analysis scope.
     *
     * @return list<InvalidBehaviorDefinition>
     */
    private function invalidDefinitions(
        array $entries,
        string $owner,
        string $method,
        Type|null $declaredType,
        Scope $scope,
    ): array {
        $invalid = [];

        foreach ($entries as [$name, $line, $definition]) {
            $definitionError = $this->resolveDefinition(
                $definition,
                $line,
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
     * Returns whether `Component::__set()` certainly accepts an `as` value as a configuration array: one constant array
     * whose class, a non-null `__class` before `class`, is a strict {@see Behavior} subclass known to the analysis.
     *
     * Records the class dependency of that class.
     *
     * @param DependencyTracker&Scope $scope Current PHPStan analysis scope.
     */
    private function isAcceptedAsConfiguration(Type $type, Scope $scope): bool
    {
        $array = ObjectConfigurationReader::constantArray($type);

        $id = $array === null ? null : self::configurationClass($array);

        if (is_string($id) === false) {
            return false;
        }

        $className = ltrim($id, '\\');

        $scope->trackClassDependency($className);

        if ($this->reflectionProvider->hasClass($className) === false) {
            return false;
        }

        $class = $this->reflectionProvider->getClass($className);

        return $class->getName() !== Behavior::class && $class->is(Behavior::class);
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

    /**
     * Returns the options of the behavior configurations of a definition list that the created behavior can't assign,
     * in definition and configuration order.
     *
     * @param list<array{int|string|null, int, Expr|Type}> $entries Name, line and definition of each entry.
     * @param DependencyTracker&Scope $scope Current PHPStan analysis scope.
     *
     * @return list<UnknownBehaviorOption>
     */
    private function unknownEntryOptions(array $entries, string $owner, string $method, Scope $scope): array
    {
        $unknown = [];

        foreach ($entries as [$name, $line, $definition]) {
            foreach ($this->unknownOptions($definition, $line, $scope) as [$behaviorClass, $option, $optionLine]) {
                $unknown[] = new UnknownBehaviorOption($owner, $method, $name, $optionLine, $behaviorClass, $option);
            }
        }

        return $unknown;
    }

    /**
     * Returns the behavior class, name and line of each option of a behavior configuration that the created
     * {@see Behavior} can't assign, in configuration order.
     *
     * The configuration is resolved as `Yii::createObject()` receives it, through
     * {@see CreateObjectConfigurationResolver::resolveTypeOptions()}, and its options are classified by
     * {@see ObjectOptionAssignment::unknownOptions()}; a class that isn't a {@see Behavior} stands aside.
     *
     * @param DependencyTracker&Scope $scope Current PHPStan analysis scope.
     *
     * @return list<array{string, string, int}>
     */
    private function unknownOptions(Expr|Type $definition, int $line, Scope $scope): array
    {
        $options = $definition instanceof Expr
            ? ObjectConfigurationReader::options($definition, $line, $scope)
            : ObjectConfigurationReader::optionsFromType($definition, $line);

        if ($options === null) {
            return [];
        }

        $configuration = $this->configurationResolver->resolveTypeOptions(
            self::CREATE_OBJECT_CLASS,
            $options,
            $line,
            $scope,
        );

        if ($configuration === null || $configuration->classReflection->is(Behavior::class) === false) {
            return [];
        }

        $class = $configuration->classReflection;

        $unknownOptions = ObjectOptionAssignment::unknownOptions(
            $class,
            $configuration->options,
            $this->serviceMap,
            $scope,
        );

        $unknown = [];

        foreach ($unknownOptions as [$option, $optionLine]) {
            $unknown[] = [$class->getDisplayName(), $option, $optionLine];
        }

        return $unknown;
    }
}

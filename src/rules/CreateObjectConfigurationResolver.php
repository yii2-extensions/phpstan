<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\rules;

use PhpParser\Node\Expr\{Array_, ClassConstFetch, StaticCall};
use PhpParser\Node\{Expr, Identifier, Name};
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\{DependencyTracker, Scope};
use PHPStan\Reflection\{ClassReflection, ReflectionProvider};
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Type;
use yii\base\{BaseObject, Component};
use yii\BaseYii;
use yii2\extensions\phpstan\{ServiceMap, ServiceMapResultCacheValueExtension};

use function count;
use function in_array;
use function ltrim;

/**
 * Resolves the class and options of a {@see BaseYii::createObject()} call whose assignment path Yii makes certain.
 *
 * Requires keys and class proven by literals or native types, no container binding, and a class that inherits the
 * constructor of {@see BaseObject} and the `__set()` of {@see BaseObject} or {@see Component}.
 *
 * {@see ServiceMap} for service and component map for Yii Application static analysis.
 */
final class CreateObjectConfigurationResolver
{
    /**
     * Options that {@see BaseYii::createObject()} removes before configuring the object.
     */
    public const array RESERVED_OPTIONS = ['__class', 'class'];

    /**
     * Parameters of {@see BaseYii::createObject()} in declaration order.
     */
    private const array PARAMETERS = ['type', 'params'];

    /**
     * Initializes the resolver with class reflection and the analysis configuration.
     *
     * @param ReflectionProvider $reflectionProvider Provides class reflection for the created class.
     * @param ServiceMap $serviceMap Service and component map for Yii Application static analysis.
     */
    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
        private readonly ServiceMap $serviceMap,
    ) {}

    /**
     * Returns the configuration of the call when Yii assigns every option to the created class through
     * `BaseObject::__set()` or `Component::__set()` semantics.
     *
     * Records the container binding and class declaration dependencies of the call before deciding, so that the result
     * cache reanalyses the call when either changes.
     *
     * @param StaticCall $node Static call to inspect.
     * @param DependencyTracker&Scope $scope Current PHPStan analysis scope.
     *
     * @return CreateObjectConfiguration|null Resolved configuration, or `null` when the call, the configuration or the
     * created class isn't proven.
     */
    public function resolve(StaticCall $node, Scope $scope): CreateObjectConfiguration|null
    {
        $calledClass = $this->resolveCreateObjectClass($node, $scope);

        if ($calledClass === null) {
            return null;
        }

        $type = self::configurationArgument($node, $scope);

        if ($type === null) {
            return null;
        }

        $configuration = self::isLiteralConfiguration($type)
            ? self::literalConfiguration($type, $scope)
            : self::constantConfiguration($type, $node->getStartLine(), $scope);

        if ($configuration === null) {
            return null;
        }

        [$id, $options] = $configuration;

        $className = ltrim($id, '\\');

        $scope->trackValueDependency(
            ServiceMapResultCacheValueExtension::class,
            ServiceMapResultCacheValueExtension::serviceKey($id),
        );
        $scope->trackClassDependency($className);

        if ($this->serviceMap->getServiceById($id) !== null || $this->serviceMap->isUnresolvedService($id)) {
            return null;
        }

        $classReflection = $this->resolveConfigurableClass($className);

        return $classReflection === null
            ? null
            : new CreateObjectConfiguration($calledClass, $classReflection, $options);
    }

    /**
     * Returns the class value Yii selects: the `__class` value when the configuration contains that key, else the
     * `class` value.
     *
     * @template T of Expr|Type
     *
     * @param list<array{string, int, T}> $options Name, line and effective value of each option.
     *
     * @return T|null Selected value, or `null` when no class key is present or `__construct()` supplies constructor
     * arguments.
     */
    private static function classValue(array $options): Expr|Type|null
    {
        if (self::optionValue($options, '__construct()') !== null) {
            return null;
        }

        return self::optionValue($options, '__class') ?? self::optionValue($options, 'class');
    }

    /**
     * Returns the `$type` argument when the arguments are positional or named `type`/`params`, without unpacking, and
     * `$params` is absent or natively known to be an empty array.
     */
    private static function configurationArgument(StaticCall $node, Scope $scope): Expr|null
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

        $params = $arguments['params'] ?? null;

        if ($params !== null) {
            $paramsType = $scope->getNativeType($params);

            if ($paramsType->isArray()->yes() === false || $paramsType->isIterableAtLeastOnce()->no() === false) {
                return null;
            }
        }

        return $arguments['type'] ?? null;
    }

    /**
     * Returns the only constant array a native type can hold, without optional keys or an unsealed part.
     *
     * A single constant array doesn't prove the value on its own: an intersection can synthesize one for a callable
     * array, so the array must also contain every value of the type.
     */
    private static function constantArray(Type $type): ConstantArrayType|null
    {
        $arrays = $type->getConstantArrays();

        if (count($arrays) !== 1) {
            return null;
        }

        $array = $arrays[0];

        return $array->isSuperTypeOf($type)->yes()
            && $array->getOptionalKeys() === []
            && $array->isUnsealed()->yes() === false
            ? $array
            : null;
    }

    /**
     * Returns the class ID and options of a configuration whose native type is one constant array with string keys.
     *
     * Each option is located at its first key when the configuration is an array literal without unpacking whose keys
     * all have one constant string value; otherwise every option is located at the call line.
     *
     * @return array{string, list<array{string, int, Type}>}|null Class name as written and the name, line and value
     * type of each option, or `null` when the native type holds more than one array, an optional key, an integer key,
     * or no single class.
     */
    private static function constantConfiguration(Expr $type, int $callLine, Scope $scope): array|null
    {
        $array = self::constantArray($scope->getNativeType($type));

        if ($array === null) {
            return null;
        }

        $lines = self::keyLines($type, $scope);
        $options = [];

        foreach ($array->getKeyTypes() as $keyType) {
            $name = self::constantString($keyType);

            if ($name === null) {
                return null;
            }

            $options[] = [$name, $lines[$name] ?? $callLine, $array->getOffsetValueType($keyType)];
        }

        $classType = self::classValue($options);
        $id = $classType === null ? null : self::constantString($classType);

        return $id === null || $id === '' ? null : [$id, $options];
    }

    /**
     * Returns the value of a native type that holds exactly one constant string.
     */
    private static function constantString(Type $type): string|null
    {
        $strings = $type->getConstantStrings();

        return count($strings) === 1 && $strings[0]->isSuperTypeOf($type)->yes() ? $strings[0]->getValue() : null;
    }

    /**
     * Returns whether the native method of the class, possibly inherited, is declared by one of the given classes.
     *
     * @param list<string> $declaringClasses Fully qualified names of the accepted declaring classes.
     */
    private static function isDeclaredBy(
        ClassReflection $classReflection,
        string $methodName,
        array $declaringClasses,
    ): bool {
        return $classReflection->hasNativeMethod($methodName)
            && in_array(
                $classReflection->getNativeMethod($methodName)->getDeclaringClass()->getName(),
                $declaringClasses,
                true,
            );
    }

    /**
     * Returns whether the configuration is an array literal without unpacking whose keys are all literal strings.
     *
     * @phpstan-assert-if-true Array_ $type
     */
    private static function isLiteralConfiguration(Expr $type): bool
    {
        if ($type instanceof Array_ === false) {
            return false;
        }

        foreach ($type->items as $item) {
            if ($item->unpack || $item->key instanceof String_ === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns the first line of each key of an array literal without unpacking, by option name.
     *
     * @return array<int|string, int> Line by option name; empty when the expression isn't such a literal or a key
     * doesn't have one constant string value, so that no option is located at an item it may not come from.
     */
    private static function keyLines(Expr $type, Scope $scope): array
    {
        if ($type instanceof Array_ === false) {
            return [];
        }

        $lines = [];

        foreach ($type->items as $item) {
            $name = match (true) {
                $item->key === null => null,
                $item->key instanceof String_ => $item->key->value,
                default => self::constantString($scope->getNativeType($item->key)),
            };

            if ($name === null) {
                return [];
            }

            $lines[$name] ??= $item->getStartLine();
        }

        return $lines;
    }

    /**
     * Returns the literal class name an expression holds, as written for a string and resolved for `::class`, or else
     * the one constant string of its native type.
     *
     * A `static::` fetch is refused in both forms, since late static binding selects the class at runtime.
     */
    private static function literalClassName(Expr $expr, Scope $scope): string|null
    {
        if ($expr instanceof String_) {
            return $expr->value === '' ? null : $expr->value;
        }

        if ($expr instanceof ClassConstFetch && $expr->class instanceof Name) {
            if ($expr->class->toLowerString() === 'static') {
                return null;
            }

            if ($expr->name instanceof Identifier && $expr->name->toLowerString() === 'class') {
                return $scope->resolveName($expr->class);
            }
        }

        $className = self::constantString($scope->getNativeType($expr));

        return $className === '' ? null : $className;
    }

    /**
     * Returns the class ID and the distinct options of an array literal whose keys are all literal strings, in
     * first-occurrence order, each at the line of its first item with the value of its last item, as PHP builds the
     * array.
     *
     * @return array{string, list<array{string, int, Expr}>}|null Class name as written and the name, line and value
     * expression of each option, or `null` when no class key is present, the selected class value isn't one class
     * name, or `__construct()` supplies constructor arguments.
     */
    private static function literalConfiguration(Array_ $type, Scope $scope): array|null
    {
        $options = [];
        $positions = [];

        foreach ($type->items as $item) {
            $name = $item->key instanceof String_ ? $item->key->value : '';
            $position = $positions[$name] ?? null;

            if ($position !== null && isset($options[$position])) {
                $options[$position] = [$options[$position][0], $options[$position][1], $item->value];
            } else {
                $positions[$name] = count($options);
                $options[] = [$name, $item->getStartLine(), $item->value];
            }
        }

        $classExpr = self::classValue($options);
        $id = $classExpr === null ? null : self::literalClassName($classExpr, $scope);

        return $id === null ? null : [$id, $options];
    }

    /**
     * Returns the effective value of an option, or `null` when the configuration doesn't contain it.
     *
     * @template T of Expr|Type
     *
     * @param list<array{string, int, T}> $options Name, line and effective value of each option.
     *
     * @return T|null
     */
    private static function optionValue(array $options, string $name): Expr|Type|null
    {
        foreach ($options as [$optionName, , $value]) {
            if ($optionName === $name) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Returns the class when it is instantiable and inherits the constructor of {@see BaseObject} and the `__set()` of
     * {@see BaseObject} or {@see Component}.
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

        return self::isDeclaredBy($classReflection, '__construct', [BaseObject::class])
            && self::isDeclaredBy($classReflection, '__set', [BaseObject::class, Component::class])
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
}

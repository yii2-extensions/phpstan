<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\rules;

use PhpParser\Node;
use PhpParser\Node\Expr\{Array_, ClassConstFetch, StaticCall};
use PhpParser\Node\{Expr, Identifier, Name};
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\{DependencyTracker, Scope};
use PHPStan\Reflection\{ClassReflection, ReflectionProvider};
use PHPStan\Rules\{IdentifierRuleError, Rule, RuleErrorBuilder};
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Type;
use yii\base\BaseObject;
use yii\BaseYii;
use yii2\extensions\phpstan\exception\Message;
use yii2\extensions\phpstan\{ServiceMap, ServiceMapResultCacheValueExtension};

use function count;
use function in_array;
use function ltrim;

/**
 * Reports {@see BaseYii::createObject()} options that Yii can't assign to the created object.
 *
 * Applies only to a configuration whose keys and class are literal or proven by one constant native type, for a class
 * without container binding that inherits the constructor and `__set()` of {@see BaseObject}; any other call is left
 * alone. PHPDoc types are not consulted.
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
        $calledClass = $this->resolveCreateObjectClass($node, $scope);

        if ($calledClass === null) {
            return [];
        }

        $type = self::configurationArgument($node, $scope);

        if ($type === null) {
            return [];
        }

        $configuration = self::isLiteralConfiguration($type)
            ? self::literalConfiguration($type, $scope)
            : self::constantConfiguration($type, $node->getStartLine(), $scope);

        if ($configuration === null) {
            return [];
        }

        [$id, $options] = $configuration;

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

        foreach ($options as [$name, $line]) {
            if (self::isUnknownOption($classReflection, $name)) {
                $errors[] = RuleErrorBuilder::message(
                    Message::CREATE_OBJECT_UNKNOWN_OPTION->getMessage(
                        $calledClass,
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
     * @return array{string, list<array{string, int}>}|null Class name as written and the name and line of each option,
     * or `null` when the native type holds more than one array, an optional key, an integer key, or no single class.
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

        return $id === null || $id === '' ? null : [$id, self::optionLines($options)];
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
     * Returns whether the class itself declares the method as {@see BaseObject} does.
     */
    private static function isDeclaredByBaseObject(ClassReflection $classReflection, string $methodName): bool
    {
        return $classReflection->hasNativeMethod($methodName)
            && $classReflection->getNativeMethod($methodName)->getDeclaringClass()->getName() === BaseObject::class;
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
     * first-occurrence order, each at the line of its first item, as PHP builds the array.
     *
     * @return array{string, list<array{string, int}>}|null Class name as written and the name and line of each option,
     * or `null` when no class key is present, the selected class value isn't one class name, or `__construct()`
     * supplies constructor arguments.
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

        return $id === null ? null : [$id, self::optionLines($options)];
    }

    /**
     * Returns the name and line of each option, dropping its value.
     *
     * @param list<array{string, int, Expr|Type}> $options Name, line and effective value of each option.
     *
     * @return list<array{string, int}>
     */
    private static function optionLines(array $options): array
    {
        $lines = [];

        foreach ($options as [$name, $line]) {
            $lines[] = [$name, $line];
        }

        return $lines;
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
}

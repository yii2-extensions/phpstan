<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\rules;

use PhpParser\Node\Expr\{Array_, ClassConstFetch, StaticCall};
use PhpParser\Node\{Expr, Identifier, Name};
use PhpParser\Node\Scalar\{Int_, String_};
use PHPStan\Analyser\{DependencyTracker, Scope};
use PHPStan\Reflection\{ClassReflection, ReflectionProvider};
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Type;
use yii\base\{BaseObject, Component};
use yii\BaseYii;
use yii2\extensions\phpstan\{ServiceMap, ServiceMapResultCacheValueExtension};

use function in_array;
use function is_int;
use function max;

/**
 * Resolves the class and options of a {@see BaseYii::createObject()} call whose assignment path Yii makes certain.
 *
 * Requires keys and class proven by literals or native types, a container ID that is unbound or bound only through
 * definitions that name a class ({@see ServiceMap::getConfiguredClassById()}) and whose options, if any, are all plain
 * public properties of the created class, and a created class that inherits the constructor of {@see BaseObject} and
 * the `__set()` of {@see BaseObject} or {@see Component}.
 *
 * Constructor arguments from `$params`, a `__construct()` option and the definitions on the chain are accepted when
 * their keys, merged as {@see \yii\di\Container::build()} merges them, name at most the one `$config` parameter of
 * that constructor, by position `0` or by name `config`. Yii then assigns the options of the configuration when there
 * are any, and otherwise uses that argument as the configuration, which is resolved only when the call supplies it.
 *
 * The resolved options are the ones Yii assigns, in order: the options of the `$type` configuration without the `class`
 * and `__class` keys that select the class, or every key of the argument used as the configuration, `class`, `__class`
 * and `__construct()` included, which {@see BaseYii::configure()} assigns as ordinary properties.
 *
 * {@see ServiceMap} for service and component map for Yii Application static analysis.
 */
final class CreateObjectConfigurationResolver
{
    /**
     * Options that {@see BaseYii::createObject()} removes from its `$type` configuration to select the class, before
     * configuring the object; a constructor argument used as the configuration keeps them.
     */
    public const array RESERVED_OPTIONS = ['__class', 'class'];

    /**
     * Name of the only parameter of the constructor of {@see BaseObject}.
     */
    private const string CONFIG_PARAMETER = 'config';
    /**
     * Option whose array {@see \yii\di\Container::build()} passes as constructor arguments instead of assigning it.
     */
    private const string CONSTRUCT_OPTION = '__construct()';
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
     * Returns the configuration that Yii assigns to the created class through `BaseObject::__set()` or
     * `Component::__set()` semantics: the options of the call without its class keys, or every key of the constructor
     * argument that the call supplies as configuration when neither the call nor a definition has options.
     *
     * Records the construction dependency of the container ID before deciding, and the declaration dependency of the
     * created class once known, so that the result cache reanalyses the call when a definition on the chain or that
     * class changes.
     *
     * @param StaticCall $node Static call to inspect.
     * @param DependencyTracker&Scope $scope Current PHPStan analysis scope.
     *
     * @return CreateObjectConfiguration|null Resolved configuration, or `null` when the call, the configuration, the
     * constructor arguments or the created class isn't proven, or a definition supplies the configuration.
     */
    public function resolve(StaticCall $node, Scope $scope): CreateObjectConfiguration|null
    {
        $calledClass = $this->resolveCreateObjectClass($node, $scope);

        if ($calledClass === null) {
            return null;
        }

        $arguments = self::callArguments($node);

        if ($arguments === null) {
            return null;
        }

        [$type, $params] = $arguments;

        $callParams = $params === null ? null : ObjectConfigurationReader::constantArray($scope->getNativeType($params));

        if ($params !== null && $callParams === null) {
            return null;
        }

        $line = $node->getStartLine();

        $typeOptions = ObjectConfigurationReader::options($type, $line, $scope);

        $configuration = $typeOptions === null
            ? self::classNameConfiguration($type, $callParams, $scope)
            : self::classConfiguration($typeOptions, $scope);

        if ($configuration === null) {
            return null;
        }

        [$id, $options] = $configuration;

        $construct = self::optionValue($options, self::CONSTRUCT_OPTION);

        $callConstruct = null;

        if ($construct !== null) {
            $callConstruct = ObjectConfigurationReader::constantArray(
                $construct instanceof Expr ? $scope->getNativeType($construct) : $construct,
            );

            if ($callConstruct === null) {
                return null;
            }

            $options = self::withoutOption($options, self::CONSTRUCT_OPTION);
        }

        $scope->trackValueDependency(
            ServiceMapResultCacheValueExtension::class,
            ServiceMapResultCacheValueExtension::constructionKey($id),
        );

        $className = $this->serviceMap->getConfiguredClassById($id);

        if ($className === null) {
            return null;
        }

        $scope->trackClassDependency($className);

        $classReflection = $this->resolveConfigurableClass($className);

        if ($classReflection === null) {
            return null;
        }

        $definitionOptions = $this->serviceMap->getDefinitionOptionNamesById($id);

        foreach ($definitionOptions as $name) {
            if (self::isPlainPublicProperty($classReflection, $name) === false) {
                return null;
            }
        }

        $constructKeys = $callConstruct === null
            ? $this->serviceMap->getDefinitionConstructKeysById($id) ?? []
            : self::keys($callConstruct);
        $definitionParamKeys = $this->serviceMap->getDefinitionParamKeysById($id);
        $callParamKeys = $callParams === null ? [] : self::keys($callParams);

        $argumentKeys = [...$constructKeys, ...$definitionParamKeys, ...$callParamKeys];

        if (self::isConfigArgumentOnly($argumentKeys) === false) {
            return null;
        }

        $assignedOptions = self::withoutClassKeys($options);

        if ($argumentKeys === [] || $definitionOptions !== [] || $assignedOptions !== []) {
            return new CreateObjectConfiguration($calledClass, $classReflection, $assignedOptions);
        }

        $key = $argumentKeys[0];

        [$argument, $argumentExpr] = match (true) {
            in_array($key, $callParamKeys, true) => [$callParams, $params],
            in_array($key, $definitionParamKeys, true) => [null, null],
            $callConstruct !== null => [$callConstruct, $construct instanceof Expr ? $construct : null],
            default => [null, null],
        };

        $argumentOptions = $argument === null
            ? null
            : self::argumentConfiguration($argument, $key, $argumentExpr, $line, $scope);

        return $argumentOptions === null
            ? null
            : new CreateObjectConfiguration($calledClass, $classReflection, $argumentOptions);
    }

    /**
     * Returns the options of the constructor argument that Yii passes as the configuration of the object, located at
     * their keys when the argument is an array literal item of the literal arguments, otherwise at the call line.
     *
     * {@see BaseYii::configure()} assigns every key of that argument, `class` and `__class` included.
     *
     * @param ConstantArrayType $arguments Constructor arguments that hold the configuration.
     * @param int|string $key Key of the argument: `0` or `config`.
     * @param Expr|null $argumentsExpr Expression of the arguments, if written in the call.
     *
     * @return list<array{string, int, Type}>|null Name, line and value type of each option, or `null` when the
     * argument isn't one constant array with string keys.
     */
    private static function argumentConfiguration(
        ConstantArrayType $arguments,
        int|string $key,
        Expr|null $argumentsExpr,
        int $callLine,
        Scope $scope,
    ): array|null {
        $argument = null;

        foreach ($arguments->getKeyTypes() as $keyType) {
            if ($keyType->getValue() === $key) {
                $argument = ObjectConfigurationReader::constantArray($arguments->getOffsetValueType($keyType));
            }
        }

        if ($argument === null) {
            return null;
        }

        $argumentExpr = self::argumentExpr($argumentsExpr, $key);
        $lines = $argumentExpr === null ? [] : ObjectConfigurationReader::keyLines($argumentExpr, $scope);
        $options = [];

        foreach ($argument->getKeyTypes() as $keyType) {
            $name = ObjectConfigurationReader::constantString($keyType);

            if ($name === null) {
                return null;
            }

            $options[] = [$name, $lines[$name] ?? $callLine, $argument->getOffsetValueType($keyType)];
        }

        return $options;
    }

    /**
     * Returns the value expression of the item of an array literal without unpacking that PHP stores under the key,
     * keeping the last of duplicated keys, or `null` when the item can't be located.
     */
    private static function argumentExpr(Expr|null $arguments, int|string $key): Expr|null
    {
        if ($arguments instanceof Array_ === false) {
            return null;
        }

        $found = null;
        $next = 0;

        foreach ($arguments->items as $item) {
            $itemKey = match (true) {
                $item->unpack => null,
                $item->key === null => $next,
                $item->key instanceof Int_ => $item->key->value,
                $item->key instanceof String_ => (string) (int) $item->key->value === $item->key->value
                    ? (int) $item->key->value
                    : $item->key->value,
                default => null,
            };

            if ($itemKey === null) {
                return null;
            }

            if (is_int($itemKey)) {
                $next = max($next, $itemKey + 1);
            }

            if ($itemKey === $key) {
                $found = $item->value;
            }
        }

        return $found;
    }

    /**
     * Returns the `$type` and `$params` arguments when the arguments are positional or named `type`/`params`, without
     * unpacking.
     *
     * @return array{Expr, Expr|null}|null Configuration and params expressions, or `null` without a `$type` argument.
     */
    private static function callArguments(StaticCall $node): array|null
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

        return $type === null ? null : [$type, $arguments['params'] ?? null];
    }

    /**
     * Returns the class ID of a configuration from the value Yii selects among its class keys: a literal class name or
     * the one constant string of the value expression, or the one constant string of the value type.
     *
     * @param list<array{string, int, Expr|Type}> $options Name, line and effective value of each option.
     *
     * @return array{string, list<array{string, int, Expr|Type}>}|null Class name as written and the options, or `null`
     * when no class key is present or the selected class value isn't one class name.
     */
    private static function classConfiguration(array $options, Scope $scope): array|null
    {
        $classValue = self::classValue($options);

        $id = match (true) {
            $classValue === null => null,
            $classValue instanceof Expr => self::literalClassName($classValue, $scope),
            default => ObjectConfigurationReader::constantString($classValue),
        };

        return $id === null || $id === '' ? null : [$id, $options];
    }

    /**
     * Returns the class ID of a `$type` argument that is one class name, with no options, when `$params` supplies
     * constructor arguments; without them the call has no option to analyse.
     *
     * @return array{string, list<array{string, int, Expr|Type}>}|null
     */
    private static function classNameConfiguration(
        Expr $type,
        ConstantArrayType|null $callParams,
        Scope $scope,
    ): array|null {
        if ($callParams === null || $callParams->getKeyTypes() === []) {
            return null;
        }

        $id = self::literalClassName($type, $scope);

        return $id === null ? null : [$id, []];
    }

    /**
     * Returns the class value Yii selects: the `__class` value when the configuration contains that key, else the
     * `class` value.
     *
     * @template T of Expr|Type
     *
     * @param list<array{string, int, T}> $options Name, line and effective value of each option.
     *
     * @return T|null Selected value, or `null` when no class key is present.
     */
    private static function classValue(array $options): Expr|Type|null
    {
        return self::optionValue($options, '__class') ?? self::optionValue($options, 'class');
    }

    /**
     * Returns whether constructor argument keys, merged by {@see \yii\di\Container::build()}, address only the
     * `$config` parameter of the {@see BaseObject} constructor: all `0` or all `config`.
     *
     * Mixed integer and string keys make Yii throw, and any other key adds a constructor argument after `$config`, so
     * that Yii passes the configuration as that argument instead.
     *
     * @param list<int|string> $keys Keys of every source of constructor arguments.
     */
    private static function isConfigArgumentOnly(array $keys): bool
    {
        foreach ($keys as $key) {
            if ($key !== 0 && $key !== self::CONFIG_PARAMETER) {
                return false;
            }
        }

        return in_array(0, $keys, true) === false || in_array(self::CONFIG_PARAMETER, $keys, true) === false;
    }

    /**
     * Returns whether Yii assigns the option directly to a native property without running application code: a public
     * instance property that isn't read-only, hooked or of asymmetric visibility.
     *
     * Definition options are assigned before the options of the call, so only such options leave the assignment of the
     * call's options to declarations, whatever the method bodies of the class do.
     */
    private static function isPlainPublicProperty(ClassReflection $classReflection, string $name): bool
    {
        if ($classReflection->hasNativeProperty($name) === false) {
            return false;
        }

        $property = $classReflection->getNativeProperty($name);

        return $property->isPublic()
            && $property->isStatic() === false
            && $property->isReadOnly() === false
            && $property->isHooked() === false
            && $property->isPrivateSet() === false
            && $property->isProtectedSet() === false;
    }

    /**
     * Returns the keys of a constant array in order.
     *
     * @return list<int|string>
     */
    private static function keys(ConstantArrayType $array): array
    {
        $keys = [];

        foreach ($array->getKeyTypes() as $keyType) {
            $keys[] = $keyType->getValue();
        }

        return $keys;
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

        $className = ObjectConfigurationReader::constantString($scope->getNativeType($expr));

        return $className === '' ? null : $className;
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

        return ObjectOptionAssignment::isConfigurable($classReflection) ? $classReflection : null;
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
     * Returns the options of the `$type` configuration that Yii assigns once {@see BaseYii::createObject()} has
     * selected the class: every option except `class` and `__class`.
     *
     * A resolved configuration has a non-`null` `__class` value, which makes Yii remove both keys, or only a `class`
     * key, which Yii removes.
     *
     * @template T of Expr|Type
     *
     * @param list<array{string, int, T}> $options Name, line and effective value of each option.
     *
     * @return list<array{string, int, T}>
     */
    private static function withoutClassKeys(array $options): array
    {
        $kept = [];

        foreach ($options as $option) {
            if (in_array($option[0], self::RESERVED_OPTIONS, true) === false) {
                $kept[] = $option;
            }
        }

        return $kept;
    }

    /**
     * Returns the options without the given one.
     *
     * @template T of Expr|Type
     *
     * @param list<array{string, int, T}> $options Name, line and effective value of each option.
     *
     * @return list<array{string, int, T}>
     */
    private static function withoutOption(array $options, string $name): array
    {
        $kept = [];

        foreach ($options as $option) {
            if ($option[0] !== $name) {
                $kept[] = $option;
            }
        }

        return $kept;
    }
}

<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\rules;

use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\{Expr, Identifier, Name};
use PHPStan\Analyser\{DependencyTracker, Scope};
use PHPStan\Reflection\{ExtendedMethodReflection, ReflectionProvider};
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Type;
use yii\base\Widget;
use yii\BaseYii;
use yii2\extensions\phpstan\ServiceMap;

use function count;
use function in_array;

/**
 * Resolves the widget class and options of a {@see Widget::widget()} or {@see Widget::begin()} call whose assignment
 * path Yii makes certain.
 *
 * Both methods set the `class` key of their configuration to the called class and pass it to
 * {@see BaseYii::createObject()} without `$params`, so the configuration is resolved as
 * {@see CreateObjectConfigurationResolver::resolveTypeOptions()} resolves that array: a non-`null` `__class` value
 * selects the class instead, and container definitions, `__construct()` and the configurable class are handled as for
 * `Yii::createObject()`.
 *
 * Requires the called class written as a name (an imported alias or a fully qualified name, never `self`, `parent` or
 * `static`) that is {@see Widget} or a subclass, a method that {@see Widget} declares and the class doesn't override,
 * the configuration as the only argument by position or by its parameter name and proven as
 * {@see ObjectConfigurationReader::options()} describes, and a created class that is a {@see Widget} subclass.
 */
final class WidgetConfigurationResolver
{
    /**
     * Methods of {@see Widget} that create a configured widget, in lowercase.
     */
    private const array METHODS = ['begin', 'widget'];
    /**
     * Class names that the scope resolves, and that late static binding or `Closure::bind()` may change.
     */
    private const array SCOPE_CLASS_NAMES = ['parent', 'self', 'static'];

    /**
     * Resolves the `Yii::createObject()` configuration that both methods build.
     */
    private readonly CreateObjectConfigurationResolver $createObjectResolver;

    /**
     * Initializes the resolver with class reflection and the analysis configuration.
     *
     * @param ReflectionProvider $reflectionProvider Provides class reflection for the called and created classes.
     * @param ServiceMap $serviceMap Service and component map for Yii Application static analysis.
     */
    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
        ServiceMap $serviceMap,
    ) {
        $this->createObjectResolver = new CreateObjectConfigurationResolver($reflectionProvider, $serviceMap);
    }

    /**
     * Returns the configuration that Yii assigns to the widget that the call creates.
     *
     * Records the declaration dependency of the called class before deciding, so that the result cache reanalyses the
     * call when that class starts or stops overriding the method, and the construction and declaration dependencies of
     * {@see CreateObjectConfigurationResolver::resolveTypeOptions()}.
     *
     * @param StaticCall $node Static call to inspect.
     * @param DependencyTracker&Scope $scope Current PHPStan analysis scope.
     *
     * @return WidgetConfiguration|null Resolved configuration, or `null` when the call, its configuration or the
     * created widget isn't proven, or the call has no configuration.
     */
    public function resolve(StaticCall $node, Scope $scope): WidgetConfiguration|null
    {
        if (
            $node->class instanceof Name === false
            || in_array($node->class->toLowerString(), self::SCOPE_CLASS_NAMES, true)
            || $node->name instanceof Identifier === false
            || in_array($node->name->toLowerString(), self::METHODS, true) === false
            || $node->isFirstClassCallable()
        ) {
            return null;
        }

        $className = $scope->resolveName($node->class);

        if ($this->reflectionProvider->hasClass($className) === false) {
            return null;
        }

        $scope->trackClassDependency($className);

        $calledClass = $this->reflectionProvider->getClass($className);

        if ($calledClass->hasNativeMethod($node->name->name) === false) {
            return null;
        }

        $method = $calledClass->getNativeMethod($node->name->name);

        // only `Widget` and its subclasses inherit the method from `Widget`
        if ($method->getDeclaringClass()->getName() !== Widget::class) {
            return null;
        }

        $config = self::configArgument($node, $method);

        if ($config === null) {
            return null;
        }

        $line = $node->getStartLine();

        $options = ObjectConfigurationReader::options($config, $line, $scope);

        if ($options === null) {
            return null;
        }

        $configuration = $this->createObjectResolver->resolveTypeOptions(
            $calledClass->getDisplayName(),
            self::withCalledClass($options, $calledClass->getName(), $line),
            $line,
            $scope,
        );

        return $configuration !== null && $configuration->classReflection->is(Widget::class)
            ? new WidgetConfiguration($method->getName(), $configuration)
            : null;
    }

    /**
     * Returns the argument passed to the only parameter of the method, read from its reflection: the first positional
     * argument or the named argument with the parameter's name.
     *
     * @return Expr|null Configuration expression, or `null` without arguments, with an unpacked argument, another named
     * or positional argument, the parameter passed twice, or a method without exactly one parameter.
     */
    private static function configArgument(StaticCall $node, ExtendedMethodReflection $method): Expr|null
    {
        $variants = $method->getVariants();

        $parameters = count($variants) === 1 ? $variants[0]->getParameters() : [];
        $parameterName = count($parameters) === 1 ? $parameters[0]->getName() : null;

        $config = null;

        foreach ($node->getArgs() as $position => $arg) {
            $isConfig = $arg->name === null ? $position === 0 : $arg->name->toString() === $parameterName;

            if ($parameterName === null || $arg->unpack || $isConfig === false || $config !== null) {
                return null;
            }

            $config = $arg->value;
        }

        return $config;
    }

    /**
     * Returns the options with the `class` value set to the called class, as `$config['class'] = get_called_class()`
     * does: in place when the configuration has a `class` key, otherwise appended at the call line.
     *
     * @param list<array{string, int, Expr|Type}> $options Name, line and effective value of each option.
     * @param string $className Name of the called class as `get_called_class()` returns it.
     *
     * @return list<array{string, int, Expr|Type}>
     */
    private static function withCalledClass(array $options, string $className, int $line): array
    {
        $classType = new ConstantStringType($className);

        foreach ($options as $position => [$name, $optionLine]) {
            if ($name === 'class') {
                $options[$position] = [$name, $optionLine, $classType];

                return $options;
            }
        }

        $options[] = ['class', $line, $classType];

        return $options;
    }
}

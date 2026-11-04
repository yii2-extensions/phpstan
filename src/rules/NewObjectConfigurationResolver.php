<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\rules;

use PhpParser\Node\Expr\New_;
use PhpParser\Node\{Expr, Name};
use PHPStan\Analyser\{DependencyTracker, Scope};
use PHPStan\Reflection\{ClassReflection, ReflectionProvider};
use yii\base\{BaseObject, Component};
use yii\BaseYii;

use function count;
use function in_array;

/**
 * Resolves the class and configuration of a direct `new` construction whose assignment path Yii makes certain.
 *
 * Requires a class name written in the expression (an imported alias, `self` or `parent`, or `static` in a final
 * class, never inside a closure or an arrow function), a class that inherits the constructor of {@see BaseObject} and
 * the `__set()` of {@see BaseObject} or {@see Component}, and a configuration passed to that constructor's parameter by
 * position or by name and proven as {@see ObjectConfigurationReader::options()} describes.
 *
 * `new` doesn't consult `Yii::$container`, so container definitions never change the constructed class, and
 * {@see BaseYii::configure()} assigns every key of the configuration.
 */
final class NewObjectConfigurationResolver
{
    /**
     * Class names that the scope resolves, and that a closure or an arrow function may see rebound.
     */
    private const array SCOPE_CLASS_NAMES = ['parent', 'self', 'static'];

    /**
     * Initializes the resolver with class reflection.
     *
     * @param ReflectionProvider $reflectionProvider Provides class reflection for the constructed class.
     */
    public function __construct(private readonly ReflectionProvider $reflectionProvider) {}

    /**
     * Returns the configuration that the inherited `BaseObject` constructor assigns to the constructed class.
     *
     * Records the declaration dependency of the constructed class once known, so that the result cache reanalyses the
     * construction when that class, a parent class or a trait changes.
     *
     * @param New_ $node Construction to inspect.
     * @param DependencyTracker&Scope $scope Current PHPStan analysis scope.
     *
     * @return NewObjectConfiguration|null Resolved configuration, or `null` when the class, its constructor and
     * `__set()`, the arguments or the configuration isn't proven.
     */
    public function resolve(New_ $node, Scope $scope): NewObjectConfiguration|null
    {
        $className = self::className($node, $scope);

        if ($className === null || $this->reflectionProvider->hasClass($className) === false) {
            return null;
        }

        $scope->trackClassDependency($className);

        $classReflection = $this->reflectionProvider->getClass($className);

        if ($classReflection->isAnonymous() || ObjectOptionAssignment::isConfigurable($classReflection) === false) {
            return null;
        }

        $config = self::configArgument($node, $classReflection);

        if ($config === null) {
            return null;
        }

        $options = ObjectConfigurationReader::options($config, $node->getStartLine(), $scope);

        return $options === null ? null : new NewObjectConfiguration($classReflection, $options);
    }

    /**
     * Returns the name of the class that the expression constructs, when it is written as a name and isn't a
     * first-class callable such as `new Target(...)`, which PHP rejects at compile time.
     *
     * `static` is accepted only in a final class, where late static binding can't select a subclass. Scope-relative
     * names are refused inside a closure or an arrow function, since `Closure::bind()` can change their class.
     */
    private static function className(New_ $node, Scope $scope): string|null
    {
        if ($node->class instanceof Name === false || $node->isFirstClassCallable()) {
            return null;
        }

        $name = $node->class->toLowerString();

        if (in_array($name, self::SCOPE_CLASS_NAMES, true) && $scope->isInAnonymousFunction()) {
            return null;
        }

        if ($name !== 'static') {
            return $scope->resolveName($node->class);
        }

        $classReflection = $scope->getClassReflection();

        return $classReflection !== null && $classReflection->isFinal() ? $classReflection->getName() : null;
    }

    /**
     * Returns the argument passed to the only parameter of the inherited constructor, read from its reflection: the
     * first positional argument or the named argument with the parameter's name.
     *
     * @return Expr|null Configuration expression, or `null` without such an argument, or with an unpacked argument,
     * another named or positional argument, the parameter passed twice, or a constructor without exactly one
     * parameter.
     */
    private static function configArgument(New_ $node, ClassReflection $classReflection): Expr|null
    {
        $variants = $classReflection->getNativeMethod('__construct')->getVariants();
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
}

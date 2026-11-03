<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\rules;

use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\{DependencyTracker, Scope};
use PHPStan\Reflection\{ClassReflection, ReflectionProvider};
use PHPStan\Rules\{IdentifierRuleError, Rule, RuleErrorBuilder};
use yii\base\BaseObject;
use yii\BaseYii;
use yii2\extensions\phpstan\exception\Message;
use yii2\extensions\phpstan\ServiceMap;

use function in_array;

/**
 * Reports {@see BaseYii::createObject()} options that Yii can't assign to the created object.
 *
 * Applies only to a configuration whose keys and class are literal or proven by one constant native type, for a class
 * without container binding that inherits the constructor and `__set()` of {@see BaseObject}; any other call is left
 * alone. PHPDoc types are not consulted.
 *
 * {@see Rule} for PHPStan rule contract.
 * {@see CreateObjectConfigurationResolver} for the configurations and classes the rule analyses.
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
     * Resolves the configurations whose assignment path is certain.
     */
    private readonly CreateObjectConfigurationResolver $configurationResolver;

    /**
     * Initializes the rule with class reflection and the analysis configuration.
     *
     * @param ReflectionProvider $reflectionProvider Provides class reflection for the created class.
     * @param ServiceMap $serviceMap Service and component map for Yii Application static analysis.
     */
    public function __construct(ReflectionProvider $reflectionProvider, ServiceMap $serviceMap)
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
        $errors = [];

        foreach ($configuration->options as [$name, $line]) {
            if (self::isUnknownOption($classReflection, $name)) {
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
}

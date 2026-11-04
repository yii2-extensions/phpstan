<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\rules;

use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\{DependencyTracker, Scope};
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\{IdentifierRuleError, Rule, RuleErrorBuilder};
use yii\base\Component;
use yii\BaseYii;
use yii2\extensions\phpstan\exception\Message;
use yii2\extensions\phpstan\ServiceMap;

/**
 * Reports {@see BaseYii::createObject()} options that Yii can't assign to the created object.
 *
 * For a {@see Component} subclass, `on ...` and `as ...` options are never reported, and other options only when no
 * behavior can receive them. The `class` and `__class` keys with which {@see BaseYii::createObject()} selects the class
 * are never reported; inside a constructor argument used as the configuration they are options like any other.
 *
 * {@see Rule} for PHPStan rule contract.
 * {@see CreateObjectConfigurationResolver} for the configurations and classes the rule analyses.
 * {@see ObjectOptionAssignment} for the assignment semantics.
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

        $errors = [];

        $unknownOptions = ObjectOptionAssignment::unknownOptions(
            $classReflection,
            $configuration->options,
            $this->serviceMap,
            $scope,
        );

        foreach ($unknownOptions as [$name, $line]) {
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

        return $errors;
    }
}

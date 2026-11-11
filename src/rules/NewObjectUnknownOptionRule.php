<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\rules;

use PhpParser\Node;
use PhpParser\Node\Expr\New_;
use PHPStan\Analyser\{DependencyTracker, Scope};
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\{IdentifierRuleError, Rule, RuleErrorBuilder};
use yii\base\Component;
use yii2\extensions\phpstan\exception\Message;
use yii2\extensions\phpstan\ServiceMap;

/**
 * Reports configuration options of a direct `new` construction that Yii can't assign to the constructed object.
 *
 * Every key is assigned, so `class`, `__class` and `__construct()` are checked like any other name. For a
 * {@see Component} subclass, `on ...` and `as ...` options are never reported, and other options only when no behavior
 * can receive them.
 *
 * {@see Rule} for PHPStan rule contract.
 * {@see NewObjectConfigurationResolver} for the constructions and classes the rule analyses.
 * {@see ObjectOptionAssignment} for the assignment semantics.
 *
 * @implements Rule<New_>
 */
final class NewObjectUnknownOptionRule implements Rule
{
    /**
     * Error identifier reported by this rule.
     */
    private const string IDENTIFIER = 'yii2.new.unknownOption';

    /**
     * Resolves the configurations whose assignment path is certain.
     */
    private readonly NewObjectConfigurationResolver $configurationResolver;

    /**
     * Initializes the rule with class reflection and the analysis configuration.
     *
     * @param ReflectionProvider $reflectionProvider Provides class reflection for the constructed class.
     * @param ServiceMap $serviceMap Service and component map for Yii Application static analysis, read only for the
     * `behaviors` map.
     */
    public function __construct(ReflectionProvider $reflectionProvider, private readonly ServiceMap $serviceMap)
    {
        $this->configurationResolver = new NewObjectConfigurationResolver($reflectionProvider);
    }

    /**
     * Returns the node class this rule analyses.
     *
     * @return class-string<New_>
     */
    public function getNodeType(): string
    {
        return New_::class;
    }

    /**
     * Returns one error per distinct option that the constructed class can't assign.
     *
     * @param New_ $node Construction to inspect.
     * @param DependencyTracker&Scope $scope Current PHPStan analysis scope.
     *
     * @return list<IdentifierRuleError> Errors reported at the first key of each unknown option, or at the line of
     * `new` when the configuration doesn't locate the key.
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $configuration = $this->configurationResolver->resolve($node, $scope);

        if ($configuration === null) {
            return [];
        }

        $classReflection = $configuration->classReflection;

        $unknownOptions = ObjectOptionAssignment::unknownOptions(
            $classReflection,
            $configuration->options,
            $this->serviceMap,
            $scope,
        );

        $errors = [];

        foreach ($unknownOptions as [$name, $line]) {
            $errors[] = RuleErrorBuilder::message(
                Message::NEW_OBJECT_UNKNOWN_OPTION->getMessage($classReflection->getDisplayName(), $name),
            )
                ->identifier(self::IDENTIFIER)
                ->line($line)
                ->build();
        }

        return $errors;
    }
}

<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\rules;

use PhpParser\Node;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\{DependencyTracker, Scope};
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\{IdentifierRuleError, Rule, RuleErrorBuilder};
use yii\base\{Behavior, Component};
use yii2\extensions\phpstan\ServiceMap;

/**
 * Reports options of behavior configurations returned by {@see Component::behaviors()} that the created
 * {@see Behavior} can't assign.
 *
 * {@see BehaviorDefinitionResolver} for the configurations the rule reads and those it stands aside on.
 * {@see ObjectOptionAssignment} for the assignment semantics.
 *
 * @implements Rule<Return_>
 */
final class BehaviorsUnknownOptionRule implements Rule
{
    /**
     * Error identifier reported by this rule.
     */
    private const string IDENTIFIER = 'yii2.behavior.unknownOption';

    /**
     * Resolves the behavior options that Yii can't assign.
     */
    private readonly BehaviorDefinitionResolver $definitionResolver;

    /**
     * Initializes the rule with class reflection and the analysis configuration.
     *
     * @param ReflectionProvider $reflectionProvider Provides class reflection for owners and behavior classes.
     * @param ServiceMap $serviceMap Service and component map for Yii Application static analysis.
     */
    public function __construct(ReflectionProvider $reflectionProvider, ServiceMap $serviceMap)
    {
        $this->definitionResolver = new BehaviorDefinitionResolver($reflectionProvider, $serviceMap);
    }

    /**
     * Returns the node class this rule analyses.
     *
     * @return class-string<Return_>
     */
    public function getNodeType(): string
    {
        return Return_::class;
    }

    /**
     * Returns one error per behavior option that Yii can't assign.
     *
     * @param Return_ $node Return statement to inspect.
     * @param DependencyTracker&Scope $scope Current PHPStan analysis scope.
     *
     * @return list<IdentifierRuleError> Errors reported at the option key, else at the list item or the return line.
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $errors = [];

        foreach ($this->definitionResolver->resolveBehaviorsReturnUnknownOptions($node, $scope) as $option) {
            $errors[] = RuleErrorBuilder::message($option->message())
                ->identifier(self::IDENTIFIER)
                ->line($option->line)
                ->build();
        }

        return $errors;
    }
}

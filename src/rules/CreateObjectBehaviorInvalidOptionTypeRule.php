<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\rules;

use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\{DependencyTracker, Scope};
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\{IdentifierRuleError, Rule, RuleErrorBuilder};
use yii\base\Behavior;
use yii\BaseYii;
use yii2\extensions\phpstan\ServiceMap;

/**
 * Reports option values of behavior configurations in the `as` options of {@see BaseYii::createObject()}
 * configurations that the created {@see Behavior} never accepts.
 *
 * {@see BehaviorDefinitionResolver} for the configurations the rule reads and those it stands aside on.
 * {@see ObjectOptionAssignment} for the assignment semantics.
 * {@see CreateObjectConfigurationResolver::rejectedOptions()} for the type coercion semantics.
 *
 * @implements Rule<StaticCall>
 */
final class CreateObjectBehaviorInvalidOptionTypeRule implements Rule
{
    /**
     * Error identifier reported by this rule.
     */
    private const string IDENTIFIER = 'yii2.behavior.invalidOptionType';

    /**
     * Resolves the behavior options whose value the created behavior never accepts.
     */
    private readonly BehaviorDefinitionResolver $definitionResolver;

    /**
     * @param ReflectionProvider $reflectionProvider Provides class reflection for owners and behavior classes.
     * @param ServiceMap $serviceMap Service and component map for Yii Application static analysis.
     */
    public function __construct(ReflectionProvider $reflectionProvider, ServiceMap $serviceMap)
    {
        $this->definitionResolver = new BehaviorDefinitionResolver($reflectionProvider, $serviceMap);
    }

    public function getNodeType(): string
    {
        return StaticCall::class;
    }

    /**
     * Returns one error per behavior option whose value the created behavior never accepts.
     *
     * @param StaticCall $node Static call to inspect.
     * @param DependencyTracker&Scope $scope Current PHPStan analysis scope.
     *
     * @return list<IdentifierRuleError> Errors reported at the option key, else at the `as` option or the call line.
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $errors = [];

        foreach ($this->definitionResolver->resolveCreateObjectAsInvalidOptionTypes($node, $scope) as $option) {
            $errors[] = RuleErrorBuilder::message($option->message())
                ->identifier(self::IDENTIFIER)
                ->line($option->line)
                ->build();
        }

        return $errors;
    }
}

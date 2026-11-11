<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\rules;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\{DependencyTracker, Scope};
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\{IdentifierRuleError, Rule, RuleErrorBuilder};
use yii\base\Component;
use yii2\extensions\phpstan\ServiceMap;

/**
 * Reports behavior definitions passed to {@see Component::attachBehavior()} or {@see Component::attachBehaviors()}
 * that Yii can't attach.
 *
 * {@see BehaviorDefinitionResolver} for the definitions the rule reports and those it stands aside on.
 *
 * @implements Rule<MethodCall>
 */
final class AttachBehaviorInvalidDefinitionRule implements Rule
{
    /**
     * Error identifier reported by this rule.
     */
    private const string IDENTIFIER = 'yii2.behavior.invalidDefinition';

    /**
     * Resolves the definitions that Yii can't attach.
     */
    private readonly BehaviorDefinitionResolver $definitionResolver;

    /**
     * Initializes the rule with class reflection and the analysis configuration.
     *
     * @param ReflectionProvider $reflectionProvider Provides class reflection for receivers and resolved classes.
     * @param ServiceMap $serviceMap Service and component map for Yii Application static analysis.
     */
    public function __construct(ReflectionProvider $reflectionProvider, ServiceMap $serviceMap)
    {
        $this->definitionResolver = new BehaviorDefinitionResolver($reflectionProvider, $serviceMap);
    }

    /**
     * Returns the node class this rule analyses.
     *
     * @return class-string<MethodCall>
     */
    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    /**
     * Returns one error per passed definition that Yii can't attach.
     *
     * @param MethodCall $node Method call to inspect.
     * @param DependencyTracker&Scope $scope Current PHPStan analysis scope.
     *
     * @return list<IdentifierRuleError> Errors reported at the list item, else at the definition argument or the call
     * line.
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $errors = [];

        foreach ($this->definitionResolver->resolveAttachCall($node, $scope) as $definition) {
            $errors[] = RuleErrorBuilder::message($definition->message())
                ->identifier(self::IDENTIFIER)
                ->line($definition->line)
                ->build();
        }

        return $errors;
    }
}

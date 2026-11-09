<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\rules;

use PhpParser\Node;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\{DependencyTracker, Scope};
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\{IdentifierRuleError, Rule, RuleErrorBuilder};
use yii\base\Component;
use yii2\extensions\phpstan\ServiceMap;

/**
 * Reports behavior definitions returned by {@see Component::behaviors()} that Yii can't attach.
 *
 * {@see BehaviorDefinitionResolver} for the definitions the rule reports and those it stands aside on.
 *
 * @implements Rule<Return_>
 */
final class BehaviorsInvalidDefinitionRule implements Rule
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
     * @param ReflectionProvider $reflectionProvider Provides class reflection for owners and resolved classes.
     * @param ServiceMap $serviceMap Service and component map for Yii Application static analysis.
     */
    public function __construct(ReflectionProvider $reflectionProvider, ServiceMap $serviceMap)
    {
        $this->definitionResolver = new BehaviorDefinitionResolver($reflectionProvider, $serviceMap);
    }

    public function getNodeType(): string
    {
        return Return_::class;
    }

    /**
     * Returns one error per returned definition that Yii can't attach.
     *
     * @param Return_ $node Return statement to inspect.
     * @param DependencyTracker&Scope $scope Current PHPStan analysis scope.
     *
     * @return list<IdentifierRuleError> Errors reported at the list item, else at the return line.
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $errors = [];

        foreach ($this->definitionResolver->resolveBehaviorsReturn($node, $scope) as $definition) {
            $errors[] = RuleErrorBuilder::message($definition->message())
                ->identifier(self::IDENTIFIER)
                ->line($definition->line)
                ->build();
        }

        return $errors;
    }
}

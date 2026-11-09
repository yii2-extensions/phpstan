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
use yii2\extensions\phpstan\ServiceMap;

/**
 * Reports `as` options of {@see BaseYii::createObject()} configurations that the created {@see Component} rejects or
 * can't attach.
 *
 * {@see BehaviorDefinitionResolver} for the options the rule reports and those it stands aside on.
 *
 * @implements Rule<StaticCall>
 */
final class CreateObjectInvalidAsDefinitionRule implements Rule
{
    /**
     * Error identifier reported by this rule.
     */
    private const string IDENTIFIER = 'yii2.behavior.invalidAsDefinition';

    /**
     * Resolves the `as` options that Yii rejects or can't attach.
     */
    private readonly BehaviorDefinitionResolver $definitionResolver;

    /**
     * @param ReflectionProvider $reflectionProvider Provides class reflection for owners and named classes.
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
     * Returns one error per `as` option that Yii rejects or can't attach.
     *
     * @param StaticCall $node Static call to inspect.
     * @param DependencyTracker&Scope $scope Current PHPStan analysis scope.
     *
     * @return list<IdentifierRuleError> Errors reported at the option key, or at the call line when the configuration
     * doesn't locate the key.
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $errors = [];

        foreach ($this->definitionResolver->resolveCreateObjectAsOptions($node, $scope) as $definition) {
            $errors[] = RuleErrorBuilder::message($definition->message())
                ->identifier(self::IDENTIFIER)
                ->line($definition->line)
                ->build();
        }

        return $errors;
    }
}

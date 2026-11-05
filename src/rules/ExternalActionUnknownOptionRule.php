<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\rules;

use PhpParser\Node;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\{DependencyTracker, Scope};
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\{IdentifierRuleError, Rule, RuleErrorBuilder};
use yii\base\Controller;
use yii2\extensions\phpstan\exception\Message;
use yii2\extensions\phpstan\ServiceMap;

/**
 * Reports options of external action definitions returned by {@see Controller::actions()} that Yii can't assign to the
 * created action.
 *
 * `on ...` and `as ...` options are never reported, and other options only when no behavior can receive them.
 *
 * {@see ExternalActionConfigurationResolver} for the definitions the rule analyses.
 * {@see ObjectOptionAssignment} for the assignment semantics.
 *
 * @implements Rule<Return_>
 */
final class ExternalActionUnknownOptionRule implements Rule
{
    /**
     * Error identifier reported by this rule.
     */
    private const string IDENTIFIER = 'yii2.externalAction.unknownOption';

    /**
     * Resolves the definitions whose assignment path is certain.
     */
    private readonly ExternalActionConfigurationResolver $configurationResolver;

    /**
     * @param ReflectionProvider $reflectionProvider Provides class reflection for the created actions.
     * @param ServiceMap $serviceMap Service and component map for Yii Application static analysis.
     */
    public function __construct(ReflectionProvider $reflectionProvider, private readonly ServiceMap $serviceMap)
    {
        $this->configurationResolver = new ExternalActionConfigurationResolver($reflectionProvider, $serviceMap);
    }

    public function getNodeType(): string
    {
        return Return_::class;
    }

    /**
     * Returns one error per distinct option that the created action can't assign.
     *
     * @param Return_ $node Return statement to inspect.
     * @param DependencyTracker&Scope $scope Current PHPStan analysis scope.
     *
     * @return list<IdentifierRuleError> Errors reported at the first key of each option, else at the definition line,
     * else at the return line.
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $errors = [];

        foreach ($this->configurationResolver->resolve($node, $scope) as $action) {
            $classReflection = $action->configuration->classReflection;

            $unknownOptions = ObjectOptionAssignment::unknownOptions(
                $classReflection,
                $action->configuration->options,
                $this->serviceMap,
                $scope,
            );

            foreach ($unknownOptions as [$name, $line]) {
                $errors[] = RuleErrorBuilder::message(
                    Message::EXTERNAL_ACTION_UNKNOWN_OPTION->getMessage(
                        $action->actionId,
                        $action->controllerClass,
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
}

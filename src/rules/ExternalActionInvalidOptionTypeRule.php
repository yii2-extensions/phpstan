<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\rules;

use PhpParser\Node;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\{DependencyTracker, Scope};
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\{IdentifierRuleError, Rule, RuleErrorBuilder};
use PHPStan\Type\VerbosityLevel;
use yii\base\Controller;
use yii2\extensions\phpstan\exception\Message;
use yii2\extensions\phpstan\ServiceMap;

/**
 * Reports option values of external action definitions returned by {@see Controller::actions()} that the property or
 * setter Yii assigns them to never accepts, even with PHP's weak type coercion.
 *
 * {@see ExternalActionConfigurationResolver} for the definitions the rule analyses.
 * {@see CreateObjectConfigurationResolver::rejectedOptions()} for the type semantics.
 *
 * @implements Rule<Return_>
 */
final class ExternalActionInvalidOptionTypeRule implements Rule
{
    /**
     * Error identifier reported by this rule.
     */
    private const string IDENTIFIER = 'yii2.externalAction.invalidOptionType';

    /**
     * Resolves the definitions whose assignment path is certain.
     */
    private readonly ExternalActionConfigurationResolver $configurationResolver;

    /**
     * @param ReflectionProvider $reflectionProvider Provides class reflection for the created actions.
     * @param ServiceMap $serviceMap Service and component map for Yii Application static analysis.
     */
    public function __construct(ReflectionProvider $reflectionProvider, ServiceMap $serviceMap)
    {
        $this->configurationResolver = new ExternalActionConfigurationResolver($reflectionProvider, $serviceMap);
    }

    public function getNodeType(): string
    {
        return Return_::class;
    }

    /**
     * Returns one error per distinct option whose value the created action never accepts.
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
            $configuration = $action->configuration;

            $rejectedOptions = CreateObjectConfigurationResolver::rejectedOptions($configuration, $scope);

            foreach ($rejectedOptions as [$name, $line, $actualType, $expectedType, $member]) {
                $errors[] = RuleErrorBuilder::message(
                    Message::EXTERNAL_ACTION_INVALID_OPTION_TYPE->getMessage(
                        $action->actionId,
                        $action->controllerClass,
                        $configuration->classReflection->getDisplayName(),
                        $name,
                        $actualType->describe(VerbosityLevel::precise()),
                        $member,
                        $expectedType->describe(VerbosityLevel::typeOnly()),
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

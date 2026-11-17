<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\rules;

use PhpParser\Node;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\{DependencyTracker, Scope};
use PHPStan\Reflection\{ClassReflection, ReflectionProvider};
use PHPStan\Rules\{IdentifierRuleError, Rule, RuleErrorBuilder};
use PHPStan\Type\Type;
use yii2\extensions\phpstan\exception\Message;
use yii2\extensions\phpstan\ServiceMap;

use function in_array;
use function is_int;

/**
 * Reports unknown options in model validation rules using closures, qualified classes or Yii's declared built-in aliases.
 *
 * Assumes the declared alias table has not been changed at runtime. Method names and uncertain dispatches stand aside.
 * {@see ValidatorConfigurationResolver} for the dispatch and {@see ObjectOptionAssignment} for the assignment semantics.
 *
 * @implements Rule<Return_>
 */
final readonly class ValidatorUnknownOptionRule implements Rule
{
    /**
     * Resolves the validator class and configuration Yii constructs.
     */
    private readonly ValidatorConfigurationResolver $resolver;

    /**
     * Initializes static class reflection and the analysis service map.
     */
    public function __construct(ReflectionProvider $reflectionProvider, private readonly ServiceMap $serviceMap)
    {
        $this->resolver = new ValidatorConfigurationResolver($reflectionProvider, $serviceMap);
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
     * Returns diagnostics for unknown options explicitly written in model rules.
     *
     * @param Return_ $node Return statement to inspect.
     * @param DependencyTracker&Scope $scope Current PHPStan analysis scope.
     *
     * @return list<IdentifierRuleError> Errors at each rule's line.
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $owner = ModelRulesReader::owner($node, $scope);

        if ($owner === null || $node->expr === null) {
            return [];
        }

        $errors = [];
        $modelRulesReader = ModelRulesReader::entries($node->expr, $node->getStartLine(), $scope) ?? [];

        foreach ($modelRulesReader as [$index, $line, $rule]) {
            foreach ($this->unknown($owner, $rule, $line, $scope) as [$class, $option]) {
                $errors[] = RuleErrorBuilder::message(
                    Message::VALIDATOR_UNKNOWN_OPTION->getMessage(
                        is_int($index) ? "#{$index}" : "'{$index}'",
                        $owner->getDisplayName(),
                        $class,
                        $option,
                    ),
                )->identifier('yii2.validator.unknownOption')->line($line)->build();
            }
        }

        return $errors;
    }

    /**
     * @param DependencyTracker&Scope $scope Current analysis scope.
     *
     * @return list<array{string, string}> Created class and option name of each unknown option written in the rule.
     */
    private function unknown(ClassReflection $owner, Type $rule, int $line, Scope $scope): array
    {
        $validator = $this->resolver->resolve($owner, $rule, $line, $scope);

        if ($validator === null) {
            return [];
        }

        $configuration = $validator->configuration;

        $unknown = [];

        foreach (
            ObjectOptionAssignment::unknownOptions(
                $configuration->classReflection,
                $configuration->options,
                $this->serviceMap,
                $scope,
            ) as [$option]
        ) {
            if (in_array($option, $validator->writtenOptions, true)) {
                $unknown[] = [$configuration->classReflection->getDisplayName(), $option];
            }
        }

        return $unknown;
    }
}

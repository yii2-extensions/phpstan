<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\rules;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\New_;
use PHPStan\Analyser\{DependencyTracker, Scope};
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\{IdentifierRuleError, Rule, RuleErrorBuilder};
use PHPStan\Type\VerbosityLevel;
use yii\BaseYii;
use yii\di\Instance;
use yii2\extensions\phpstan\exception\Message;

/**
 * Reports configuration values of a direct `new` construction that the property or setter Yii assigns them to never
 * accepts.
 *
 * A value is reported only when PHP's weak type coercion, which applies inside {@see BaseYii::configure()} and `__set()`
 * whatever the `strict_types` mode of the constructing file, can't convert it to the native type of the public
 * property or of the first setter parameter. PHPDoc types are not consulted. An {@see Instance} value is assigned as
 * that object, since `new` doesn't resolve container references.
 *
 * {@see Rule} for PHPStan rule contract.
 * {@see NewObjectConfigurationResolver} for the constructions and classes the rule analyses.
 * {@see ObjectOptionAssignment} for the assignment semantics.
 *
 * @implements Rule<New_>
 */
final class NewObjectInvalidOptionTypeRule implements Rule
{
    /**
     * Error identifier reported by this rule.
     */
    private const string IDENTIFIER = 'yii2.new.invalidOptionType';

    /**
     * Resolves the configurations whose assignment path is certain.
     */
    private readonly NewObjectConfigurationResolver $configurationResolver;

    /**
     * Initializes the rule with class reflection.
     *
     * @param ReflectionProvider $reflectionProvider Provides class reflection for the constructed class.
     */
    public function __construct(ReflectionProvider $reflectionProvider)
    {
        $this->configurationResolver = new NewObjectConfigurationResolver($reflectionProvider);
    }

    public function getNodeType(): string
    {
        return New_::class;
    }

    /**
     * Returns one error per distinct option whose value the constructed class never accepts.
     *
     * @param New_ $node Construction to inspect.
     * @param DependencyTracker&Scope $scope Current PHPStan analysis scope.
     *
     * @return list<IdentifierRuleError> Errors reported at the first key of each option, or at the line of `new` when
     * the configuration doesn't locate the key.
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $configuration = $this->configurationResolver->resolve($node, $scope);

        if ($configuration === null) {
            return [];
        }

        $classReflection = $configuration->classReflection;
        $errors = [];

        foreach ($configuration->options as [$name, $line, $value]) {
            $actualType = $value instanceof Expr ? $scope->getNativeType($value) : $value;

            $target = ObjectOptionAssignment::rejectingTarget($classReflection, $name, $actualType, $scope);

            if ($target === null) {
                continue;
            }

            [$expectedType, $member] = $target;

            $errors[] = RuleErrorBuilder::message(
                Message::NEW_OBJECT_INVALID_OPTION_TYPE->getMessage(
                    $classReflection->getDisplayName(),
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

        return $errors;
    }
}

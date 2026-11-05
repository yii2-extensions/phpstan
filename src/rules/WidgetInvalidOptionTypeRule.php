<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\rules;

use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\{DependencyTracker, Scope};
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\{IdentifierRuleError, Rule, RuleErrorBuilder};
use PHPStan\Type\VerbosityLevel;
use yii\base\Widget;
use yii\di\Instance;
use yii2\extensions\phpstan\exception\Message;
use yii2\extensions\phpstan\ServiceMap;

/**
 * Reports {@see Widget::widget()} and {@see Widget::begin()} option values that the property or setter Yii assigns them
 * to never accepts.
 *
 * A value is reported only when PHP's weak type coercion can't convert it to the native type of the public property or
 * of the first setter parameter. PHPDoc types are not consulted. A value that may be an {@see Instance} reference is
 * also checked as the object or `null` that the container may resolve it to before the assignment. The `class` and
 * `__class` keys with which `Yii::createObject()` selects the class are never checked; inside a constructor argument
 * used as the configuration they are checked like any other option.
 *
 * {@see Rule} for PHPStan rule contract.
 * {@see WidgetConfigurationResolver} for the calls and classes the rule analyses.
 * {@see CreateObjectConfigurationResolver::rejectedOptions()} for the type semantics.
 *
 * @implements Rule<StaticCall>
 */
final class WidgetInvalidOptionTypeRule implements Rule
{
    /**
     * Error identifier reported by this rule.
     */
    private const string IDENTIFIER = 'yii2.widget.invalidOptionType';

    /**
     * Resolves the configurations whose assignment path is certain.
     */
    private readonly WidgetConfigurationResolver $configurationResolver;

    /**
     * Initializes the rule with class reflection and the analysis configuration.
     *
     * @param ReflectionProvider $reflectionProvider Provides class reflection for the called and created classes.
     * @param ServiceMap $serviceMap Service and component map for Yii Application static analysis.
     */
    public function __construct(ReflectionProvider $reflectionProvider, ServiceMap $serviceMap)
    {
        $this->configurationResolver = new WidgetConfigurationResolver($reflectionProvider, $serviceMap);
    }

    public function getNodeType(): string
    {
        return StaticCall::class;
    }

    /**
     * Returns one error per distinct option whose value the created widget never accepts.
     *
     * @param StaticCall $node Static call to inspect.
     * @param DependencyTracker&Scope $scope Current PHPStan analysis scope.
     *
     * @return list<IdentifierRuleError> Errors reported at the first key of each option, or at the call line when the
     * configuration doesn't locate the key.
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $widget = $this->configurationResolver->resolve($node, $scope);

        if ($widget === null) {
            return [];
        }

        $configuration = $widget->configuration;
        $errors = [];

        $rejectedOptions = CreateObjectConfigurationResolver::rejectedOptions($configuration, $scope);

        foreach ($rejectedOptions as [$name, $line, $actualType, $expectedType, $member]) {
            $errors[] = RuleErrorBuilder::message(
                Message::WIDGET_INVALID_OPTION_TYPE->getMessage(
                    $configuration->calledClass,
                    $widget->method,
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

        return $errors;
    }
}

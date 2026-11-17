<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\rules;

use Closure;
use PHPStan\Analyser\{DependencyTracker, Scope};
use PHPStan\Reflection\{ClassReflection, ReflectionProvider};
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\{MixedType, ObjectType, Type};
use yii\base\Component;
use yii\validators\{InlineValidator, Validator};
use yii2\extensions\phpstan\ServiceMap;

use function array_keys;
use function array_map;
use function array_merge;
use function array_slice;
use function is_array;
use function is_string;
use function str_contains;
use function strval;

/**
 * Resolves the validator class and options that {@see Validator::createValidator()} passes to `Yii::createObject()`
 * for a `Model::rules()` entry using a closure, a qualified class name or one of Yii's declared built-in aliases.
 *
 * Assumes the declared alias table has not been changed at runtime. Method names, other unqualified names, qualified
 * names under an overridden `hasMethod()` and incomplete or uncertain rules stand aside. Container definitions and the
 * created class follow {@see CreateObjectConfigurationResolver::resolveTypeOptions()}.
 */
final readonly class ValidatorConfigurationResolver
{
    /**
     * Resolves the class and configuration Yii constructs.
     */
    private CreateObjectConfigurationResolver $resolver;

    /**
     * Initializes static class reflection and the analysis service map.
     */
    public function __construct(
        private ReflectionProvider $reflectionProvider,
        ServiceMap $serviceMap,
    ) {
        $this->resolver = new CreateObjectConfigurationResolver($reflectionProvider, $serviceMap);
    }

    /**
     * Returns the configuration of the validator that Yii creates for the rule, or `null` when it isn't proven.
     *
     * Records the declaration and source file dependencies of {@see Validator} before reading its declared alias table,
     * and the dependencies of {@see CreateObjectConfigurationResolver::resolveTypeOptions()}.
     *
     * @param ClassReflection $owner Model whose `rules()` returns the rule.
     * @param Type $rule Native type of the rule.
     * @param int $line Line reported for the rule's options.
     * @param DependencyTracker&Scope $scope Current PHPStan analysis scope.
     */
    public function resolve(ClassReflection $owner, Type $rule, int $line, Scope $scope): ValidatorConfiguration|null
    {
        $values = ModelRulesReader::ruleValues($rule);

        // incomplete rules belong to yii2.validator.incompleteRule
        if ($values === null || isset($values[0], $values[1]) === false || $values[0]->isNull()->no() === false) {
            return null;
        }

        $type = $values[1];

        $inline = (new ObjectType(Closure::class))->isSuperTypeOf($type)->yes();

        $name = $inline ? null : ObjectConfigurationReader::constantString($type);

        $alias = null;

        if ($inline === false) {
            $scope->trackClassDependency(Validator::class);

            // Read declaration defaults, never the mutable runtime table configured by a consumer bootstrap.
            $validator = $this->reflectionProvider->getClass(Validator::class);

            $file = $validator->getFileName();

            if ($file !== null) {
                // Class signature dependencies do not include property initializer values.
                $scope->trackFileDependency($file);
            }

            $defaults = $validator->getNativeReflection()->getDefaultProperties();

            $aliases = $defaults['builtInValidators'] ?? null;

            $alias = $name !== null && is_array($aliases) ? ($aliases[$name] ?? null) : null;

            // Unknown unqualified names may be model or behavior methods, or aliases added at runtime.
            if ($name === null || ($alias === null && str_contains($name, '\\') === false)) {
                return null;
            }

            // Built-in aliases take priority over model methods, including overridden hasMethod().
            if (
                $alias === null && (
                    $owner->hasNativeMethod('hasMethod') === false
                    || $owner->getNativeMethod('hasMethod')
                        ->getDeclaringClass()
                        ->getName() !== Component::class
                )
            ) {
                return null;
            }
        }

        // createValidator(): array_slice($rule, 2) by position, then the keys it sets, on a PHP array of types
        $params = array_slice($values, 2);
        $written = array_map(strval(...), array_keys($params));

        $params['attributes'] = $values[0]->toArray();

        if ($inline) {
            $params['class'] = new ConstantStringType(InlineValidator::class);
            $params['method'] = $type;
        } else {
            unset($params['current']);
            if (is_array($alias)) {
                $aliasOptions = [];

                // Only a class value selects the validator; other defaults are neither written nor reported.
                foreach ($alias as $key => $value) {
                    $aliasOptions[$key] = is_string($value) ? new ConstantStringType($value) : new MixedType();
                }

                $params = array_merge($aliasOptions, $params);
            } else {
                $params['class'] = new ConstantStringType(is_string($alias) ? $alias : $name);
            }
        }

        $options = [];

        foreach ($params as $key => $value) {
            $options[] = [(string) $key, $line, $value];
        }

        // the class value Yii::createObject() selects: `__class` when present, else `class`
        $classValue = $params['__class'] ?? $params['class'] ?? null;

        $id = $classValue === null ? null : ObjectConfigurationReader::constantString($classValue);

        if ($id === null) {
            return null;
        }

        $configuration = $this->resolver->resolveTypeOptions('Yii', $options, $line, $scope);

        if ($configuration === null || $configuration->classReflection->is(Validator::class) === false) {
            return null;
        }

        return new ValidatorConfiguration($id, $configuration, $written);
    }
}

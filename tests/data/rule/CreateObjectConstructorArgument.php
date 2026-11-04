<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule;

use Yii;
use yii\di\Instance;
use yii2\extensions\phpstan\tests\support\createobject\{
    ArgumentsTarget,
    ComponentBehavior,
    ComponentMembersTarget,
    CustomConstructorTarget,
    Target,
    TypedTarget,
    TypedValue,
};

/**
 * `Yii::createObject()` calls with constructor arguments for both option rules, one per method.
 */
final class CreateObjectConstructorArgument
{
    /**
     * Name of the `$config` parameter, used as a constant key of the constructor arguments.
     */
    private const string CONFIG = 'config';

    public function aliasToDefinitionParams(): object
    {
        return Yii::createObject(
            [
                'class' => 'arguments.alias',
                'colour' => 'red',
            ],
        );
    }

    public function aliasToDefinitionParamsReplaced(): object
    {
        return Yii::createObject(
            'arguments.alias',
            [
                [
                    'colour' => 'red',
                ],
            ],
        );
    }

    public function classIdDefinitionParams(): object
    {
        return Yii::createObject(
            [
                'class' => ArgumentsTarget::class,
                'colour' => 'red',
            ],
        );
    }

    public function classIdDefinitionParamsReplaced(): object
    {
        return Yii::createObject(
            ArgumentsTarget::class,
            [
                [
                    'colour' => 'red',
                ],
            ],
        );
    }

    public function componentBehaviorInArgument(): object
    {
        return Yii::createObject(
            ComponentMembersTarget::class,
            [
                [
                    'as colouring' => ComponentBehavior::class,
                    'colour' => 'red',
                ],
            ],
        );
    }

    public function componentParams(): object
    {
        return Yii::createObject(
            ComponentMembersTarget::class,
            [
                [
                    'colour' => 'red',
                ],
            ],
        );
    }

    public function componentParamsInvalidValue(): object
    {
        return Yii::createObject(
            ComponentMembersTarget::class,
            [
                [
                    'count' => 'abc',
                ],
            ],
        );
    }

    public function constantParams(): object
    {
        $params = [['colour' => 'red']];

        return Yii::createObject(Target::class, $params);
    }

    public function construct(): object
    {
        return Yii::createObject(
            [
                'class' => Target::class,
                '__construct()' => [
                    [
                        'colour' => 'red',
                    ],
                ],
            ],
        );
    }

    public function constructAndParamsMixed(): object
    {
        return Yii::createObject(
            [
                'class' => Target::class,
                '__construct()' => [
                    'config' => [
                        'colour' => 'red',
                    ],
                ],
            ],
            [
                [
                    'name' => 'param',
                ],
            ],
        );
    }

    public function constructEmptyWithOptions(): object
    {
        return Yii::createObject(
            [
                'class' => Target::class,
                '__construct()' => [],
                'colour' => 'red',
            ],
        );
    }

    public function constructIgnoredForOptions(): object
    {
        return Yii::createObject(
            [
                'class' => Target::class,
                '__construct()' => [
                    [
                        'name' => 'ignored',
                    ],
                ],
                'colour' => 'red',
            ],
        );
    }

    public function constructInvalidValue(): object
    {
        return Yii::createObject(
            [
                'class' => TypedTarget::class,
                '__construct()' => [
                    [
                        'count' => 'abc',
                    ],
                ],
            ],
        );
    }

    public function constructLegacyClass(): object
    {
        return Yii::createObject(
            [
                '__class' => Target::class,
                '__construct()' => [
                    [
                        'colour' => 'red',
                    ],
                ],
            ],
        );
    }

    public function constructNamed(): object
    {
        return Yii::createObject(
            [
                'class' => Target::class,
                '__construct()' => [
                    'config' => [
                        'colour' => 'red',
                    ],
                ],
            ],
        );
    }

    public function constructNull(): object
    {
        return Yii::createObject(
            [
                'class' => Target::class,
                '__construct()' => null,
                'colour' => 'red',
            ],
        );
    }

    public function constructTwoArguments(): object
    {
        return Yii::createObject(
            [
                'class' => Target::class,
                '__construct()' => [
                    [
                        'colour' => 'red',
                    ],
                    'second',
                ],
            ],
        );
    }

    public function constructValid(): object
    {
        return Yii::createObject(
            [
                'class' => Target::class,
                '__construct()' => [
                    [
                        'name' => 'constructor',
                    ],
                ],
            ],
        );
    }

    public function customConstructorParams(): object
    {
        return Yii::createObject(
            CustomConstructorTarget::class,
            [
                [
                    'colour' => 'red',
                ],
            ],
        );
    }

    public function definitionConstructNull(): object
    {
        return Yii::createObject(
            [
                'class' => 'arguments.constructNull',
                'colour' => 'red',
            ],
        );
    }

    public function definitionConstructReplaced(): object
    {
        return Yii::createObject(
            [
                'class' => 'arguments.construct',
                '__construct()' => [
                    [
                        'colour' => 'red',
                    ],
                ],
            ],
        );
    }

    public function definitionConstructReplacedByParams(): object
    {
        return Yii::createObject(
            'arguments.construct',
            [
                [
                    'colour' => 'red',
                ],
            ],
        );
    }

    public function definitionConstructSuppliesConfiguration(): object
    {
        return Yii::createObject(
            'arguments.construct',
        );
    }

    public function definitionConstructTwoReplaced(): object
    {
        return Yii::createObject(
            [
                'class' => 'arguments.constructTwo',
                '__construct()' => [
                    [
                        'colour' => 'red',
                    ],
                ],
            ],
        );
    }

    public function definitionConstructTwoWithOptions(): object
    {
        return Yii::createObject(
            [
                'class' => 'arguments.constructTwo',
                'colour' => 'red',
            ],
        );
    }

    public function definitionConstructWithOptions(): object
    {
        return Yii::createObject(
            [
                'class' => 'arguments.construct',
                'colour' => 'red',
            ],
        );
    }

    public function definitionCustomConstructor(): object
    {
        return Yii::createObject(
            [
                'class' => 'arguments.customConstructor',
                'colour' => 'red',
            ],
        );
    }

    public function definitionHopNamedParams(): object
    {
        return Yii::createObject(
            [
                'class' => 'arguments.hopNamed',
                'colour' => 'red',
            ],
        );
    }

    public function definitionHopSecondParam(): object
    {
        return Yii::createObject(
            [
                'class' => 'arguments.hop',
                'colour' => 'red',
            ],
        );
    }

    public function definitionNamedParamsMixed(): object
    {
        return Yii::createObject(
            'arguments.named',
            [
                [
                    'colour' => 'red',
                ],
            ],
        );
    }

    public function definitionNamedParamsReplaced(): object
    {
        return Yii::createObject(
            'arguments.named',
            [
                'config' => [
                    'colour' => 'red',
                ],
            ],
        );
    }

    public function definitionOptionsDropParams(): object
    {
        return Yii::createObject(
            'binding.optionsWithParams',
            [
                [
                    'colour' => 'red',
                ],
            ],
        );
    }

    public function definitionParamsBeforeConstruct(): object
    {
        return Yii::createObject(
            [
                'class' => 'arguments.params',
                '__construct()' => [
                    [
                        'colour' => 'red',
                    ],
                ],
            ],
        );
    }

    public function definitionParamsInvalidValue(): object
    {
        return Yii::createObject(
            'arguments.typed',
            [
                [
                    'count' => 'abc',
                ],
            ],
        );
    }

    public function definitionParamsReplaced(): object
    {
        return Yii::createObject(
            'arguments.params',
            [
                [
                    'colour' => 'red',
                ],
            ],
        );
    }

    public function definitionParamsTwoWithOptions(): object
    {
        return Yii::createObject(
            [
                'class' => 'arguments.paramsTwo',
                'colour' => 'red',
            ],
        );
    }

    public function definitionParamsWithOptions(): object
    {
        return Yii::createObject(
            [
                'class' => 'arguments.params',
                'colour' => 'red',
            ],
        );
    }

    public function definitionParamsWithOptionsInvalidValue(): object
    {
        return Yii::createObject(
            [
                'class' => 'arguments.typed',
                'count' => 'abc',
            ],
        );
    }

    public function definitionSingletonParams(): object
    {
        return Yii::createObject(
            'arguments.singleton',
            [
                [
                    'colour' => 'red',
                ],
            ],
        );
    }

    public function dynamicParams(): object
    {
        return Yii::createObject(Target::class, $this->params());
    }

    public function namedCallArguments(): object
    {
        return Yii::createObject(
            params: [
                [
                    'colour' => 'red',
                ],
            ],
            type: Target::class,
        );
    }

    public function optionsInstanceInvalidValue(): object
    {
        return Yii::createObject(
            [
                'class' => TypedTarget::class,
                'items' => $this->reference(),
            ],
        );
    }

    public function optionsInstanceNullableValue(): object
    {
        return Yii::createObject(
            [
                'class' => ArgumentsTarget::class,
                'list' => $this->reference(),
            ],
        );
    }

    public function optionsInstanceValue(): object
    {
        return Yii::createObject(
            [
                'class' => TypedTarget::class,
                'value' => $this->reference(),
            ],
        );
    }

    public function paramsAndConstructNamed(): object
    {
        return Yii::createObject(
            [
                'class' => Target::class,
                '__construct()' => [
                    'config' => [
                        'name' => 'constructor',
                    ],
                ],
            ],
            [
                'config' => [
                    'colour' => 'red',
                ],
            ],
        );
    }

    public function paramsBeforeConstruct(): object
    {
        return Yii::createObject(
            [
                'class' => Target::class,
                '__construct()' => [
                    [
                        'name' => 'constructor',
                    ],
                ],
            ],
            [
                [
                    'colour' => 'red',
                ],
            ],
        );
    }

    public function paramsClassKeys(): object
    {
        return Yii::createObject(
            Target::class,
            [
                [
                    'class' => Target::class,
                    'colour' => 'red',
                ],
            ],
        );
    }

    public function paramsConstantKey(): object
    {
        return Yii::createObject(
            Target::class,
            [
                self::CONFIG => [
                    'colour' => 'red',
                ],
            ],
        );
    }

    public function paramsConstructKey(): object
    {
        return Yii::createObject(
            Target::class,
            [
                [
                    '__construct()' => [],
                ],
            ],
        );
    }

    public function paramsDuplicateArgument(): object
    {
        return Yii::createObject(
            Target::class,
            [
                0 => [
                    'name' => 'first',
                ],
                0 => [
                    'colour' => 'red',
                ],
            ],
        );
    }

    public function paramsIgnoredForOptions(): object
    {
        return Yii::createObject(
            [
                'class' => Target::class,
                'colour' => 'red',
            ],
            [
                [
                    'name' => 'ignored',
                ],
            ],
        );
    }

    public function paramsIgnoredForOptionsInvalidValue(): object
    {
        return Yii::createObject(
            [
                'class' => TypedTarget::class,
                'count' => 'abc',
            ],
            [
                [
                    'title' => 'ignored',
                ],
            ],
        );
    }

    public function paramsInstanceValue(): object
    {
        return Yii::createObject(
            TypedTarget::class,
            [
                [
                    'value' => $this->reference(),
                ],
            ],
        );
    }

    public function paramsIntegerOptionKey(): object
    {
        return Yii::createObject(
            Target::class,
            [
                [
                    0 => 'red',
                ],
            ],
        );
    }

    public function paramsInvalidValue(): object
    {
        return Yii::createObject(
            TypedTarget::class,
            [
                [
                    'count' => 'abc',
                ],
            ],
        );
    }

    public function paramsLegacyClass(): object
    {
        return Yii::createObject(
            [
                '__class' => Target::class,
                'class' => 'Missing\Target',
            ],
            [
                [
                    'colour' => 'red',
                ],
            ],
        );
    }

    public function paramsMixedKeys(): object
    {
        return Yii::createObject(
            Target::class,
            [
                [
                    'name' => 'param',
                ],
                'config' => [
                    'colour' => 'red',
                ],
            ],
        );
    }

    public function paramsNamed(): object
    {
        return Yii::createObject(
            Target::class,
            [
                'config' => [
                    'colour' => 'red',
                ],
            ],
        );
    }

    public function paramsNamedIgnoredForOptions(): object
    {
        return Yii::createObject(
            [
                'class' => Target::class,
                'colour' => 'red',
            ],
            [
                'config' => [],
            ],
        );
    }

    public function paramsNull(): object
    {
        return Yii::createObject(
            Target::class,
            [
                null,
            ],
        );
    }

    public function paramsOtherName(): object
    {
        return Yii::createObject(
            Target::class,
            [
                'options' => [
                    'colour' => 'red',
                ],
            ],
        );
    }

    public function paramsPositional(): object
    {
        return Yii::createObject(
            Target::class,
            [
                [
                    'colour' => 'red',
                ],
            ],
        );
    }

    public function paramsScalarIgnoredForOptions(): object
    {
        return Yii::createObject(
            [
                'class' => Target::class,
                'colour' => 'red',
            ],
            [
                'scalar',
            ],
        );
    }

    public function paramsSecondPosition(): object
    {
        return Yii::createObject(
            Target::class,
            [
                1 => [
                    'colour' => 'red',
                ],
            ],
        );
    }

    public function paramsStringZeroKey(): object
    {
        return Yii::createObject(
            Target::class,
            [
                '0' => [
                    'colour' => 'red',
                ],
            ],
        );
    }

    public function paramsTwoArguments(): object
    {
        return Yii::createObject(
            Target::class,
            [
                [
                    'colour' => 'red',
                ],
                'second',
            ],
        );
    }

    public function paramsTwoArgumentsDropOptions(): object
    {
        return Yii::createObject(
            [
                'class' => Target::class,
                'colour' => 'red',
            ],
            [
                [
                    'name' => 'param',
                ],
                'second',
            ],
        );
    }

    public function paramsUnpackedArgument(): object
    {
        return Yii::createObject(
            Target::class,
            [
                ...[
                    [
                        'colour' => 'red',
                    ],
                ],
            ],
        );
    }

    public function paramsUnpackedArguments(): object
    {
        return Yii::createObject(...[Target::class, [['colour' => 'red']]]);
    }

    public function paramsValid(): object
    {
        return Yii::createObject(
            Target::class,
            [
                [
                    'name' => 'param',
                ],
            ],
        );
    }

    public function paramsWithClassConfiguration(): object
    {
        return Yii::createObject(
            [
                'class' => Target::class,
            ],
            [
                [
                    'colour' => 'red',
                ],
            ],
        );
    }

    public function paramsWithUnionKeys(): object
    {
        $params = $this->params() === [] ? [] : [['colour' => 'red']];

        return Yii::createObject(Target::class, $params);
    }

    public function variableClassName(): object
    {
        $class = Target::class;

        return Yii::createObject(
            $class,
            [
                [
                    'colour' => 'red',
                ],
            ],
        );
    }

    private function params(): array
    {
        return [['colour' => 'red']];
    }

    private function reference(): Instance
    {
        return Instance::of(TypedValue::class);
    }
}

<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule;

use yii\di\Instance;
use yii2\extensions\phpstan\tests\support\createobject\{
    ComponentBehaviorsTarget,
    ComponentCustomConstructorTarget,
    ComponentMappedTarget,
    ComponentMembersTarget,
    CustomConstructorTarget,
    Target,
    TypedTarget,
    TypedValue,
};

/**
 * Direct `new` constructions whose class, arguments, configuration or assignment the rules can't prove.
 */
final class NewObjectStandAside
{
    private string $key = 'colour';

    private bool $toggle = true;

    public function anonymousClass(): object
    {
        return new class (['colour' => 'red']) extends Target {};
    }

    public function behaviorsOverrideReceivesOption(): object
    {
        return new ComponentBehaviorsTarget(['colour' => 'red']);
    }

    public function behaviorsOverrideUnknownOption(): object
    {
        return new ComponentBehaviorsTarget(['shade' => 'dark']);
    }

    public function componentCustomConstructor(): object
    {
        return new ComponentCustomConstructorTarget(['alias' => 'value', 'colour' => 'red']);
    }

    public function componentMappedBehavior(): object
    {
        return new ComponentMappedTarget(['shade' => 'dark']);
    }

    public function componentSetterBeforeOption(): object
    {
        return new ComponentMembersTarget(['attach' => true, 'colour' => 'red']);
    }

    public function customConstructorConsumesOption(): object
    {
        return new CustomConstructorTarget(['alias' => 'value']);
    }

    public function customConstructorUnknownOption(): object
    {
        return new CustomConstructorTarget(['colour' => 'red']);
    }

    public function documentedConfiguration(): object
    {
        return new Target($this->documented());
    }

    public function dynamicClass(): object
    {
        $class = Target::class;

        return new $class(['colour' => 'red']);
    }

    public function expressionClass(): object
    {
        return new (Target::class)(['colour' => 'red']);
    }

    public function extraArgument(): object
    {
        return new Target(['colour' => 'red'], 'extra');
    }

    public function getterOnly(): object
    {
        return new Target(['count' => 1]);
    }

    public function instanceForStringProperty(): object
    {
        return new TypedTarget(['title' => $this->reference()]);
    }

    public function privateSetter(): object
    {
        return new Target(['hidden' => 'value']);
    }

    public function selfInAnonymousClass(): object
    {
        $factory = new class extends Target {
            public function create(): object
            {
                return new self(['colour' => 'red']);
            }
        };

        return $factory->create();
    }

    public function unionOfShapes(): object
    {
        $config = $this->toggle ? ['colour' => 'red'] : ['name' => 'value'];

        return new Target($config);
    }

    public function unknownNamedArgument(): object
    {
        return new Target(options: ['colour' => 'red']);
    }

    public function unpackedArgument(): object
    {
        return new Target(...[['colour' => 'red']]);
    }

    public function unpackedNamedArgument(): object
    {
        return new Target(...['config' => ['colour' => 'red']]);
    }

    public function variableKey(): object
    {
        return new Target([$this->key => 'red']);
    }

    /**
     * @return array{colour: string}
     */
    private function documented(): array
    {
        return ['colour' => 'red'];
    }

    private function reference(): Instance
    {
        return Instance::of(TypedValue::class);
    }
}

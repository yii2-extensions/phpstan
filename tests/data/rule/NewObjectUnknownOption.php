<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule;

use yii2\extensions\phpstan\tests\support\createobject\{
    AliasTarget,
    ChildTarget,
    ComponentMembersTarget,
    ComponentTarget,
    Target,
    Target as ConfiguredTarget,
};

/**
 * Direct `new` constructions whose configuration names an option that the constructed class can't assign.
 */
final class NewObjectUnknownOption
{
    private const array CONFIG = ['name' => 'constant', 'colour' => 'red'];

    private const string OPTION = 'colour';

    public function aliasedClass(): object
    {
        return new ConfiguredTarget(['colour' => 'red']);
    }

    public function annotatedProperty(): object
    {
        return new Target(['documented' => 'value']);
    }

    public function boundClass(): object
    {
        return new AliasTarget(['label' => 'value']);
    }

    public function caseMismatchedProperty(): object
    {
        return new Target(['Name' => 'value']);
    }

    public function classKey(): object
    {
        return new Target(['class' => Target::class]);
    }

    public function componentClassKey(): object
    {
        return new ComponentTarget(['name' => 'value', 'class' => ComponentTarget::class]);
    }

    public function componentUnknownOption(): object
    {
        return new ComponentMembersTarget(['count' => 1, 'colour' => 'red']);
    }

    public function constantConfiguration(): object
    {
        return new Target(self::CONFIG);
    }

    public function constantKey(): object
    {
        return new Target([self::OPTION => 'red']);
    }

    public function constructKey(): object
    {
        return new Target(['__construct()' => [['name' => 'value']]]);
    }

    public function inheritedClass(): object
    {
        return new ChildTarget(['shade' => 'dark', 'colour' => 'red']);
    }

    public function legacyClassKey(): object
    {
        return new Target(['__class' => Target::class]);
    }

    public function localVariable(): object
    {
        $config = ['name' => 'value'];
        $config['colour'] = 'red';

        return new Target($config);
    }

    public function multilineKey(): object
    {
        return new Target(['name' => 'value', 'colour' => 'red']);
    }

    public function namedConfig(): object
    {
        return new Target(config: ['colour' => 'red']);
    }

    public function privateProperty(): object
    {
        return new Target(['secret' => 'value']);
    }

    public function protectedProperty(): object
    {
        return new Target(['internal' => 'value']);
    }

    public function unknownOption(): object
    {
        return new Target(['name' => 'value', 'colour' => 'red']);
    }
}

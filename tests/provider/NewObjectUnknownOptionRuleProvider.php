<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

use yii2\extensions\phpstan\tests\data\rule\{NewObjectSelfReference, NewObjectUnknownOption};
use yii2\extensions\phpstan\tests\support\createobject\{
    AliasTarget,
    ChildTarget,
    ComponentMembersTarget,
    ComponentTarget,
    Target,
};

use function sprintf;

/**
 * Data provider for {@see \yii2\extensions\phpstan\tests\rules\NewObjectUnknownOptionRuleTest} test cases.
 */
final class NewObjectUnknownOptionRuleProvider
{
    /**
     * @phpstan-return iterable<string, array{class-string, string, list<array{string|null, string}>}>
     */
    public static function reportedProvider(): iterable
    {
        $fixture = NewObjectUnknownOption::class;

        yield 'imported alias' => [
            $fixture,
            'aliasedClass',
            [["'colour'", self::message(Target::class, 'colour')]],
        ];
        yield 'annotated property' => [
            $fixture,
            'annotatedProperty',
            [["'documented'", self::message(Target::class, 'documented')]],
        ];
        yield 'container binding of the same name' => [
            $fixture,
            'boundClass',
            [["'label'", self::message(AliasTarget::class, 'label')]],
        ];
        yield 'case-mismatched property' => [
            $fixture,
            'caseMismatchedProperty',
            [["'Name'", self::message(Target::class, 'Name')]],
        ];
        yield 'class key' => [
            $fixture,
            'classKey',
            [["'class'", self::message(Target::class, 'class')]],
        ];
        yield 'component class key' => [
            $fixture,
            'componentClassKey',
            [["'class'", self::message(ComponentTarget::class, 'class')]],
        ];
        yield 'component unknown option' => [
            $fixture,
            'componentUnknownOption',
            [["'colour'", self::message(ComponentMembersTarget::class, 'colour')]],
        ];
        yield 'constant configuration' => [
            $fixture,
            'constantConfiguration',
            [[null, self::message(Target::class, 'colour')]],
        ];
        yield 'constant key' => [
            $fixture,
            'constantKey',
            [['self::OPTION', self::message(Target::class, 'colour')]],
        ];
        yield '__construct() key' => [
            $fixture,
            'constructKey',
            [["'__construct()'", self::message(Target::class, '__construct()')]],
        ];
        yield 'inherited class' => [
            $fixture,
            'inheritedClass',
            [["'colour'", self::message(ChildTarget::class, 'colour')]],
        ];
        yield '__class key' => [
            $fixture,
            'legacyClassKey',
            [["'__class'", self::message(Target::class, '__class')]],
        ];
        yield 'local variable' => [
            $fixture,
            'localVariable',
            [[null, self::message(Target::class, 'colour')]],
        ];
        yield 'multiline key' => [
            $fixture,
            'multilineKey',
            [["'colour'", self::message(Target::class, 'colour')]],
        ];
        yield 'named config' => [
            $fixture,
            'namedConfig',
            [["'colour'", self::message(Target::class, 'colour')]],
        ];
        yield 'private property' => [
            $fixture,
            'privateProperty',
            [["'secret'", self::message(Target::class, 'secret')]],
        ];
        yield 'protected property' => [
            $fixture,
            'protectedProperty',
            [["'internal'", self::message(Target::class, 'internal')]],
        ];
        yield 'unknown option' => [
            $fixture,
            'unknownOption',
            [["'colour'", self::message(Target::class, 'colour')]],
        ];

        $self = NewObjectSelfReference::class;

        yield 'self in a closure' => [
            $self,
            'closureSelf',
            [],
        ];
        yield 'parent' => [
            $self,
            'parentClass',
            [["'shade'", self::message(Target::class, 'shade')]],
        ];
        yield 'self' => [
            $self,
            'selfClass',
            [["'colour'", self::message($self, 'colour')]],
        ];
        yield 'self with an assignable option' => [
            $self,
            'selfValid',
            [],
        ];
        yield 'static in a final class' => [
            $self,
            'staticClass',
            [["'colour'", self::message($self, 'colour')]],
        ];
    }

    /**
     * Returns the diagnostic message for an option that the constructed class can't assign.
     */
    private static function message(string $class, string $name): string
    {
        return sprintf(
            'new %s() sets unknown property $%s: the class has no public property or setter with that name.',
            $class,
            $name,
        );
    }
}

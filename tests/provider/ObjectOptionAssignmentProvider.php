<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

use PHPStan\Type\{ArrayType, IntegerType, MixedType, ObjectType, ObjectWithoutClassType, Type};
use PHPStan\Type\Constant\ConstantStringType;
use yii2\extensions\phpstan\tests\data\rule\action\hook\HookedIdAction;
use yii2\extensions\phpstan\tests\data\rule\traitproperty\{
    CountObject,
    CountTraitObject,
    InheritedPaintTriggerComponent,
    InheritedWideCountObject,
    NestedPaintTriggerComponent,
    NestedWideCountObject,
    PaintTriggerComponent,
    ProtectedCountObject,
    ProtectedTriggerComponent,
    PublicCountObject,
    PublicSecretObject,
    PublicTriggerComponent,
    SecretObject,
    ShadowedPaintTriggerComponent,
    ShadowedWideCountObject,
    TriggerComponent,
    TriggerTraitComponent,
    WideCountObject,
};
use yii2\extensions\phpstan\tests\support\createobject\{
    ComponentBehaviorsTarget,
    ComponentEnsureBehaviorsTarget,
    ComponentEventTarget,
    ComponentMappedChildTarget,
    ComponentMembersTarget,
    ComponentTarget,
    Target,
    TypedTarget,
};

/**
 * Data provider for {@see \yii2\extensions\phpstan\tests\rules\ObjectOptionAssignmentTest} test cases.
 *
 * Provides option names, value types and expected outcomes of the shared option semantics, without a rule boundary.
 */
final class ObjectOptionAssignmentProvider
{
    /**
     * PHP 8.4 fixture, named by string because PHPStan parses the analysed sources as PHP 8.3, which has no asymmetric
     * visibility.
     */
    private const string VISIBILITY_HOOK_TARGET = 'yii2\\extensions\\phpstan\\tests\\data\\rule\\assignment\\VisibilityHookTarget';

    /**
     * @phpstan-return iterable<string, array{string, string, Type, array{string, string}|null}>
     */
    public static function hookedTargetProvider(): iterable
    {
        $string = new ConstantStringType('x');

        yield 'set hook' => [
            self::VISIBILITY_HOOK_TARGET,
            'hooked',
            $string,
            null,
        ];
        yield 'private(set)' => [
            self::VISIBILITY_HOOK_TARGET,
            'privateSet',
            $string,
            null,
        ];
        yield 'protected(set)' => [
            self::VISIBILITY_HOOK_TARGET,
            'protectedSet',
            $string,
            null,
        ];
        yield 'plain control' => [
            self::VISIBILITY_HOOK_TARGET,
            'plain',
            $string,
            ['int', 'the property'],
        ];
    }

    /**
     * @phpstan-return iterable<string, array{string, list<string>, list<string>}>
     */
    public static function hookedUnknownOptionProvider(): iterable
    {
        yield 'hooked and asymmetric visibility properties' => [
            self::VISIBILITY_HOOK_TARGET,
            ['hooked', 'privateSet', 'protectedSet', 'plain'],
            [],
        ];
        yield 'option after a set hook' => [
            HookedIdAction::class,
            ['id', 'colour'],
            [],
        ];
        yield 'option after a plain property control' => [
            HookedIdAction::class,
            ['count', 'colour'],
            ['colour'],
        ];
    }

    /**
     * @phpstan-return iterable<string, array{class-string, string, bool}>
     */
    public static function redeclarationProvider(): iterable
    {
        yield 'property without trait' => [
            Target::class,
            'name',
            false,
        ];
        yield 'property declared only by a used trait' => [
            Target::class,
            'tag',
            false,
        ];
        yield 'Component property without trait' => [
            ComponentTarget::class,
            'name',
            false,
        ];
    }

    /**
     * @phpstan-return iterable<string, array{class-string, string, Type, array{string, string}|null}>
     */
    public static function targetProvider(): iterable
    {
        $setter = static fn(string $method): string => TypedTarget::class . "::{$method}()";

        $array = new ArrayType(new MixedType(), new MixedType());
        $string = new ConstantStringType('x');

        yield 'public property, non-numeric string' => [
            TypedTarget::class,
            'count',
            new ConstantStringType('all'),
            ['int', 'the property'],
        ];
        yield 'public property, numeric string' => [
            TypedTarget::class,
            'count',
            new ConstantStringType('5'),
            null,
        ];
        yield 'public property, integer' => [
            TypedTarget::class,
            'count',
            new IntegerType(),
            null,
        ];
        yield 'public property over its setter' => [
            TypedTarget::class,
            'both',
            $array,
            ['int', 'the property'],
        ];
        yield 'public property over its setter, accepted' => [
            TypedTarget::class,
            'both',
            new IntegerType(),
            null,
        ];
        yield 'protected property through its setter' => [
            TypedTarget::class,
            'guardedCount',
            new IntegerType(),
            [
                'array',
                $setter('setGuardedCount'),
            ],
        ];
        yield 'static property through its setter' => [
            TypedTarget::class,
            'shared',
            new IntegerType(),
            ['array', $setter('setShared')],
        ];
        yield 'setter, case-insensitive name' => [
            TypedTarget::class,
            'SIZE',
            $string,
            ['int', $setter('setSize')],
        ];
        yield 'setter with two parameters' => [
            TypedTarget::class,
            'pair',
            $string,
            ['int', $setter('setPair')],
        ];
        yield 'variadic setter' => [
            TypedTarget::class,
            'values',
            $string,
            ['int', $setter('setValues')],
        ];
        yield 'nullable setter' => [
            TypedTarget::class,
            'label',
            $array,
            ['string|null', $setter('setLabel')],
        ];
        yield 'callable setter, string PHPStan does not know as callable' => [
            TypedTarget::class,
            'handler',
            new ConstantStringType('Probe\\undefinedFunction'),
            null,
        ];
        yield 'callable setter, integer' => [
            TypedTarget::class,
            'handler',
            new IntegerType(),
            ['callable(): mixed', $setter('setHandler')],
        ];
        yield 'string setter, object that may be Stringable' => [
            TypedTarget::class,
            'text',
            new ObjectWithoutClassType(),
            null,
        ];
        yield 'string setter, non-final class without __toString()' => [
            TypedTarget::class,
            'text',
            new ObjectType(Target::class),
            null,
        ];
        yield 'read-only property' => [
            TypedTarget::class,
            'frozen',
            $string,
            null,
        ];
        yield 'mixed property' => [
            TypedTarget::class,
            'untyped',
            $array,
            null,
        ];
        yield 'setter without parameters' => [
            TypedTarget::class,
            'nothing',
            $string,
            null,
        ];
        yield 'private setter' => [
            TypedTarget::class,
            'secret',
            $string,
            null,
        ];
        yield 'empty name' => [
            TypedTarget::class,
            '',
            $string,
            null,
        ];
        yield 'unknown name' => [
            TypedTarget::class,
            'colour',
            $string,
            null,
        ];
        yield 'case-sensitive property name' => [
            TypedTarget::class,
            'COUNT',
            new ConstantStringType('all'),
            null,
        ];
    }

    /**
     * @phpstan-return iterable<string, array{class-string, string, bool}>
     */
    public static function traitRedeclarationProvider(): iterable
    {
        yield 'plain property' => [
            CountObject::class,
            'count',
            false,
        ];
        yield 'identical trait redeclaration' => [
            CountTraitObject::class,
            'count',
            false,
        ];
        yield 'trait with a set hook' => [
            WideCountObject::class,
            'count',
            true,
        ];
        yield 'trait with a set hook, inherited' => [
            InheritedWideCountObject::class,
            'count',
            true,
        ];
        yield 'trait with a set hook through a nested trait' => [
            NestedWideCountObject::class,
            'count',
            true,
        ];
        yield 'plain child of a class whose trait has a set hook' => [
            ShadowedWideCountObject::class,
            'count',
            true,
        ];
        yield 'trait as the only declaration' => [
            ProtectedCountObject::class,
            'count',
            false,
        ];
        yield 'plain child of a class whose trait has other visibility' => [
            PublicCountObject::class,
            'count',
            false,
        ];
        yield 'trait with other visibility' => [
            PublicSecretObject::class,
            'secret',
            true,
        ];
        yield 'set hook trait on a Component' => [
            PaintTriggerComponent::class,
            'trigger',
            true,
        ];
        yield 'identical trait on a Component' => [
            TriggerTraitComponent::class,
            'trigger',
            false,
        ];
    }

    /**
     * @phpstan-return iterable<string, array{class-string, string, Type, array{string, string}|null}>
     */
    public static function traitTargetProvider(): iterable
    {
        $array = new ArrayType(new MixedType(), new MixedType());

        yield 'plain property' => [
            CountObject::class,
            'count',
            $array,
            ['int', 'the property'],
        ];
        yield 'identical trait redeclaration' => [
            CountTraitObject::class,
            'count',
            $array,
            ['int', 'the property'],
        ];
        yield 'widening set hook trait' => [
            WideCountObject::class,
            'count',
            $array,
            null,
        ];
        yield 'widening set hook trait, inherited' => [
            InheritedWideCountObject::class,
            'count',
            $array,
            null,
        ];
        yield 'widening set hook, nested trait' => [
            NestedWideCountObject::class,
            'count',
            $array,
            null,
        ];
        yield 'plain child of a widening trait class' => [
            ShadowedWideCountObject::class,
            'count',
            $array,
            null,
        ];
        yield 'explicit plain child property' => [
            PublicCountObject::class,
            'count',
            $array,
            ['int', 'the property'],
        ];
    }

    /**
     * @phpstan-return iterable<string, array{class-string, list<string>, list<string>}>
     */
    public static function traitUnknownOptionProvider(): iterable
    {
        yield 'trait with other visibility' => [
            PublicSecretObject::class,
            ['secret'],
            [],
        ];
        yield 'plain protected property control' => [
            SecretObject::class,
            ['secret'],
            ['secret']
        ];
        yield 'option after a trait set hook' => [
            PaintTriggerComponent::class,
            ['trigger', 'colour'],
            [],
        ];
        yield 'option after an inherited trait set hook' => [
            InheritedPaintTriggerComponent::class,
            ['trigger', 'colour'],
            [],
        ];
        yield 'option after a nested trait set hook' => [
            NestedPaintTriggerComponent::class,
            ['trigger', 'colour'],
            [],
        ];
        yield 'option after a plain child of a trait set hook class' => [
            ShadowedPaintTriggerComponent::class,
            ['trigger', 'colour'],
            [],
        ];
        yield 'option after a plain property' => [
            TriggerComponent::class,
            ['trigger', 'colour'],
            ['colour'],
        ];
        yield 'option after an identical trait property' => [
            TriggerTraitComponent::class,
            ['trigger', 'colour'],
            ['colour'],
        ];
        yield 'option after an explicit plain child property' => [
            PublicTriggerComponent::class,
            ['trigger', 'colour'],
            ['colour'],
        ];
        yield 'protected trait property' => [
            ProtectedTriggerComponent::class,
            ['trigger', 'colour'],
            ['trigger', 'colour'],
        ];
    }

    /**
     * @phpstan-return iterable<string, array{class-string, list<string>, list<string>}>
     */
    public static function unknownOptionProvider(): iterable
    {
        yield 'public, static and protected static properties' => [
            Target::class,
            ['name', 'shared', 'registry'],
            [],
        ];
        yield 'protected and private properties without setter' => [
            Target::class,
            ['internal', 'secret'],
            ['internal', 'secret'],
        ];
        yield 'setters of any visibility, trait members and getter-only names' => [
            Target::class,
            ['label', 'guarded', 'hidden', 'tag', 'tagged', 'count'],
            [],
        ];
        yield 'case-sensitive property, case-insensitive setter' => [
            Target::class,
            ['Name', 'LABEL'],
            ['Name'],
        ];
        yield 'empty and unknown names, in order' => [
            Target::class,
            ['zeta', '', 'colour'],
            ['zeta', 'colour'],
        ];
        yield 'read-only property' => [
            TypedTarget::class,
            ['frozen'],
            [],
        ];
        yield 'Component, event without code and unknown option' => [
            ComponentMembersTarget::class,
            ['on change', 'colour'],
            ['colour'],
        ];
        yield 'Component, behavior option' => [
            ComponentMembersTarget::class,
            ['colour', 'as paint'],
            [],
        ];
        yield 'Component, option before a setter' => [
            ComponentMembersTarget::class,
            ['colour', 'limit'],
            ['colour'],
        ];
        yield 'Component, option after a setter' => [
            ComponentMembersTarget::class,
            ['limit', 'colour'],
            [],
        ];
        yield 'Component, option after a private setter' => [
            ComponentMembersTarget::class,
            ['hidden', 'colour'],
            []
        ];
        yield 'Component, option after a plain property' => [
            ComponentMembersTarget::class,
            ['name', 'colour'],
            ['colour'],
        ];
        yield 'Component, protected property without setter' => [
            ComponentMembersTarget::class,
            ['internal'],
            ['internal'],
        ];
        yield 'Component, option after an event with an overridden on()' => [
            ComponentEventTarget::class,
            ['on change', 'colour'],
            [],
        ];
        yield 'Component with an overridden behaviors()' => [
            ComponentBehaviorsTarget::class,
            ['colour'],
            [],
        ];
        yield 'Component with an overridden ensureBehaviors()' => [
            ComponentEnsureBehaviorsTarget::class,
            ['colour'],
            [],
        ];
        yield 'Component with a mapped ancestor' => [
            ComponentMappedChildTarget::class,
            ['colour'],
            [],
        ];
        yield 'Component without behaviors' => [
            ComponentTarget::class,
            ['colour'],
            ['colour'],
        ];
    }
}

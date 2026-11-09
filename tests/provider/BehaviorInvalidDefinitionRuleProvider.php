<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

use yii\base\Component;
use yii2\extensions\phpstan\tests\data\rule\behavior\{
    BehaviorsListTrait,
    ConstantListBehaviorsOwner,
    DynamicBehaviorsOwner,
    EnsureOverrideBehaviorsOwner,
    InheritedDocBehaviorsOwner,
    NestedReturnBehaviorsOwner,
    NotComponentBehaviors,
    ReportedBehaviorsOwner,
    StandAsideBehaviorsOwner,
    TraitBehaviorsOwner,
    ValidBehaviorsOwner,
};
use yii2\extensions\phpstan\tests\support\behavior\{
    BehaviorOwner,
    MappedAwayBehavior,
    NotAttachableObject,
    PlainClass,
    PrivateStaticAttachClass,
    ProtectedAttachClass,
    ProtectedAttachObject,
    ProtectedStaticAttachObject,
};

use function array_slice;
use function sprintf;

/**
 * Data provider for {@see \yii2\extensions\phpstan\tests\rules\BehaviorsInvalidDefinitionRuleTest} and
 * {@see \yii2\extensions\phpstan\tests\rules\AttachBehaviorInvalidDefinitionRuleTest} test cases.
 */
final class BehaviorInvalidDefinitionRuleProvider
{
    /**
     * Returns the expected message for a configuration array without a class.
     */
    public static function arrayMessage(string $behavior, string $owner, string $method): string
    {
        return sprintf(
            "Behavior %s in %s::%s() is a configuration array without a non-null '__class' or 'class' element, which "
            . 'Yii::createObject() rejects.',
            $behavior,
            $owner,
            $method,
        );
    }

    /**
     * Returns the expected diagnostics of `AttachCalls::reported()`, the only reported method of its file.
     *
     * @phpstan-return list<array{string, string|null, string}>
     */
    public static function attachCallsReported(): array
    {
        $owner = BehaviorOwner::class;

        return [
            [
                'NotAttachableObject::class,',
                "'notAttachable',",
                self::classMessage(
                    "'notAttachable'",
                    $owner,
                    'attachBehavior',
                    NotAttachableObject::class,
                ),
            ],
            [
                'MappedAwayBehavior::class,',
                "'mappedAway',",
                self::resolvedMessage(
                    "'mappedAway'",
                    $owner,
                    'attachBehavior',
                    MappedAwayBehavior::class,
                    NotAttachableObject::class,
                ),
            ],
            [
                "['label' => 'value'],",
                "'noClass',",
                self::arrayMessage(
                    "'noClass'",
                    $owner,
                    'attachBehavior',
                ),
            ],
            [
                'PlainClass::class,',
                '$name,',
                self::classMessage(
                    'with a non-constant name',
                    $owner,
                    'attachBehavior',
                    PlainClass::class,
                ),
            ],
            [
                'behavior: ProtectedAttachObject::class,',
                null,
                self::classMessage(
                    "'named'",
                    $owner,
                    'attachBehavior',
                    ProtectedAttachObject::class,
                ),
            ],
            [
                'PlainClass::class,',
                "'onComponent',",
                self::classMessage(
                    "'onComponent'",
                    Component::class,
                    'attachBehavior',
                    PlainClass::class,
                ),
            ],
            [
                'NotAttachableObject::class,',
                "'multiLine',",
                self::classMessage(
                    "'multiLine'",
                    $owner,
                    'attachBehavior',
                    NotAttachableObject::class,
                ),
            ],
            [
                "'listed' =>",
                null,
                self::classMessage(
                    "'listed'",
                    $owner,
                    'attachBehaviors',
                    PlainClass::class,
                ),
            ],
            [
                "['__class' =>",
                null,
                self::classMessage(
                    '#0',
                    $owner,
                    'attachBehaviors',
                    NotAttachableObject::class,
                ),
            ],
            [
                'self::DEFINITIONS)',
                null,
                self::classMessage(
                    "'constant'",
                    $owner,
                    'attachBehaviors',
                    NotAttachableObject::class,
                ),
            ],
            [
                "'7' =>",
                null,
                self::classMessage(
                    '#7',
                    $owner,
                    'attachBehaviors',
                    PlainClass::class,
                ),
            ],
            [
                'NotAttachableObject::class,',
                "'7' =>",
                self::classMessage(
                    '#8',
                    $owner,
                    'attachBehaviors',
                    NotAttachableObject::class,
                ),
            ],
        ];
    }

    /**
     * Returns the expected message for a class that Yii can't attach.
     */
    public static function classMessage(string $behavior, string $owner, string $method, string $class): string
    {
        return sprintf(
            'Behavior %s in %s::%s() uses %s, which Yii cannot attach: it is not a yii\base\Behavior and has no public '
            . 'attach() method.',
            $behavior,
            $owner,
            $method,
            $class,
        );
    }

    /**
     * @phpstan-return iterable<string, array{class-string, list<array{string, string|null, string}>}>
     */
    public static function reportedProvider(): iterable
    {
        $owner = ReportedBehaviorsOwner::class;

        $classes = [
            'notAttachable' => NotAttachableObject::class,
            'plainClass' => PlainClass::class,
            'privateStaticAttach' => PrivateStaticAttachClass::class,
            'protectedAttach' => ProtectedAttachObject::class,
            'protectedAttachClass' => ProtectedAttachClass::class,
            'protectedStaticAttach' => ProtectedStaticAttachObject::class,
        ];

        $named = [];

        foreach ($classes as $name => $class) {
            $named[] = [
                "'{$name}' =>",
                null,
                self::classMessage(
                    "'{$name}'",
                    $owner,
                    'behaviors',
                    $class,
                ),
            ];
        }

        // in the fixture's list order, which is the report order
        yield 'literal list with item lines' => [
            $owner,
            [
                [
                    "'aliasChain' =>",
                    null,
                    self::resolvedMessage(
                        "'aliasChain'",
                        $owner,
                        'behaviors',
                        'notAttachableAlias',
                        PlainClass::class,
                    ),
                ],
                [
                    "'configured' =>",
                    null,
                    self::classMessage(
                        "'configured'",
                        $owner,
                        'behaviors',
                        NotAttachableObject::class,
                    ),
                ],
                [
                    "'dunderWins' =>",
                    null,
                    self::classMessage(
                        "'dunderWins'",
                        $owner,
                        'behaviors',
                        PlainClass::class,
                    ),
                ],
                [
                    "'duplicate' => NotAttachableObject::class",
                    null,
                    self::classMessage(
                        "'duplicate'",
                        $owner,
                        'behaviors',
                        NotAttachableObject::class,
                    ),
                ],
                [
                    "'emptyArray' =>",
                    null,
                    self::arrayMessage(
                        "'emptyArray'",
                        $owner,
                        'behaviors',
                    ),
                ],
                [
                    "'leadingSlash' =>",
                    null,
                    self::classMessage(
                        "'leadingSlash'",
                        $owner,
                        'behaviors',
                        NotAttachableObject::class,
                    ),
                ],
                [
                    "'mappedAway' =>",
                    null,
                    self::resolvedMessage(
                        "'mappedAway'",
                        $owner,
                        'behaviors',
                        MappedAwayBehavior::class,
                        NotAttachableObject::class,
                    ),
                ],
                $named[0],
                [
                    "'notCallable' =>",
                    null,
                    self::arrayMessage(
                        "'notCallable'",
                        $owner,
                        'behaviors',
                    ),
                ],
                [
                    "'nullClass' =>",
                    null,
                    self::arrayMessage(
                        "'nullClass'",
                        $owner,
                        'behaviors',
                    ),
                ],
                ...array_slice($named, 1),
                [
                    '            PlainClass::class,',
                    null,
                    self::classMessage(
                        '#0',
                        $owner,
                        'behaviors',
                        PlainClass::class,
                    ),
                ],
            ],
        ];

        yield 'constant list at the return line' => [
            ConstantListBehaviorsOwner::class,
            [
                [
                    'return $definitions;',
                    null,
                    self::classMessage(
                        "'notAttachable'",
                        ConstantListBehaviorsOwner::class,
                        'behaviors',
                        NotAttachableObject::class,
                    ),
                ],
            ],
        ];
    }

    /**
     * Returns the expected message for a definition that the container resolves to a class Yii can't attach.
     */
    public static function resolvedMessage(
        string $behavior,
        string $owner,
        string $method,
        string $definedClass,
        string $resolvedClass,
    ): string {
        return sprintf(
            'Behavior %s in %s::%s() uses %s, which the container resolves to %s; Yii cannot attach it: it is not a '
            . 'yii\base\Behavior and has no public attach() method.',
            $behavior,
            $owner,
            $method,
            $definedClass,
            $resolvedClass,
        );
    }

    /**
     * @phpstan-return iterable<string, array{list<class-string>}>
     */
    public static function standAsideProvider(): iterable
    {
        yield 'array without class under the inherited declared type' => [[InheritedDocBehaviorsOwner::class]];
        yield 'attachable definitions, including static and magic attach()' => [[ValidBehaviorsOwner::class]];
        yield 'behaviors() declared in a trait' => [[TraitBehaviorsOwner::class, BehaviorsListTrait::class]];
        yield 'class that is not a component' => [[NotComponentBehaviors::class]];
        yield 'closure return inside behaviors()' => [[NestedReturnBehaviorsOwner::class]];
        yield 'ensureBehaviors() override' => [[EnsureOverrideBehaviorsOwner::class]];
        yield 'unpacked and merged lists' => [[DynamicBehaviorsOwner::class]];
        yield 'unproven definitions' => [[StandAsideBehaviorsOwner::class]];
    }
}

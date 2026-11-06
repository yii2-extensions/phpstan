<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

use PhpParser\Node\ArrayItem;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\{Array_, ClassConstFetch, Variable};
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\{Int_, String_};
use PHPStan\Type\{ArrayType, IntegerType, MixedType, ObjectType, StringType, Type};
use PHPStan\Type\Constant\{ConstantArrayTypeBuilder, ConstantIntegerType, ConstantStringType};
use yii\di\Instance;
use yii2\extensions\phpstan\tests\data\rule\action\hook\HookedIdAction;
use yii2\extensions\phpstan\tests\data\rule\traitproperty\{
    PaintTriggerComponent,
    TriggerAction,
    TriggerComponent,
    TriggerTraitAction,
    TriggerTraitComponent,
};
use yii2\extensions\phpstan\tests\support\action\{
    ChildAction,
    ConstructBoundAction,
    OptionsBoundAction,
    ParamsBoundAction,
    PlainIdAction,
    SetterBoundAction,
    TargetAction,
};
use yii2\extensions\phpstan\tests\support\createobject\{
    AbstractTarget,
    AliasTarget,
    ChildTarget,
    CustomConstructorTarget,
    CustomSetterTarget,
    DefinitionOptionsTarget,
    Target,
    TypedTarget,
};

use function is_array;
use function is_int;
use function is_string;

/**
 * Data provider for {@see \yii2\extensions\phpstan\tests\rules\CreateObjectConfigurationResolverTest} test cases.
 *
 * Provides `$type` options as value types, with the configuration Yii assigns or `null` when the resolver stands aside.
 */
final class CreateObjectConfigurationResolverProvider
{
    /**
     * PHP 8.4 fixture, named by string because PHPStan parses the analysed sources as PHP 8.3, which has no asymmetric
     * visibility.
     */
    private const string VISIBILITY_HOOK_TARGET = 'yii2\\extensions\\phpstan\\tests\\data\\rule\\assignment\\VisibilityHookTarget';

    /**
     * @phpstan-return iterable<string, array{list<array{string, Type}>, array{string, list<array{string, int, string}>}|null}>
     */
    public static function actionProvider(): iterable
    {
        yield 'Action subclass' => [
            [
                ['class', self::name(TargetAction::class)],
                ['name', self::name('x')],
            ],
            [TargetAction::class, [['name', 3, "'x'"]]],
        ];
        yield 'definition with plain public property options' => [
            [
                ['class', self::name(OptionsBoundAction::class)],
                ['shade', self::name('x')],
            ],
            [ChildAction::class, [['shade', 3, "'x'"]]],
        ];
        yield 'definition option on a setter' => [
            [['class', self::name(SetterBoundAction::class)]],
            null,
        ];
        yield 'definition with params' => [
            [['class', self::name(ParamsBoundAction::class)]],
            null,
        ];
        yield 'definition with `__construct()`' => [
            [['class', self::name(ConstructBoundAction::class)]],
            null,
        ];
        yield '`__construct()` option' => [
            [
                ['class', self::name(TargetAction::class)],
                ['__construct()', self::list(['bound'])],
            ],
            null,
        ];
        yield 'class that is not an Action' => [
            [['class', self::name(Target::class)]],
            null,
        ];
        yield '`id` declared by a trait' => [
            [['class', self::name(PlainIdAction::class)]],
            null,
        ];
    }

    /**
     * @phpstan-return iterable<string, array{Expr, Type, int}>
     */
    public static function constructLiteralProvider(): iterable
    {
        $configuration = static fn(): Array_ => new Array_(
            [new ArrayItem(new String_('y'), new String_('colour'), false, ['startLine' => 5])],
        );
        $positional = self::list([['colour' => 'y']]);

        yield 'list item' => [
            new Array_([new ArrayItem($configuration())]),
            $positional,
            5,
        ];
        yield 'integer key' => [
            new Array_([new ArrayItem($configuration(), new Int_(0))]),
            $positional,
            5,
        ];
        yield 'numeric string key' => [
            new Array_([new ArrayItem($configuration(), new String_('0'))]),
            $positional,
            5,
        ];
        yield 'named key' => [
            new Array_([new ArrayItem($configuration(), new String_('config'))]),
            self::list(['config' => ['colour' => 'y']]),
            5,
        ];
        yield 'unpacked item, located at the call' => [
            new Array_([new ArrayItem(new Variable('arguments'), null, false, [], true)]),
            $positional,
            9,
        ];
        yield 'item under a non-literal key, located at the call' => [
            new Array_([new ArrayItem($configuration(), new Variable('key'))]),
            $positional,
            9,
        ];
    }

    /**
     * @phpstan-return iterable<string, array{Expr, Type, string|null}>
     */
    public static function expressionProvider(): iterable
    {
        $mixed = new MixedType();

        yield 'string literal' => [
            new String_(Target::class),
            $mixed,
            Target::class,
        ];
        yield 'empty string literal' => [
            new String_(''),
            $mixed,
            null,
        ];
        yield '`::class` of a name' => [
            new ClassConstFetch(new Name(Target::class), 'class'),
            $mixed,
            Target::class,
        ];
        yield '`static::class`' => [
            new ClassConstFetch(new Name('static'), 'class'),
            self::name(Target::class),
            null,
        ];
        yield 'expression with one constant class name' => [
            new Variable('class'),
            self::name(Target::class),
            Target::class,
        ];
        yield 'expression not proven to be one string' => [
            new Variable('class'),
            new StringType(),
            null,
        ];
        yield 'expression with an empty string' => [
            new Variable('class'),
            self::name(''),
            null,
        ];
    }

    /**
     * @phpstan-return iterable<
     *   string,
     *   array{list<array{string, Type}>, array{string, list<array{string, int, string}>}|null}
     * >
     */
    public static function hookedActionProvider(): iterable
    {
        yield 'hooked `id`' => [
            [['class', self::name(HookedIdAction::class)]],
            null,
        ];
    }

    /**
     * @phpstan-return iterable<
     *   string,
     *   array{list<array{string, Type}>, array{string, list<array{string, int, string}>}|null}
     * >
     */
    public static function hookedDefinitionProvider(): iterable
    {
        yield 'plain property definition option' => [
            [
                ['class', self::name('assignment.plain')],
                ['colour', self::name('x')],
            ],
            [self::VISIBILITY_HOOK_TARGET, [['colour', 3, "'x'"]]],
        ];
        yield 'hooked property definition option' => [
            [['class', self::name('assignment.hooked')]],
            null,
        ];
        yield 'private(set) definition option' => [
            [['class', self::name('assignment.privateSet')]],
            null,
        ];
        yield 'protected(set) definition option' => [
            [['class', self::name('assignment.protectedSet')]],
            null,
        ];
    }

    /**
     * @phpstan-return iterable<
     *   string,
     *   array{class-string, list<array{string, Type}>, list<array{string, int, string, string, string}>}
     * >
     */
    public static function rejectedProvider(): iterable
    {
        $instance = new ObjectType(Instance::class);

        yield 'rejected values in order, accepted ones skipped' => [
            TypedTarget::class,
            [
                ['count', new ConstantStringType('all')],
                ['title', new ConstantStringType('x')],
                ['enabled', new ArrayType(new MixedType(), new MixedType())],
            ],
            [
                ['count', 2, "'all'", 'int', 'the property'],
                ['enabled', 4, 'array', 'bool', 'the property'],
            ],
        ];
        yield '`Instance` that may resolve to an accepted object' => [
            TypedTarget::class,
            [['value', $instance]],
            [],
        ];
        yield '`Instance` that may resolve to an accepted string' => [
            TypedTarget::class,
            [['title', $instance]],
            [],
        ];
        yield '`Instance` for a type that no resolved value has' => [
            TypedTarget::class,
            [['count', $instance]],
            [['count', 2, Instance::class, 'int', 'the property']],
        ];
        yield 'unknown option' => [
            TypedTarget::class,
            [['colour', new ConstantStringType('x')]],
            [],
        ];
    }

    /**
     * @phpstan-return iterable<
     *   string,
     *   array{list<array{string, Type}>, array{string, list<array{string, int, string}>}|null}
     * >
     */
    public static function resolveProvider(): iterable
    {
        yield 'unbound class, class key removed' => [
            [
                ['class', self::name(Target::class)],
                ['name', self::name('x')],
                ['colour', self::name('red')],
            ],
            [Target::class, [['name', 3, "'x'"], ['colour', 4, "'red'"]]],
        ];
        yield '`__class` over `class`, both removed' => [
            [
                ['class', self::name(ChildTarget::class)],
                ['__class', self::name(Target::class)],
                ['name', self::name('x')],
            ],
            [Target::class, [['name', 4, "'x'"]]],
        ];
        yield 'no class key' => [
            [['name', self::name('x')]],
            null,
        ];
        yield 'empty class' => [
            [['class', self::name('')]],
            null,
        ];
        yield 'class not proven to be one string' => [
            [['class', new StringType()]],
            null,
        ];
        yield 'missing class' => [
            [['class', self::name('Missing\Target')]],
            null,
        ];
        yield 'abstract class' => [
            [['class', self::name(AbstractTarget::class)]],
            null,
        ];
        yield 'custom constructor' => [
            [['class', self::name(CustomConstructorTarget::class)]],
            null,
        ];
        yield 'own `__set()`' => [
            [['class', self::name(CustomSetterTarget::class)]],
            null,
        ];
        yield 'alias binding' => [
            [
                ['class', self::name(AliasTarget::class)],
                ['colour', self::name('x')],
            ],
            [Target::class, [['colour', 3, "'x'"]]],
        ];
        yield 'class-only ID chain' => [
            [
                ['class', self::name('binding.second')],
                ['colour', self::name('x')],
            ],
            [Target::class, [['colour', 3, "'x'"]]],
        ];
        yield 'factory' => [
            [['class', self::name('binding.factory')]],
            null,
        ];
        yield 'singleton' => [
            [['class', self::name('binding.singleton')]],
            null,
        ];
        yield 'object' => [
            [['class', self::name('binding.object')]],
            null,
        ];
        yield 'definition cycle' => [
            [['class', self::name('binding.cycleFirst')]],
            null,
        ];
        yield 'definition with plain public property options' => [
            [
                ['class', self::name('binding.options')],
                ['colour', self::name('x')],
            ],
            [Target::class, [['colour', 3, "'x'"]]],
        ];
        yield 'definition options keyed by class' => [
            [['class', self::name(DefinitionOptionsTarget::class)]],
            [DefinitionOptionsTarget::class, []],
        ];
        yield 'definition option on a setter' => [
            [['class', self::name('binding.setterOption')]],
            null,
        ];
        yield 'definition option on a protected property' => [
            [['class', self::name('binding.protectedOption')]],
            null,
        ];
        yield 'definition option on a read-only property' => [
            [['class', self::name('binding.readonlyOption')]],
            null,
        ];
        yield 'definition option on a static property' => [
            [['class', self::name('binding.staticOption')]],
            null,
        ];
        yield 'definition option unknown to the class' => [
            [['class', self::name('binding.unknownOption')]],
            null,
        ];
        yield 'definition with two constructor arguments' => [
            [['class', self::name('binding.params')]],
            null,
        ];
        yield '`__construct()` addressing only `$config`' => [
            [
                ['class', self::name(Target::class)],
                ['__construct()', self::list([[]])],
                ['name', self::name('x')],
            ],
            [Target::class, [['name', 4, "'x'"]]],
        ];
        yield '`__construct()` with a second argument' => [
            [
                ['class', self::name(Target::class)],
                ['__construct()', self::list([[], 'x'])],
            ],
            null,
        ];
        yield '`__construct()` not one constant array' => [
            [
                ['class', self::name(Target::class)],
                ['__construct()', new ArrayType(new IntegerType(), new MixedType())],
            ],
            null,
        ];
        yield '`__construct()` argument as the configuration, class keys kept' => [
            [
                ['class', self::name(Target::class)],
                ['__construct()', self::list([['class' => 'x', 'colour' => 'y']])],
            ],
            [Target::class, [['class', 9, "'x'"], ['colour', 9, "'y'"]]],
        ];
        yield 'definition params as the configuration' => [
            [['class', self::name('arguments.params')]],
            null,
        ];
        yield 'definition params beside call options' => [
            [
                ['class', self::name('arguments.params')],
                ['colour', self::name('x')],
            ],
            [Target::class, [['colour', 3, "'x'"]]],
        ];
        yield '`__construct()` named argument that is not an array' => [
            [
                ['class', self::name(Target::class)],
                ['__construct()', self::list(['config' => 'x'])],
            ],
            null,
        ];
        yield '`__construct()` argument with an integer key' => [
            [
                ['class', self::name(Target::class)],
                ['__construct()', self::list([['x']])],
            ],
            null,
        ];
    }

    /**
     * @phpstan-return iterable<
     *   string,
     *   array{list<array{string, Type}>, array{string, list<array{string, int, string}>}|null}
     * >
     */
    public static function traitActionProvider(): iterable
    {
        yield 'definition sets a trait-hooked property' => [
            [['class', self::name('traitproperty.paintTrigger')]],
            null,
        ];
        yield 'definition sets a plain property' => [
            [['class', self::name('traitproperty.trigger')]],
            [TriggerAction::class, []],
        ];
        yield 'definition sets an identical trait property' => [
            [['class', self::name('traitproperty.triggerTrait')]],
            [TriggerTraitAction::class, []],
        ];
    }

    /**
     * @phpstan-return iterable<
     *   string,
     *   array{list<array{string, Type}>, array{string, list<array{string, int, string}>}|null}
     * >
     */
    public static function traitDefinitionProvider(): iterable
    {
        yield 'definition sets a trait-hooked property' => [
            [
                ['class', self::name('traitproperty.paintTrigger')],
                ['colour', self::name('x')],
            ],
            null,
        ];
        yield 'definition sets a plain property' => [
            [
                ['class', self::name('traitproperty.trigger')],
                ['colour', self::name('x')],
            ],
            [TriggerComponent::class, [['colour', 3, "'x'"]]],
        ];
        yield 'definition sets an identical trait property' => [
            [
                ['class', self::name('traitproperty.triggerTrait')],
                ['colour', self::name('x')],
            ],
            [TriggerTraitComponent::class, [['colour', 3, "'x'"]]],
        ];
        yield 'unbound class with a trait-hooked property' => [
            [
                ['class', self::name(PaintTriggerComponent::class)],
                ['colour', self::name('x')],
            ],
            [PaintTriggerComponent::class, [['colour', 3, "'x'"]]],
        ];
    }

    /**
     * @param array<int|string, mixed> $values
     */
    private static function list(array $values): Type
    {
        $builder = ConstantArrayTypeBuilder::createEmpty();

        foreach ($values as $key => $value) {
            $builder->setOffsetValueType(
                is_int($key) ? new ConstantIntegerType($key) : new ConstantStringType($key),
                match (true) {
                    is_array($value) => self::list($value),
                    is_string($value) => new ConstantStringType($value),
                    default => new MixedType(),
                },
            );
        }

        return $builder->getArray();
    }

    private static function name(string $value): Type
    {
        return new ConstantStringType($value);
    }
}

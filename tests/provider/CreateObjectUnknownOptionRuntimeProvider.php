<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

use Closure;
use Yii;
use yii\base\{BaseObject, InvalidCallException, UnknownMethodException, UnknownPropertyException};
use yii\BaseYii;
use yii2\extensions\phpstan\tests\data\rule\CreateObjectStaticTarget;
use yii2\extensions\phpstan\tests\support\createobject\{
    ChildTarget,
    ComponentTarget,
    CustomConstructorTarget,
    CustomSetterTarget,
    Factory,
    FactoryTarget,
    InstanceTarget,
    Options,
    SingletonTarget,
    Target,
    UnresolvedTarget,
};

use function random_int;

/**
 * Data provider for {@see \yii2\extensions\phpstan\tests\rules\CreateObjectUnknownOptionRuntimeTest} test cases.
 */
final class CreateObjectUnknownOptionRuntimeProvider
{
    /**
     * Unknown option name read through `self::` by the constant-known cases.
     */
    private const string UNKNOWN = 'colour';

    /**
     * @phpstan-return iterable<string, array{Closure(): object, string, string}>
     */
    public static function reportedOptionProvider(): iterable
    {
        yield 'unknown option' => [
            static fn(): object => Yii::createObject(
                [
                    'class' => Target::class,
                    'colour' => 'red',
                ],
            ),
            Target::class,
            'colour',
        ];
        yield 'case-sensitive property name' => [
            static fn(): object => Yii::createObject(
                [
                    'class' => Target::class,
                    'Name' => 'value',
                ],
            ),
            Target::class,
            'Name',
        ];
        yield 'protected property' => [
            static fn(): object => Yii::createObject(
                [
                    'class' => Target::class,
                    'internal' => 'value',
                ],
            ),
            Target::class,
            'internal',
        ];
        yield 'private property' => [
            static fn(): object => Yii::createObject(
                [
                    'class' => Target::class,
                    'secret' => 'value',
                ]
            ),
            Target::class,
            'secret',
        ];
        yield 'annotated property' => [
            static fn(): object => Yii::createObject(
                [
                    'class' => Target::class,
                    'documented' => 'value',
                ],
            ),
            Target::class,
            'documented',
        ];
        yield 'annotated write-only property' => [
            static fn(): object => Yii::createObject(
                [
                    'class' => Target::class,
                    'annotated' => 'value',
                ],
            ),
            Target::class,
            'annotated',
        ];
        yield 'parent private property' => [
            static fn(): object => Yii::createObject(
                [
                    'class' => ChildTarget::class,
                    'secret' => 'value',
                ],
            ),
            ChildTarget::class,
            'secret',
        ];
        yield 'inherited protected property' => [
            static fn(): object => Yii::createObject(
                [
                    'class' => ChildTarget::class,
                    'internal' => 'value',
                ],
            ),
            ChildTarget::class,
            'internal',
        ];
        yield '__class over class' => [
            static fn(): object => Yii::createObject(
                [
                    'class' => ComponentTarget::class,
                    '__class' => Target::class,
                    'colour' => 'red',
                ],
            ),
            Target::class,
            'colour',
        ];
        yield 'event key on BaseObject' => [
            static fn(): object => Yii::createObject(
                [
                    'class' => BaseObject::class,
                    'on change' => 'event',
                ],
            ),
            BaseObject::class,
            'on change',
        ];
        yield 'leading backslash' => [
            static fn(): object => Yii::createObject(
                [
                    'class' => '\\' . Target::class,
                    'colour' => 1,
                ],
            ),
            Target::class,
            'colour',
        ];
        yield 'leading backslash misses the binding' => [
            static fn(): object => Yii::createObject(
                [
                    'class' => '\\' . FactoryTarget::class,
                    'colour' => 1,
                ],
            ),
            FactoryTarget::class,
            'colour',
        ];
        yield 'BaseYii' => [
            static fn(): object => BaseYii::createObject(
                [
                    'class' => Target::class,
                    'colour' => 'base',
                ],
            ),
            Target::class,
            'colour',
        ];
        yield 'BaseYii subclass' => [
            static fn(): object => Factory::createObject(
                [
                    'class' => Target::class,
                    'colour' => 'subclass',
                ],
            ),
            Target::class,
            'colour',
        ];
        yield 'named arguments' => [
            static fn(): object => Yii::createObject(
                params: [],
                type: [
                    'class' => Target::class,
                    'colour' => 'named',
                ],
            ),
            Target::class,
            'colour',
        ];
        yield 'other class constant key' => [
            static fn(): object => Yii::createObject(
                [
                    'class' => Target::class,
                    Options::UNKNOWN => 'value',
                ],
            ),
            Target::class,
            'colour',
        ];
        yield 'self constant key' => [
            static fn(): object => Yii::createObject(
                [
                    'class' => Target::class,
                    self::UNKNOWN => 'value',
                ],
            ),
            Target::class,
            'colour',
        ];
        yield 'constant class key' => [
            static fn(): object => Yii::createObject(
                [
                    Options::CLASS_KEY => Target::class,
                    'colour' => 'value',
                ],
            ),
            Target::class,
            'colour',
        ];
        yield 'constant class value' => [
            static fn(): object => Yii::createObject(
                [
                    'class' => Options::TARGET,
                    'colour' => 'value',
                ],
            ),
            Target::class,
            'colour',
        ];
        yield 'local class value' => [
            static function (): object {
                $className = Target::class;

                return Yii::createObject(
                    [
                        'class' => $className,
                        'colour' => 'value',
                    ],
                );
            },
            Target::class,
            'colour',
        ];
        yield 'constant __class value' => [
            static fn(): object => Yii::createObject(
                [
                    'class' => ComponentTarget::class,
                    '__class' => Options::TARGET,
                    'colour' => 'value',
                ],
            ),
            Target::class,
            'colour',
        ];
        yield 'leading backslash constant misses the binding' => [
            static fn(): object => Yii::createObject(
                [
                    'class' => Options::FACTORY_TARGET_WITH_BACKSLASH,
                    'colour' => 'value',
                ],
            ),
            FactoryTarget::class,
            'colour',
        ];
        yield 'local configuration' => [
            static function (): object {
                $config = ['class' => Target::class, 'colour' => 'local'];

                return Yii::createObject($config);
            },
            Target::class,
            'colour',
        ];
        yield 'other class constant configuration' => [
            static fn(): object => Yii::createObject(Options::CONFIG),
            Target::class,
            'colour',
        ];
        yield 'assigned offset' => [
            static function (): object {
                $config = ['class' => Target::class, 'name' => 'public'];
                $config['colour'] = 'assigned offset';

                return Yii::createObject($config);
            },
            Target::class,
            'colour',
        ];
        yield 'unpacked constant configuration' => [
            static fn(): object => Yii::createObject(
                [
                    ...Options::BASE,
                    'name' => 'unpacked',
                ],
            ),
            Target::class,
            'colour',
        ];
        yield 'later class replaces unpacked class' => [
            static fn(): object => Yii::createObject(
                [
                    ...Options::COMPONENT_CONFIG,
                    'class' => Target::class,
                ],
            ),
            Target::class,
            'colour',
        ];
        yield 'later unpacking replaces class' => [
            static fn(): object => Yii::createObject(
                [
                    'class' => ComponentTarget::class,
                    'colour' => 'replaced class',
                    ...Options::TARGET_CLASS,
                ],
            ),
            Target::class,
            'colour',
        ];
        yield 'constant empty params' => [
            static fn(): object => Yii::createObject(
                Options::CONFIG,
                Options::NO_PARAMS,
            ),
            Target::class,
            'colour',
        ];
        yield 'local empty params' => [
            static function (): object {
                $params = [];

                return Yii::createObject(
                    Options::CONFIG,
                    $params,
                );
            },
            Target::class,
            'colour',
        ];
        yield 'late static class of a final class' => [
            static fn(): object => CreateObjectStaticTarget::createWithConstantKey(),
            CreateObjectStaticTarget::class,
            'colour',
        ];
        yield 'numeric string key in a literal configuration' => [
            static fn(): object => Yii::createObject(
                [
                    'class' => Target::class,
                    '1' => 'numeric string key',
                ],
            ),
            Target::class,
            '1',
        ];
        yield 'union of one shape' => [
            static function (): object {
                $config = random_int(0, 1) === 1
                    ? Options::CONFIG
                    : [
                        'class' => Target::class,
                        'colour' => 'same keys, other value',
                    ];

                return Yii::createObject($config);
            },
            Target::class,
            'colour',
        ];
    }

    /**
     * @phpstan-return iterable<string, array{Closure(): object, class-string}>
     */
    public static function standAsideProvider(): iterable
    {
        yield 'factory ignores the configuration' => [
            static fn(): object => Yii::createObject(
                [
                    'class' => FactoryTarget::class,
                    'colour' => 'factory',
                ],
            ),
            FactoryTarget::class,
        ];
        yield 'object definition ignores the configuration' => [
            static fn(): object => Yii::createObject(
                [
                    'class' => InstanceTarget::class,
                    'colour' => 'object',
                ],
            ),
            InstanceTarget::class,
        ];
        yield 'untyped factory ignores the configuration' => [
            static fn(): object => Yii::createObject(
                [
                    'class' => UnresolvedTarget::class,
                    'colour' => 'unresolved',
                ],
            ),
            UnresolvedTarget::class,
        ];
        yield 'existing singleton ignores the configuration' => [
            static function (): object {
                Yii::createObject(SingletonTarget::class);

                return Yii::createObject(
                    [
                        'class' => SingletonTarget::class,
                        'colour' => 'singleton',
                    ],
                );
            },
            SingletonTarget::class,
        ];
        yield 'first singleton call assigns the configuration' => [
            static fn(): object => Yii::createObject(
                [
                    'class' => SingletonTarget::class,
                    'colour' => 'singleton',
                ],
            ),
            UnknownPropertyException::class,
        ];
        yield 'custom constructor consumes the option' => [
            static fn(): object => Yii::createObject(
                [
                    'class' => CustomConstructorTarget::class,
                    'alias' => 'consumed',
                ],
            ),
            CustomConstructorTarget::class,
        ];
        yield 'custom __set() accepts the option' => [
            static fn(): object => Yii::createObject(
                [
                    'class' => CustomSetterTarget::class,
                    'colour' => 'magic',
                ],
            ),
            CustomSetterTarget::class,
        ];
        yield 'component accepts an event handler' => [
            static fn(): object => Yii::createObject(
                [
                    'class' => ComponentTarget::class,
                    'on change' => static function (): void {},
                ],
            ),
            ComponentTarget::class,
        ];
        yield '__construct() moves the configuration to an ignored argument' => [
            static fn(): object => Yii::createObject(
                [
                    'class' => Target::class,
                    '__construct()' => [[], 'extra'],
                    'colour' => 'constructor',
                ],
            ),
            Target::class,
        ];
        yield 'params move the configuration to an ignored argument' => [
            static fn(): object => Yii::createObject(
                [
                    'class' => Target::class,
                    'colour' => 'params',
                ],
                [
                    [],
                    'second',
                ],
            ),
            Target::class,
        ];
        yield 'getter without setter' => [
            static fn(): object => Yii::createObject(
                [
                    'class' => Target::class,
                    'count' => 1,
                ],
            ),
            InvalidCallException::class,
        ];
        yield 'private setter' => [
            static fn(): object => Yii::createObject(
                [
                    'class' => Target::class,
                    'hidden' => 'value',
                ],
            ),
            UnknownMethodException::class,
        ];
        yield 'static property' => [
            static fn(): object => Yii::createObject(
                [
                    'class' => Target::class,
                    'shared' => 'value',
                ],
            ),
            UnknownPropertyException::class,
        ];
        yield 'protected static property' => [
            static fn(): object => Yii::createObject(
                [
                    'class' => Target::class,
                    'registry' => 'value',
                ],
            ),
            UnknownPropertyException::class,
        ];
        yield 'late static class of a final class in a literal configuration' => [
            static fn(): object => CreateObjectStaticTarget::createWithLiteralKeys(),
            UnknownPropertyException::class,
        ];
        yield 'reference capture replaces the constant configuration' => [
            static function (): object {
                $config = Options::CONFIG;

                $replace = static function () use (&$config): void {
                    $config = Options::VALID_CONFIG;
                };

                $replace();

                return Yii::createObject($config);
            },
            Target::class,
        ];
        yield 'constant params move the configuration to an ignored argument' => [
            static fn(): object => Yii::createObject(
                Options::CONFIG,
                Options::PARAMS,
            ),
            Target::class,
        ];
        yield 'constant __construct() moves the configuration to an ignored argument' => [
            static fn(): object => Yii::createObject(Options::CONSTRUCTOR_CONFIG),
            Target::class,
        ];
    }

    /**
     * @phpstan-return iterable<string, array{Closure(): object, class-string}>
     */
    public static function validOptionProvider(): iterable
    {
        yield 'public property, setter and case-insensitive setter' => [
            static fn(): object => Yii::createObject(
                [
                    'class' => Target::class,
                    'name' => 'public',
                    'label' => 'setter',
                    'LABEL' => 'setter',
                ],
            ),
            Target::class,
        ];
        yield 'protected setter and trait setter' => [
            static fn(): object => Yii::createObject(
                [
                    'class' => Target::class,
                    'guarded' => 'protected setter',
                    'tagged' => 'trait setter',
                ],
            ),
            Target::class,
        ];
        yield 'trait property' => [
            static fn(): object => Yii::createObject(
                [
                    'class' => Target::class,
                    'tag' => 'trait property',
                ],
            ),
            Target::class,
        ];
        yield 'inherited property and setter' => [
            static fn(): object => Yii::createObject(
                [
                    'class' => ChildTarget::class,
                    'name' => 'inherited',
                    'label' => 'inherited setter',
                ],
            ),
            ChildTarget::class,
        ];
        yield 'own property and setter' => [
            static fn(): object => Yii::createObject(
                [
                    'class' => ChildTarget::class,
                    'colourCode' => 'own property',
                    'shade' => 'own setter',
                ],
            ),
            ChildTarget::class,
        ];
        yield '__class with ignored class' => [
            static fn(): object => Yii::createObject(
                [
                    '__class' => Target::class,
                    'class' => ComponentTarget::class,
                    'name' => '__class wins',
                ],
            ),
            Target::class,
        ];
        yield 'constant keys and class value' => [
            static fn(): object => Yii::createObject(
                [
                    Options::CLASS_KEY => Options::TARGET,
                    Options::VALID => 'public',
                    'label' => 'setter',
                ],
            ),
            Target::class,
        ];
        yield 'constant configuration' => [
            static fn(): object => Yii::createObject(
                Options::VALID_CONFIG,
                Options::NO_PARAMS,
            ),
            Target::class,
        ];
        yield 'unpacked class replaces class' => [
            static fn(): object => Yii::createObject(
                [
                    'class' => Target::class,
                    'colourCode' => 'child property',
                    ...Options::CHILD_CLASS,
                ],
            ),
            ChildTarget::class,
        ];
        yield 'later class replaces unpacked class' => [
            static fn(): object => Yii::createObject(
                [
                    ...Options::VALID_CONFIG,
                    'class' => ChildTarget::class,
                    'shade' => 'child setter',
                ],
            ),
            ChildTarget::class,
        ];
    }
}

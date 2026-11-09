<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

use Error;
use yii\base\{InvalidConfigException, UnknownMethodException, UnknownPropertyException};
use yii\di\NotInstantiableException;
use yii2\extensions\phpstan\tests\data\rule\behavior\{
    ReportedBehaviorsOwner,
    StandAsideBehaviorsOwner,
    ValidBehaviorsOwner,
};

use function strrpos;
use function substr;

/**
 * Data provider for {@see \yii2\extensions\phpstan\tests\rules\BehaviorDefinitionRuntimeTest} test cases.
 */
final class BehaviorDefinitionRuntimeProvider
{
    /**
     * @phpstan-return iterable<string, array{class-string, int|string, class-string|null}>
     */
    public static function entryProvider(): iterable
    {
        foreach (self::outcomes() as $owner => $entries) {
            $short = substr($owner, (int) strrpos($owner, '\\') + 1);

            foreach ($entries as $name => $outcome) {
                yield "{$short} {$name}" => [$owner, $name, $outcome];
            }
        }
    }
    /**
     * Recorded outcomes by fixture owner and behavior name.
     *
     * @phpstan-return array<class-string, array<int|string, class-string|null>>
     */
    public static function outcomes(): array
    {
        return [
            ReportedBehaviorsOwner::class => [
                'aliasChain' => Error::class,
                'configured' => UnknownMethodException::class,
                'dunderWins' => Error::class,
                'duplicate' => UnknownMethodException::class,
                'emptyArray' => InvalidConfigException::class,
                'leadingSlash' => UnknownMethodException::class,
                'mappedAway' => UnknownMethodException::class,
                'notAttachable' => UnknownMethodException::class,
                'notCallable' => InvalidConfigException::class,
                'nullClass' => InvalidConfigException::class,
                'plainClass' => Error::class,
                'privateStaticAttach' => Error::class,
                'protectedAttach' => UnknownMethodException::class,
                'protectedAttachClass' => Error::class,
                'protectedStaticAttach' => UnknownMethodException::class,
                0 => Error::class,
            ],
            ValidBehaviorsOwner::class => [
                'aliasChain' => null,
                'attachable' => null,
                'base' => null,
                'callable' => null,
                'classLoses' => null,
                'closure' => null,
                'configured' => null,
                'configuredId' => null,
                'containerId' => null,
                'contract' => null,
                'dunder' => null,
                'duplicate' => null,
                'instance' => null,
                'instanceAlias' => null,
                'invokable' => null,
                'magicCall' => null,
                'plainStaticAttach' => null,
                'publicAttach' => null,
                'redirected' => null,
                'staticAttach' => null,
                'staticAttachOnly' => null,
            ],
            StandAsideBehaviorsOwner::class => [
                'abstract' => NotInstantiableException::class,
                'abstractPlain' => NotInstantiableException::class,
                'closureClassName' => Error::class,
                'component' => UnknownMethodException::class,
                'factory' => UnknownMethodException::class,
                'nullDunder' => UnknownPropertyException::class,
                'singleton' => UnknownMethodException::class,
                'unboundInterface' => NotInstantiableException::class,
                'union' => null,
                'unknownId' => NotInstantiableException::class,
            ],
        ];
    }
}

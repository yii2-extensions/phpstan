<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\rules;

use PhpParser\Node\Expr;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\Type;
use yii\BaseYii;

/**
 * Represents a {@see BaseYii::createObject()} configuration whose class and assignment path are proven.
 *
 * {@see CreateObjectConfigurationResolver} for the conditions under which a configuration is resolved.
 */
final readonly class CreateObjectConfiguration
{
    /**
     * Stores the called class, resolved class and effective configuration options.
     *
     * @param string $calledClass Class through which `createObject()` is called, as PHPStan displays it.
     * @param ClassReflection $classReflection Class Yii creates and configures.
     * @param list<array{string, int, Expr|Type}> $options Name, reported line and effective value of each distinct
     * option that Yii assigns, in first-occurrence order: the value expression of an array literal whose keys are all
     * literal strings, otherwise the value type of the constant native array. `class` and `__class` are present only
     * when they come from a constructor argument used as the configuration.
     */
    public function __construct(
        public string $calledClass,
        public ClassReflection $classReflection,
        public array $options,
    ) {}
}

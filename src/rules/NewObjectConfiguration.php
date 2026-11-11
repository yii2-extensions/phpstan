<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\rules;

use PhpParser\Node\Expr;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\Type;

/**
 * Represents the configuration of a direct `new` construction whose class and assignment path are proven.
 *
 * {@see NewObjectConfigurationResolver} for the conditions under which a configuration is resolved.
 */
final readonly class NewObjectConfiguration
{
    /**
     * Stores the constructed class and effective configuration options.
     *
     * @param ClassReflection $classReflection Class that `new` constructs and configures.
     * @param list<array{string, int, Expr|Type}> $options Name, reported line and effective value of each distinct
     * option, in first-occurrence order, as {@see ObjectConfigurationReader::options()} reads them. Every key is
     * assigned, `class`, `__class` and `__construct()` included.
     */
    public function __construct(
        public ClassReflection $classReflection,
        public array $options,
    ) {}
}

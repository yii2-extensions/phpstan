<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\type;

use PHPStan\Type\{ObjectType, VerbosityLevel};
use yii\db\ActiveQuery;

use function sprintf;

/**
 * Represents a PHPStan type for Yii {@see ActiveQuery} objects with model class context.
 *
 * Provides a specialized object type for Yii's {@see ActiveQuery} instances, capturing the associated model class and
 * array mode flag for accurate static analysis, type inference, and autocompletion in PHPStan.
 *
 * This type enables PHPStan to distinguish between different {@see ActiveQuery} usages by embedding the model class and
 * whether the query returns arrays or model instances supporting precise type checks and IDE support for query builder
 * patterns.
 *
 * Key features.
 * - Embeds the model class name for context-aware type inference.
 * - Immutable design for safe usage in type analysis.
 * - Integrates with PHPStan's object type system for compatibility and strict analysis.
 * - Supports descriptive output for debugging and reporting.
 * - Tracks whether the query returns arrays or model objects `asArray`.
 *
 * @see ObjectType for PHPStan's base object type.
 *
 * @copyright Copyright (C) 2023 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class ActiveQueryObjectType extends ObjectType
{
    /**
     * Creates a new instance of the {@see ActiveQueryObjectType} class.
     *
     * @param string $modelClass Fully qualified class name of the model associated with this {@see ActiveQuery}.
     * @param bool $asArray Indicates whether the query returns results as arrays `true` or model instances `false`.
     */
    public function __construct(private readonly string $modelClass, private readonly bool $asArray)
    {
        parent::__construct(ActiveQuery::class);
    }

    /**
     * Retrieves a string representation of the {@see ActiveQuery} object type with model class context.
     *
     * Returns a descriptive string for this {@see ActiveQuery} type, including the associated model class name, to
     * support debugging, reporting, and static analysis output in PHPStan.
     *
     * This method extends the base object type description by appending the model class in angle brackets, enabling
     * clear differentiation between {@see ActiveQuery} types for different models during type analysis and IDE inspection.
     *
     * @param VerbosityLevel $level Verbosity level for the type description output.
     *
     * @return string String representation of the {@see ActiveQuery} type with model class context.
     */
    public function describe(VerbosityLevel $level): string
    {
        return sprintf('%s<%s>', parent::describe($level), $this->modelClass);
    }

    /**
     * Retrieves the fully qualified model class name associated with this {@see ActiveQuery} type.
     *
     * Returns the model class name that this {@see ActiveQueryObjectType} instance represents enabling static analysis
     * tools and IDEs to infer the correct model context for query builder operations and type checks.
     *
     * This method is essential for distinguishing between different {@see ActiveQuery} usages and for providing
     * accurate autocompletion and type inference in PHPStan and supporting IDEs.
     *
     * @return string Fully qualified class name of the associated model.
     */
    public function getModelClass(): string
    {
        return $this->modelClass;
    }

    /**
     * Determines whether this {@see ActiveQuery} type returns results as arrays or model instances.
     *
     * Returns the array mode flag for this {@see ActiveQueryObjectType} instance, indicating if query results are
     * returned as associative arrays `true` or as model objects `false`.
     *
     * This method is essential for accurate type inference and static analysis, allowing PHPStan and IDEs to
     * distinguish between array and object result modes in query builder operations.
     *
     * @return bool `true` if the query returns results as arrays; `false` if as model instances.
     */
    public function isAsArray(): bool
    {
        return $this->asArray;
    }
}

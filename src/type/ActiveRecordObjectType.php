<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\type;

use ArrayAccess;
use PHPStan\{ShouldNotHappenException, TrinaryLogic};
use PHPStan\Type\{ErrorType, ObjectType, Type};

use function count;

/**
 * Represents a PHPStan object type for Yii ActiveRecord models with array access support.
 *
 * Provides a custom object type implementation for Yii ActiveRecord classes, enabling accurate type inference and
 * static analysis for ActiveRecord instances, including support for array access to model properties.
 *
 * This class extends PHPStan's {@see ObjectType} to handle property access via array syntax, reflecting Yii's
 * ActiveRecord behavior where model attributes can be accessed as array offsets.
 *
 * It ensures that property existence and type checks are performed correctly for both object and array access patterns.
 *
 * The implementation overrides offset related type methods to integrate with PHPStan's type system, returning precise
 * type information and error handling for invalid property access.
 *
 * Key features.
 * - Accurate type inference for ActiveRecord property access via array offsets.
 * - Handles property existence checks for array access.
 * - Integrates with PHPStan's strict analysis and autocompletion.
 * - Returns error types for invalid property assignments.
 * - Supports both object and array access semantics for ActiveRecord models.
 *
 * @see ObjectType for PHPStan's base object type.
 *
 * @copyright Copyright (C) 2023 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class ActiveRecordObjectType extends ObjectType
{
    /**
     * Determines whether the ActiveRecord object type has a value for the specified array offset.
     *
     * Checks if the given offset (typically a property name as a string) exists as a property on the ActiveRecord
     * model, supporting array access semantics.
     *
     * If the offset is not a constant string, returns {@see TrinaryLogic::No}
     *
     * If the ActiveRecord model implements {@see ArrayAccess}, the method checks for property existence and returns
     * the result as a {@see TrinaryLogic} value.
     *
     * Otherwise, it delegates to the parent {@see ObjectType} implementation.
     *
     * This method enables PHPStan to accurately infer type information and property existence for array access on
     * ActiveRecord models supporting static analysis and IDE autocompletion for both object and array access patterns.
     *
     * @param Type $offsetType Type representing the array offset (property name) to check.
     *
     * @throws ShouldNotHappenException if an unexpected error occurs during property resolution.
     *
     * @return TrinaryLogic `yes` if the property exists, `no` if not, or the result from the parent implementation.
     */
    public function hasOffsetValueType(Type $offsetType): TrinaryLogic
    {
        $constantStrings = $offsetType->getConstantStrings();

        if (count($constantStrings) === 0) {
            return TrinaryLogic::createNo();
        }

        if ($this->isInstanceOf(ArrayAccess::class)->yes()) {
            return TrinaryLogic::createFromBoolean($this->hasProperty($constantStrings[0]->getValue())->yes());
        }

        return parent::hasOffsetValueType($offsetType);
    }

    /**
     * Sets the value type for a given array offset on the ActiveRecord object type.
     *
     * Validates whether the specified offset (property name as a string) exists as a property on the ActiveRecord
     * model.
     *
     * If the offset is a constant string and the property doesn't exist, returns an {@see ErrorType} to signal an
     * invalid property assignment for static analysis.
     *
     * If the property exists or the offset is not a constant string, returns the current object type instance,
     * preserving type safety and supporting array access semantics for ActiveRecord models.
     *
     * This method enables PHPStan to detect invalid property assignments via array access and to provide accurate type
     * inference and error reporting for ActiveRecord models.
     *
     * @param Type|null $offsetType Type representing the array offset (property name) to set, or `null`.
     * @param Type $valueType Type of the value being assigned to the offset.
     * @param bool $unionValues Whether to union the value type with the existing type default `true`.
     *
     * @return Type {@see ErrorType} if the property doesn't exist, or the current object type instance otherwise.
     */
    public function setOffsetValueType(Type|null $offsetType, Type $valueType, bool $unionValues = true): Type
    {
        $constantStrings = $offsetType?->getConstantStrings();

        if (
            $constantStrings !== null &&
            count($constantStrings) > 0 &&
            $this->hasProperty($constantStrings[0]->getValue())->no()
        ) {
            return new ErrorType();
        }

        return $this;
    }
}

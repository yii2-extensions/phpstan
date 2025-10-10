<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\type;

use yii\db\ActiveRecord;
use yii2\extensions\phpstan\tests\support\stub\{
    ModelWithConflictingProperty,
    ModelWithMultipleBehaviors,
    NestedSetsModel,
    Post,
};

use function PHPStan\Testing\assertType;

/**
 * Test suite for dynamic return types of {@see ActiveRecord::getAttribute} method in Yii Active Record scenarios.
 *
 * Validates type inference and return types for {@see ActiveRecord::getAttribute} method when used with custom
 * {@see ActiveRecord} implementations that include behaviors with property definitions and PHPDoc annotations, ensuring
 * proper type resolution for model properties and behavior-defined attributes.
 *
 * These tests ensure that PHPStan correctly infers the result types for {@see ActiveRecord::getAttribute} calls based
 * on model property annotations, behavior property definitions, and precedence rules when both model and behavior
 * define the same property name.
 *
 * The test suite validates the type system's ability to distinguish between model-defined properties and behavior-added
 * properties, ensuring that model property types take precedence over behavior property types when conflicts occur.
 *
 * Test coverage.
 * - Behavior property type inference for multiple behaviors with different property types.
 * - Model property type inference from PHPDoc annotations.
 * - Multiple behavior property handling with different return types.
 * - Precedence rules for conflicting property names between models and behaviors.
 * - Type inference for unknown attributes (fallback to mixed type).
 * - Type safety validation for {@see ActiveRecord::getAttribute} with known and unknown properties.
 *
 * @copyright Copyright (C) 2023 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class ActiveRecordGetAttributeDynamicMethodReturnType
{
    public function testReturnIntAndStringWhenGetAttributeWithMultipleBehaviors(): void
    {
        $model = new ModelWithMultipleBehaviors();

        assertType('int', $model->getAttribute('lft'));
        assertType('string', $model->getAttribute('slug'));
    }

    public function testReturnIntWhenGetAttributeWithBehaviorPhpDoc(): void
    {
        $model = new NestedSetsModel();

        assertType('int', $model->getAttribute('lft'));
        assertType('int', $model->getAttribute('rgt'));
        assertType('int', $model->getAttribute('depth'));
    }

    public function testReturnMixedWhenGetAttributeWithBehaviorPhpDoc(): void
    {
        $model = new NestedSetsModel();

        assertType('mixed', $model->getAttribute('unknown_attribute'));
    }

    public function testReturnMixedWhenGetAttributeWithModelPhpDoc(): void
    {
        $post = new Post();

        assertType('mixed', $post->getAttribute('unknown_attribute'));
    }

    public function testReturnStringWhenGetAttributeWithModelPhpDoc(): void
    {
        $post = new Post();

        assertType('string', $post->getAttribute('title'));
        assertType('string', $post->getAttribute('content'));
    }

    public function testReturnStringWhenGetAttributeWithModelPhpDocTakesPrecedenceOverBehavior(): void
    {
        $model = new ModelWithConflictingProperty();

        assertType('string', $model->getAttribute('lft'));
    }
}

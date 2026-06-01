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
 * Type assertion fixture for {@see ActiveRecord::getAttribute()} return types in PHPStan analysis.
 *
 * Verifies type inference from model PHPDoc and behavior property definitions, including precedence of model properties
 * over behavior properties on conflict and the `mixed` fallback for unknown attributes.
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

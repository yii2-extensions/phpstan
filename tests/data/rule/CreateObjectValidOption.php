<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule;

use Yii;
use yii2\extensions\phpstan\tests\support\createobject\{
    ChildTarget,
    ComponentTarget,
    Target,
};

/**
 * Literal `Yii::createObject()` options that the created class assigns or that the rule doesn't claim.
 */
final class CreateObjectValidOption
{
    public function acceptValidOptions(): void
    {
        Yii::createObject(['class' => Target::class, 'name' => 'public', 'label' => 'setter', 'LABEL' => 'setter']);
        Yii::createObject(['class' => Target::class, 'guarded' => 'protected setter', 'tagged' => 'trait setter']);
        Yii::createObject(['class' => Target::class, 'tag' => 'trait property']);
        Yii::createObject(['class' => ChildTarget::class, 'name' => 'inherited', 'label' => 'inherited setter']);
        Yii::createObject(['class' => ChildTarget::class, 'colourCode' => 'own property', 'shade' => 'own setter']);
        Yii::createObject(['class' => Target::class, 'count' => 'read-only', 'hidden' => 'private setter']);
        Yii::createObject(['class' => Target::class, 'shared' => 'static property', '' => 'empty']);
        Yii::createObject(['class' => Target::class, 'registry' => 'protected static property']);
        Yii::createObject(['__class' => Target::class, 'class' => ComponentTarget::class, 'name' => '__class wins']);
    }
}

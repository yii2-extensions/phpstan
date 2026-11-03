<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule;

use Yii;
use yii2\extensions\phpstan\tests\support\createobject\{TypedOptions, TypedTarget};

/**
 * Constant-known `Yii::createObject()` configurations whose option value the created class never accepts.
 */
final class CreateObjectConstantInvalidOptionType
{
    public function assignedOffset(): object
    {
        $config = ['class' => TypedTarget::class];
        $config['count'] = [];

        return Yii::createObject($config);
    }

    public function constantConfiguration(): object
    {
        return Yii::createObject(TypedOptions::CONFIG);
    }

    public function localValue(): object
    {
        $value = 'abc';

        return Yii::createObject(['class' => TypedTarget::class, 'count' => $value]);
    }

    public function unpackedConstantThenLiteral(): object
    {
        return Yii::createObject([...TypedOptions::BASE, 'count' => 'abc']);
    }

    public function variableConfiguration(): object
    {
        $config = ['class' => TypedTarget::class, 'tags' => 'abc'];

        return Yii::createObject($config);
    }
}

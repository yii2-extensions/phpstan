<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule;

use Yii;
use yii2\extensions\phpstan\tests\support\createobject\{ChildTarget, ComponentTarget, Options, Target};

/**
 * Constant-known `Yii::createObject()` options that the created class assigns.
 */
final class CreateObjectConstantValidOption
{
    private const string KEY = 'label';

    public function acceptValidOptions(): void
    {
        $className = Target::class;

        $config = [
            'class' => Target::class,
            'name' => 'local',
        ];
        $params = [];

        Yii::createObject(['class' => Target::class, Options::VALID => 'other class constant', self::KEY => 'self']);
        Yii::createObject([Options::CLASS_KEY => Target::class, 'name' => 'constant class key']);
        Yii::createObject(['class' => Options::TARGET, 'name' => 'constant class value']);
        Yii::createObject(['class' => $className, 'name' => 'local class value']);
        Yii::createObject(['class' => ComponentTarget::class, '__class' => Options::TARGET, 'name' => '__class wins']);
        Yii::createObject($config);
        Yii::createObject(Options::VALID_CONFIG);
        Yii::createObject([...Options::VALID_CONFIG, 'label' => 'unpacked']);
        Yii::createObject(['class' => Target::class, 'colourCode' => 'child property', ...Options::CHILD_CLASS]);
        Yii::createObject([...Options::VALID_CONFIG, 'class' => ChildTarget::class, 'shade' => 'child setter']);
        Yii::createObject(Options::VALID_CONFIG, Options::NO_PARAMS);
        Yii::createObject(Options::VALID_CONFIG, $params);
    }
}

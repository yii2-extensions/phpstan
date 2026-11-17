<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\validator\unknown;

use yii\base\Model;
use yii\validators\StringValidator;

/**
 * Model whose rules use method names, unknown names, a class name under an overridden `hasMethod()` or invalid shapes.
 */
final class StandAsideModel extends Model
{
    public function custom(): void {}

    public function hasMethod($name, $checkBehaviors = true): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            ['title', 'custom', 'maxx' => 10],
            ['title', 'unknownAlias', 'maxx' => 10],
            ['title', ['class' => StringValidator::class], 'maxx' => 10],
            ['title', StringValidator::class, 'maxx' => 10],
            ['title'],
        ];
    }
}

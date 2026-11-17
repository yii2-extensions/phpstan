<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\validator\unknown;

use yii\base\Model;

/**
 * Model whose `hasMethod()` claims every name, which neither a built-in alias nor a `Closure` type consults.
 */
final class AliasPriorityModel extends Model
{
    public function hasMethod($name, $checkBehaviors = true): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            ['title', 'string', 'maxx' => 10],
            ['title', static function (): void {}, 'maxx' => 10],
        ];
    }
}

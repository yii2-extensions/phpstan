<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\stub;

use yii\db\ActiveRecord;

/**
 * User ActiveRecord model for testing property and rule definitions.
 *
 * Provides a minimal {@see ActiveRecord} subclass with explicit property declarations and validation rules for static
 * analysis and type inference tests.
 *
 * This class is used in PHPStan and static analysis scenarios to validate correct type inference for property access
 * and rule configuration in Yii Active Record models.
 *
 * Key features.
 * - Declares public properties {@see $id}, {@see $name}, and {@see $email} for type assertion tests.
 * - Defines {@see rules()} for property validation and type constraints.
 * - Implements {@see tableName()} for table mapping in test scenarios.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 *
 * @copyright Copyright (C) 2023 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
class User extends ActiveRecord
{
    public int $id = 1;
    public string $name = 'John Doe';
    public string $email = 'john@example.com';

    public static function tableName(): string
    {
        return 'users';
    }

    public function rules(): array
    {
        return [
            [['id'], 'integer'],
            [['name', 'email'], 'string'],
            [['email'], 'email'],
        ];
    }
}

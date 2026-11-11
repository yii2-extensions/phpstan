<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\validator;

use yii\base\Model;
use yii\validators\RequiredValidator;

/**
 * Model whose `rules()` returns only rules that `Model::createValidators()` accepts.
 */
final class ValidRulesModel extends Model
{
    private const array RULE = ['email', 'email'];

    public string $email = '';
    public string $name = '';

    /**
     * @return array<array-key, mixed>
     */
    public function rules(): array
    {
        return [
            // since PHP 8.3 the implicit key follows the negative key, and `-4` replaces it
            -5 => [
                'name',
                'required',
            ],
            ['implicit'],
            -4 => [
                'name',
                'trim',
            ],
            [
                'name',
                'required',
            ],
            [
                [
                    'name',
                    'email',
                ],
                'string',
                'max' => 255,
                'on' => 'default',
            ],
            [
                1 => 'trim',
                0 => 'name',
            ],
            [
                '1' => 'trim',
                '0' => 'email',
            ],
            // PHP keeps the last value of a duplicate key: the type replaces `null`
            [
                'name',
                null,
                1 => 'required',
            ],
            [[], 'required'],
            new RequiredValidator(['attributes' => ['name']]),
            self::RULE,
            'named' => ['name'],
            'named' => [
                'name',
                'safe',
            ],
            // PHP normalizes `'9'` to `9`, so the later complete rule replaces the incomplete one
            9 => ['name'],
            '9' => [
                'name',
                'safe',
            ],
        ];
    }
}

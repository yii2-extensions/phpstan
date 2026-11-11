<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\validator;

use yii\base\Component;

/**
 * Component that is not a model, whose `rules()` Yii never consumes.
 */
final class NotModelRules extends Component
{
    /**
     * @return array<array-key, mixed>
     */
    public function rules(): array
    {
        return [['name']];
    }
}

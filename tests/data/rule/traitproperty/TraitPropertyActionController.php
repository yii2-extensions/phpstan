<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\traitproperty;

use yii\base\Controller;

/**
 * Controller whose external actions target properties that a trait redeclares, and their controls.
 */
final class TraitPropertyActionController extends Controller
{
    public function actions(): array
    {
        return [
            'plainCount' => ['class' => CountAction::class, 'count' => [1, 2]],
            'paintTrigger' => ['class' => PaintTriggerAction::class, 'trigger' => 1, 'colour' => 'blue'],
            'publicSecret' => ['class' => PublicSecretAction::class, 'secret' => 'open'],
            'protectedSecret' => ['class' => SecretAction::class, 'secret' => 'open'],
            'plainTrigger' => ['class' => TriggerAction::class, 'trigger' => 1, 'colour' => 'blue'],
            'wideCount' => ['class' => WideCountAction::class, 'count' => [1, 2]],
        ];
    }
}

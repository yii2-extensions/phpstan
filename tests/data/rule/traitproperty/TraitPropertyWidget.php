<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\traitproperty;

/**
 * `widget()` calls whose options, or the options of their container definitions, target properties that a trait
 * redeclares, and their controls.
 */
final class TraitPropertyWidget
{
    public function count(): string
    {
        return CountWidget::widget(['count' => [1, 2]]);
    }

    public function definitionCount(): string
    {
        return DefinitionCountWidget::widget(['count' => [1, 2]]);
    }

    public function definitionPaintTrigger(): string
    {
        return DefinitionPaintTriggerWidget::widget(['colour' => 'blue']);
    }

    public function definitionTrigger(): string
    {
        return DefinitionTriggerWidget::widget(['colour' => 'blue']);
    }

    public function definitionTriggerTrait(): string
    {
        return DefinitionTriggerTraitWidget::widget(['colour' => 'blue']);
    }

    public function paintTrigger(): string
    {
        return PaintTriggerWidget::widget(['trigger' => 1, 'colour' => 'blue']);
    }

    public function publicSecret(): string
    {
        return PublicSecretWidget::widget(['secret' => 'open']);
    }

    public function secret(): string
    {
        return SecretWidget::widget(['secret' => 'open']);
    }

    public function trigger(): string
    {
        return TriggerWidget::widget(['trigger' => 1, 'colour' => 'blue']);
    }

    public function wideCount(): string
    {
        return WideCountWidget::widget(['count' => [1, 2]]);
    }
}

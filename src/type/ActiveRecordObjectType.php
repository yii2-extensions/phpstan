<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\type;

use ArrayAccess;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\ErrorType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

final class ActiveRecordObjectType extends ObjectType
{
    /**
     * @throws ShouldNotHappenException
     */
    public function hasOffsetValueType(Type $offsetType): TrinaryLogic
    {
        $constantStrings = $offsetType->getConstantStrings();
        if (count($constantStrings) === 0) {
            return TrinaryLogic::createNo();
        }

        if ($this->isInstanceOf(ArrayAccess::class)->yes()) {
            return TrinaryLogic::createFromBoolean($this->hasProperty($constantStrings[0]->getValue())->yes());
        }

        return parent::hasOffsetValueType($offsetType);
    }

    public function setOffsetValueType(?Type $offsetType, Type $valueType, bool $unionValues = true): Type
    {
        $constantStrings = $offsetType?->getConstantStrings();

        if (count($constantStrings) > 0 && $this->hasProperty($constantStrings[0]->getValue())->no()) {
            return new ErrorType();
        }

        return $this;
    }
}

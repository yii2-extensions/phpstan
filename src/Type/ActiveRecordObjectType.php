<?php

declare(strict_types=1);

namespace Yii2\Extensions\PHPStan\Type;

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
        if (count($offsetType->getConstantStrings()) === 0) {
            return TrinaryLogic::createNo();
        }

        if ($this->isInstanceOf(ArrayAccess::class)->yes()) {
            return TrinaryLogic::createFromBoolean($this->hasProperty($offsetType->getValue())->yes());
        }

        return parent::hasOffsetValueType($offsetType);
    }

    public function setOffsetValueType(?Type $offsetType, Type $valueType, bool $unionValues = true): Type
    {
        if (count($offsetType->getConstantStrings()) > 0 && $this->hasProperty($offsetType->getValue())->no()) {
            return new ErrorType();
        }

        return $this;
    }
}

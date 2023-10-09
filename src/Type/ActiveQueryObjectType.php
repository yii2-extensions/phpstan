<?php

declare(strict_types=1);

namespace Yii\PHPStan\Type;

use PHPStan\Type\ObjectType;
use PHPStan\Type\VerbosityLevel;
use yii\db\ActiveQuery;

final class ActiveQueryObjectType extends ObjectType
{
    public function __construct(private readonly string $modelClass, private readonly bool $asArray)
    {
        parent::__construct(ActiveQuery::class);
    }

    public function getModelClass(): string
    {
        return $this->modelClass;
    }

    public function isAsArray(): bool
    {
        return $this->asArray;
    }

    public function describe(VerbosityLevel $level): string
    {
        return sprintf('%s<%s>', parent::describe($level), $this->modelClass);
    }
}

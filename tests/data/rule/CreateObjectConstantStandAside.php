<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule;

use Yii;
use yii2\extensions\phpstan\tests\support\createobject\{ChildTarget, Options, Target};

/**
 * `Yii::createObject()` calls whose configuration PHPStan's native types don't prove to be one constant array.
 */
class CreateObjectConstantStandAside
{
    public const string KEY = 'colour';

    /**
     * @var array{class: Target::class, colour: string}
     */
    private array $documentedConfig = Options::CONFIG;

    /**
     * Configurations whose PHPDoc type is more precise than their native type.
     *
     * @param array{class: Target::class, colour: string} $config
     * @param Target::class $className
     * @param 'colour' $key
     * @param array{} $params
     */
    public function standAsideOnPhpDocPrecision(array $config, string $className, string $key, array $params): void
    {
        /** @var array{class: Target::class, colour: string} $annotated */
        $annotated = $this->untyped();

        Yii::createObject($config);
        Yii::createObject(['class' => $className, 'colour' => 'documented class']);
        Yii::createObject(['class' => Target::class, $key => 'documented key']);
        Yii::createObject(['class' => Target::class, 'colour' => 'documented empty params'], $params);
        Yii::createObject($annotated);
        Yii::createObject($this->documentedConfig());
        Yii::createObject($this->documentedConfig);
    }

    /**
     * Configurations with more than one possible value or an unknown part.
     *
     * @param array<array-key, mixed> $extra
     */
    public function standAsideOnUncertainConfiguration(bool $flag, array $extra): void
    {
        $union = $flag ? Options::CONFIG : Options::VALID_CONFIG;
        $optional = ['class' => Target::class];

        if ($flag) {
            $optional['colour'] = 'optional';
        }

        $widened = ['class' => Target::class];

        for ($index = 0; $index < 3; $index++) {
            $widened["colour{$index}"] = 'widened';
        }

        $captured = Options::CONFIG;

        $replace = static function () use (&$captured): void {
            $captured = Options::VALID_CONFIG;
        };

        $replace();

        $maybeEmpty = $flag ? [] : Options::PARAMS;

        Yii::createObject($union);
        Yii::createObject($optional);
        Yii::createObject($widened);
        Yii::createObject($captured);
        Yii::createObject(['class' => $flag ? Options::TARGET : ChildTarget::class, Options::UNKNOWN => 'union class']);
        Yii::createObject([...$extra, ...Options::CONFIG]);
        Yii::createObject([...Options::CONFIG, ...$extra]);
        Yii::createObject(['class' => Target::class, static::KEY => 'late static binding']);
        Yii::createObject([...Options::LIST, ...Options::CONFIG]);
        Yii::createObject(Options::CONFIG, Options::PARAMS);
        Yii::createObject(Options::CONFIG, $maybeEmpty);
        Yii::createObject(Options::CONFIG, $extra);
        Yii::createObject(Options::CONFIG, null);
    }

    public function standAsideOnUnprovenTarget(): void
    {
        Yii::createObject(Options::CONSTRUCTOR_CONFIG);
        Yii::createObject(Options::NULL_CLASS_CONFIG);
        Yii::createObject(Options::NO_CLASS_CONFIG);
        Yii::createObject(['class' => Options::FACTORY_TARGET, Options::UNKNOWN => 'factory']);
        Yii::createObject(['class' => Options::EMPTY_CLASS, 'colour' => 'empty class']);
        Yii::createObject([...Options::CONFIG, 'class' => Options::EMPTY_CLASS]);
        Yii::createObject(['class' => static::class, Options::UNKNOWN => 'late static class']);
    }

    /**
     * @return array{class: Target::class, colour: string}
     */
    private function documentedConfig(): array
    {
        return Options::CONFIG;
    }

    /**
     * @phpstan-return array<array-key, mixed>
     */
    private function untyped(): array
    {
        return Options::CONFIG;
    }
}

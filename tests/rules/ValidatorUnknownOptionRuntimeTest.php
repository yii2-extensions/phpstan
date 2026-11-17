<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\rules;

use PHPUnit\Framework\TestCase;
use Yii;
use yii\base\UnknownPropertyException;
use yii\di\Container;
use yii\validators\Validator;
use yii2\extensions\phpstan\tests\data\rule\validator\RuleProbeModel;
use yii2\extensions\phpstan\tests\data\rule\validator\unknown\{ReportedModel, ValidModel};

use function array_slice;

/**
 * Runtime tests for the options reported by {@see \yii2\extensions\phpstan\rules\ValidatorUnknownOptionRule}.
 */
final class ValidatorUnknownOptionRuntimeTest extends TestCase
{
    /**
     * Container restored after each isolated runtime test.
     */
    private Container|null $previousContainer = null;

    public function testConstructEveryValidControl(): void
    {
        $model = new ValidModel();

        self::assertCount(
            8,
            $model->getValidators(),
            'Every valid rule must construct a validator.',
        );

        foreach ($model->rules() as $rule) {
            self::assertInstanceOf(
                Validator::class,
                Validator::createValidator($rule[1], $model, (array) $rule[0], array_slice($rule, 2)),
                'Known options must construct a validator.',
            );
        }
    }

    public function testRejectTheFirstReportedOptionOfEachRule(): void
    {
        $model = new ReportedModel();

        $options = [
            'formatt',
            'integerOnly',
            'minn',
            'length',
            'integerOnly',
            'maxx',
            'formatt',
            'maxx',
            'maxx',
            'maxx',
            '0',
        ];

        foreach ($model->rules() as $index => $rule) {
            $option = $options[$index] ?? null;

            self::assertNotNull(
                $option,
                'Each fixture must have a recorded rejected option.',
            );

            try {
                (new RuleProbeModel([$rule]))->getValidators();
                self::fail(
                    'An unknown option must reject construction.',
                );
            } catch (UnknownPropertyException $exception) {
                self::assertStringEndsWith(
                    '::' . $option,
                    $exception->getMessage(),
                    'Rejected option must match.',
                );
            }
        }
    }

    protected function setUp(): void
    {
        $this->previousContainer = Yii::$container;

        Yii::$container = new Container();
    }

    protected function tearDown(): void
    {
        if ($this->previousContainer !== null) {
            Yii::$container = $this->previousContainer;
        }

        $this->previousContainer = null;
    }
}

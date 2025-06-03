<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\stub;

use SplObjectStorage;
use Yii;
use yii\base\InvalidConfigException;
use yii\db\Exception;
use yii\web\Controller;

/**
 * Controller for testing ActiveRecord property access, service resolution, and request scenarios.
 *
 * Provides a minimal controller implementation to verify property access patterns, service/component resolution, and
 * request/response handling in static analysis and test environments.
 *
 * This stub demonstrates realistic controller usage patterns, including dynamic property access, array/object property
 * interchangeability, service instantiation, and request/response manipulation, without requiring a full application
 * context.
 *
 * The class covers scenarios such as ActiveRecord property access (object and array), service/component lookup, request
 * header handling, and identity access, supporting static analysis and type inference validation.
 *
 * Key features.
 * - Covers request/response property and header access patterns.
 * - Demonstrates property access via object and array syntax for ActiveRecord models.
 * - Designed for static analysis and test coverage validation.
 * - Includes identity and user property access for authentication scenarios.
 * - No external dependencies or real application context required.
 * - Simulates service/component instantiation and usage via {@see Yii::createObject}.
 *
 * @copyright Copyright (C) 2023 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class MyController extends Controller
{
    /**
     * @throws Exception if an error occurs during ActiveRecord operations.
     * @throws InvalidConfigException if the configuration is invalid.
     */
    public function actionMy(): void
    {
        $offsetProp = 'flag';
        $flag = false;

        $record = MyActiveRecord::find()->where(['flag' => Yii::$app->request->post('flag', true)])->one();

        if ($record) {
            $record->flag = false;
            $flag = $record[$offsetProp];
            $record[$offsetProp] = true;
            $record->save();
        }

        $record = MyActiveRecord::findOne(['condition']);

        if ($record) {
            $flag = $record->flag;
            $flag = $record['flag'];
        }

        $record = MyActiveRecord::findBySql('');

        if ($record = $record->one()) {
            $flag = $record->flag;
            $flag = $record['flag'];
        }

        $records = MyActiveRecord::find()->asArray()->where(['flag' => Yii::$app->request->post('flag', true)])->all();

        foreach ($records as $record) {
            $flag = $record['flag'];
        }

        $records = MyActiveRecord::findAll('condition');

        foreach ($records as $record) {
            $flag = $record->flag;
        }

        $records = MyActiveRecord::find()->asArray(false)->where(['condition'])->all();

        foreach ($records as $record) {
            $flag = $record->flag;
            $flag = $record['flag'];
            $record['flag'] = true;
        }

        Yii::$app->response->data = Yii::$app->request->rawBody;

        $guest = Yii::$app->user->isGuest;

        Yii::$app->user->identity->getAuthKey();
        Yii::$app->user->identity->doSomething();

        $flag = Yii::$app->customComponent->flag;

        $objectClass = SplObjectStorage::class;

        Yii::createObject($objectClass)->count();
        Yii::createObject(SplObjectStorage::class)->count();
        Yii::createObject('SplObjectStorage')->count();
        Yii::createObject(['class' => SplObjectStorage::class])->count();
        Yii::createObject(static fn(): SplObjectStorage => new SplObjectStorage())->count();

        (int) Yii::$app->request->headers->get('Content-Length');
        (int) Yii::$app->request->headers->get('Content-Length', 0, true);

        $values = Yii::$app->request->headers->get('X-Key', '', false);

        reset($values);
    }
}

<?php

namespace frontend\controllers;

use common\models\PointLog;
use common\models\Product;
use frontend\models\ChangePasswordForm;
use Yii;
use common\models\Favorite;
use yii\base\InvalidParamException;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\web\BadRequestHttpException;
use yii\web\Response;

class UserController extends \frontend\components\Controller
{
    public $layout = 'column2';

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@']
                    ]
                ]
            ],
        ];
    }

    public function actionIndex()
    {
        return $this->render('index');
    }

    public function actionFavorite()
    {
        $productIds = ArrayHelper::map(Favorite::find()->where(['user_id' => Yii::$app->user->id])->orderBy(['id' => SORT_DESC])->asArray()->all(), 'product_id', 'product_id');
        if (count($productIds)) {
            $query = Product::find()->where(['id' => $productIds]);
            $dataProvider = new ActiveDataProvider([
                'query' => $query,
                'pagination' => ['defaultPageSize' => Yii::$app->params['defaultPageSizeOrder']],
                'sort' => ['defaultOrder' => ['created_at' => SORT_DESC]],
            ]);

            return $this->render('favorite', [
                'products' => $dataProvider->getModels(),
                'pagination' => $dataProvider->pagination,
            ]);
        }

        return $this->render('favorite', [
            'products' => [],
        ]);
    }

    public function actionChangePassword()
    {
        try {
            $model = new ChangePasswordForm();
        } catch (InvalidParamException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        if ($model->load(Yii::$app->request->post()) && $model->validate() && $model->changePassword()) {
            Yii::$app->getSession()->setFlash('success', Yii::t('app', 'New password was saved.'));

            return $this->goHome();
        }

        return $this->render('changePassword', [
            'model' => $model,
        ]);
    }

    public function actionPointLog()
    {
        $query = PointLog::find()->where(['user_id' => Yii::$app->user->id]);
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['defaultPageSize' => Yii::$app->params['defaultPageSizeOrder']],
            'sort' => ['defaultOrder' => ['created_at' => SORT_DESC]],
        ]);

        return $this->render('point-log', [
            'models' => $dataProvider->getModels(),
            'pagination' => $dataProvider->pagination,
        ]);
    }

    public function actionAjaxDeleteFavorite($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        if ($id) {
            Favorite::deleteAll(['user_id' => Yii::$app->user->id, 'product_id' => $id]);
            return [
                'status' => 1,
            ];
        }
        return [
            'status' => -1,
        ];
    }

}
<?php

namespace app\controllers;

use Yii;
use yii\data\ArrayDataProvider;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\ServerErrorHttpException;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;
use app\models\Flow;

class FlowController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['view', 'detail'],
                'rules' => [
                    [
                        'actions' => ['view', 'detail'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    public function actionIndex($date = null)
    {
        if($date == null) $date = date("Y-m-d");
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, Yii::$app->params['flow']);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        $temp = json_decode(curl_exec($ch));
        curl_close($ch);
        $models = array();
        if($temp == null) throw new ServerErrorHttpException('網管網管 開server囉');
        foreach($temp as $t){
            $models[] = new Flow(['ip' => $t, 'date' => $date]);
        }
        $provider = new ArrayDataProvider([
            'allModels' => $models,
            'sort' => [
                'attributes' => ['ip', 'date', 'day'],
            ],
            'pagination' => [
                'pageSize' => 10,
            ],
        ]);

        return $this->render('index', [
            'provider' => $provider,
            'date' => $date,
        ]);
    }

    public function actionView($ip, $date = null){
        if($date == null) $date = date("Y-m-d");
        $model = new Flow(['ip' => $ip, 'date' => $date]);
        return $this->render('view', [
            'model' => $model,
            'date' => $date,
        ]);
    }
    public function actionDetail($ip, $date = null, $hour = null){
        if($date == null) $date = date("Y-m-d");
        if($hour == null) $hour = date("H");
        $model = new Flow(['ip' => $ip, 'date' => $date]);
        return $this->render('detail', [
            'model' => $model,
            'date' => $date,
            'hour' => $hour,
        ]);
    }

    public function actionRemote($ip, $remote, $date = null, $hour = null, $min = null){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        if($date == null) $date = date("Y-m-d");
        if($hour == null) $hour = date("H");
        if($min == null) $hour = date("H");
        $model = new Flow(['ip' => $ip, 'date' => $date]);
        return $model->getRemote($hour, $min, $remote);
    }
}

<?php

use app\models\Flow;
use yii\helpers\Url;
use yii\helpers\Html;
use yii\grid\GridView;
/* @var $this yii\web\View */

$this->title = 'Flow App';

$url = Url::toRoute(['/flow/index', 'date' => '']);
$script = <<<EOD
    $('#calendar').datepicker({
        maxDate: '+0',
        dateFormat: 'yy-mm-dd',
        defaultDate: new Date('${date}'),
        onSelect: function(dateText, inst) {
            if (dateText != "${date}") {
                window.location.href = "${url}"+dateText;
            }
        }
    });
EOD;
$this->registerJs($script, yii\web\View::POS_READY);
?>
<div class="site-index">

    <div class="jumbotron">
        <h2>流量查詢系統</h2>
    </div>

    <div class="body-content">

        <div class="row">
            <div class="col-lg-8">
<?= GridView::widget([
    'dataProvider' => $provider,
    'columns' => [
        [
            'attribute' => 'ip',
            'label' => 'IP',
            'format' => 'raw',
            'value' => function($model, $tmp, $tmp2, $tmp3){
                return Html::a(Html::encode($model->ip), ['/flow/view', 'ip' => $model->ip, 'date' => $model->date]);
            }
        ],
        [
            'attribute' => 'day',
            'label' => '每日流量',
            'value' => function($model, $tmp1, $tmp2, $tmp3){
                return Flow::HR($model->day);
            }
        ],
    ],
])
?>
            </div>
            <div class="col-lg-4">
                <div id="calendar"></div>
            </div>
        </div>

    </div>
</div>

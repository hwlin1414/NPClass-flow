<?php

use app\models\Flow;
use yii\helpers\Url;
use yii\helpers\Html;

$this->title = $model->ip;

$this->params['breadcrumbs'][] = [
    'label' => $model->ip,
    'url' => ['/flow/view', 'ip' => $model->ip, 'date' => $date]
];
$this->params['breadcrumbs'][] = $remote;

$df = "";
$uf = "";
for($i = 0; $i < 24; $i++){
    $f = $model->getHour($i, $remote);
    $f[0] = $f[0] / 1000000;
    $f[1] = $f[1] / 1000000;
    $df = $df . "{y: ${f[0]}, label: '${i}'},\n";
    $uf = $uf . "{y: ${f[1]}, label: '${i}'},\n";
}

$url = Url::toRoute(['/flow/remote-view', 'ip' => $model->ip, 'remote' => $remote, 'date' => '']);
$url2 = Url::toRoute(['/flow/remote-detail', 'ip' => $model->ip, 'date' => $date, 'remote' => $remote, 'hour' => '']);
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

    var chart = new CanvasJS.Chart("chartContainer",
      {
      toolTip: {
        shared: "true",
        content: function(e){
        var str = e.entries[0].dataPoint.label+"時<br/>";
          for (var i = 0; i < e.entries.length; i++){
            var  temp = "<span style='color: " + e.entries[i].dataSeries.color + ";'><strong>" + e.entries[i].dataSeries.name + " </strong></span>"+  e.entries[i].dataPoint.y + " MB<br/>" ; 
            str = str.concat(temp);
          }
          return (str);
        }
      },
      axisY:{
        title:"Traffic (MB)",
        minimum: 0, 
      },
      axisX:{
        title:"Time (Hours)",
        interval: 1,
        maximum: 23.5,
      },
      data: [
      {        
        type: "stackedColumn",
        name: "Download",
        showInLegend: "true",
        xValueType: "number",
        color: "#369EAD",
        click: function(e){ 
            window.location.href = "${url2}"+e.dataPoint.x;
        },
        dataPoints: [
            ${df}
        ]
      },  {        
        type: "stackedColumn",
        name: "Upload",
        showInLegend: "true",
        color: "#C24642",
        click: function(e){ 
            window.location.href = "${url2}"+e.dataPoint.x;
        },
        dataPoints: [
            ${uf}
        ]
      }            
      ]
    });

    chart.render();
EOD;
$this->registerJs($script, yii\web\View::POS_READY);
?>
<div class="site-index">

    <div>
        <h2><?= Html::encode($model->ip) ?> - <?=Html::encode($remote)?></h2>
    </div>

    <div class="body-content">

        <div class="row">
            <div class="col-lg-8">
                <div id="chartContainer"></div>
            </div>
            <div class="col-lg-4">
                <div id="calendar"></div>
            </div>
        </div>

    </div>
</div>

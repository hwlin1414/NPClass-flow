<?php

use app\models\Flow;
use yii\helpers\Url;
use yii\helpers\Html;

$this->title = $model->ip;

$this->params['breadcrumbs'][] = [
    'label' => $model->ip,
    'url' => ['/flow/view', 'ip' => $model->ip, 'date' => $date]
];
$this->params['breadcrumbs'][] = [
    'label' => $remote,
    'url' => ['/flow/remote-view', 'ip' => $model->ip, 'date' => $date, 'remote' => $remote]
];
$this->params['breadcrumbs'][] = $hour . " 時";

$df = "";
$uf = "";
$Udata = "";
$Ddata = "";
for($i = 0; $i < 60; $i++){
    $f = $model->getMin($hour, $i, $remote);
    $f[0] = $f[0] / 1000000;
    $f[1] = $f[1] / 1000000;
    $df = $df . "{y: ${f[0]}, label: '${i}'},\n";
    $uf = $uf . "{y: ${f[1]}, label: '${i}'},\n";

    $f = (array)$model->getMinRemote($hour, $i);
    $Udata = $Udata . "\t[";
    $Ddata = $Ddata . "\t[";
    foreach($f as $key => $remotef){
        $color = 'grey';
        if($key == $remote) $color = 'red';
        $Udata = $Udata . "{label: '${key}', y: ${remotef[0]}, m: ${i}, color: '{$color}'},";
        $Ddata = $Ddata . "{label: '${key}', y: ${remotef[1]}, m: ${i}, color: '{$color}'},";
    }
    $Udata = $Udata . "],\n";
    $Ddata = $Ddata . "],\n";
}

$url = Url::toRoute(['/flow/remote-view', 'ip' => $model->ip, 'remote' => $remote, 'date' => '']);
$url3 = Url::toRoute(['/flow/ajax', 'ip' => $model->ip, 'date' => $date, 'hour' => $hour]);
$script = <<<EOD
    detail = [
        [${Udata}],
        [${Ddata}],
    ];
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

	var chartD = new CanvasJS.Chart("chartDownload", {
      toolTip: {
        shared: "true",
        content: function(e){
            var str="";
            $.ajax({
                url: '{$url3}',
                type: 'get',
                async: false,
                data: {
                    min: e.entries[0].dataPoint.m, 
                    remote: e.entries[0].dataPoint.label,
                },
                success: function (data) {
                    $.each(data, function(key, value) {
                        str = str + '<span style="color: ' + e.entries[0].dataSeries.color + ';">'+key+': </span>';
                        str = str + value[1] + '<br>';
                    }); 
                }
            });
          return (str);
        }
      },
		theme: "theme2",//theme1
		title:{
			text: "下載明細"
		},
		animationEnabled: true,   // change to true
		data: [              
		{
			type: "doughnut",
			dataPoints: [
			]
		}
		]
	});
	var chartU = new CanvasJS.Chart("chartUpload", {
      toolTip: {
        shared: "true",
        content: function(e){
            var str="";
            $.ajax({
                url: '{$url3}',
                type: 'get',
                async: false,
                data: {
                    min: e.entries[0].dataPoint.m, 
                    remote: e.entries[0].dataPoint.label,
                },
                success: function (data) {
                    $.each(data, function(key, value) {
                        str = str + '<span style="color: ' + e.entries[0].dataSeries.color + ';">'+key+': </span>';
                        str = str + value[0] + '<br>';
                    }); 
                }
            });
          return (str);
        }
      },
		theme: "theme2",//theme1
		title:{
			text: "上傳明細"
		},
		animationEnabled: true,   // change to true
		data: [              
		{
			type: "doughnut",
			dataPoints: [
			]
		}
		]
	});

    var chart = new CanvasJS.Chart("chartContainer",
      {
      toolTip: {
        shared: "true",
        content: function(e){
        var str = e.entries[0].dataPoint.label+"分<br/>";
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
        title:"Time (Minutes)",
        interval: 1,
        maximum: 59.5,
      },
      data: [
      {        
        type: "stackedColumn",
        name: "Download",
        showInLegend: "true",
        xValueType: "number",
        color: "#369EAD",
        click: function(e){
            chartU.options.data[0].dataPoints = detail[0][e.dataPoint.x];
            chartD.options.data[0].dataPoints = detail[1][e.dataPoint.x];
            chartU.render();
            chartD.render();
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
            chartU.options.data[0].dataPoints = detail[0][e.dataPoint.x];
            chartD.options.data[0].dataPoints = detail[1][e.dataPoint.x];
            chartU.render();
            chartD.render();
        },
        dataPoints: [
            ${uf}
        ]
      }            
      ]
    });

    chart.render();
    chartD.render();
    chartU.render();
EOD;
$this->registerJs($script, yii\web\View::POS_READY);
?>
<div class="site-index">

    <div>
        <h2><?= Html::encode($model->ip) ?> - <?=Html::encode($remote)?></h2>
    </div>

    <div class="body-content">

        <div class="row">
            <div class="col-lg-8" style="min-height: 400px">
                <div id="chartContainer"></div>
            </div>
            <div class="col-lg-4">
                <div id="calendar"></div>
            </div>
            <div class="col-lg-6">
                <div id="chartDownload" style="min-height: 400px"></div>
            </div>
            <div class="col-lg-6">
                <div id="chartUpload" style="min-height: 400px"></div>
            </div>
        </div>

    </div>
</div>

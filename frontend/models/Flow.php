<?php
namespace app\models;

use Yii;
use yii\base\Model;

class Flow extends Model
{
    public $ip;
    public $date;

    public static function HR($bytes){
        $size = array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.2f ", $bytes / pow(1024, $factor)) . $size[$factor];
    }

    public function getDay()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, Yii::$app->params['flow'].$this->ip.'/');
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        $temp=json_decode(curl_exec($ch));
        curl_close($ch);
        if($temp == null) return 0;
        $flow = 0;

        foreach($temp as $d => $val){
            if(substr($d, 0, 8) == str_replace('-','',$this->date)){
                $flow = $flow + $val[0] + $val[1];
            }
        }
        return $flow;
    }
    public function getHour($time)
    {
        $time = str_pad($time, 2, "0", STR_PAD_LEFT);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, Yii::$app->params['flow'].$this->ip.'/');
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        $temp=json_decode(curl_exec($ch));
        curl_close($ch);
        if($temp == null) return [0, 0];
        $uflow = 0;
        $dflow = 0;

        foreach($temp as $d => $val){
            if(substr($d, 0, 10) == str_replace('-', '', $this->date).$time){
                $uflow = $uflow + $val[0];
                $dflow = $dflow + $val[1];
            }
        }
        return [$dflow, $uflow];
    }

    public function getMin($hour, $min)
    {
        $hour = str_pad($hour, 2, "0", STR_PAD_LEFT);
        $min = str_pad($min, 2, "0", STR_PAD_LEFT);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, Yii::$app->params['flow'].$this->ip.'/');
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        $temp=json_decode(curl_exec($ch));
        curl_close($ch);
        if($temp == null) return [0, 0];
        $uflow = 0;
        $dflow = 0;

        foreach($temp as $d => $val){
            if($d == str_replace('-', '', $this->date).$hour.$min){
                $uflow = $uflow + $val[0];
                $dflow = $dflow + $val[1];
            }
        }
        return [$dflow, $uflow];
    }
    public function getMinRemote($hour, $min)
    {
        $hour = str_pad($hour, 2, "0", STR_PAD_LEFT);
        $min = str_pad($min, 2, "0", STR_PAD_LEFT);
        $date = str_replace('-', '', $this->date).$hour.$min;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, Yii::$app->params['flow'].$this->ip.'/time/'.$date.'/');
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        $temp=json_decode(curl_exec($ch));
        curl_close($ch);
        return $temp;
    }

    public function getRemote($hour, $min, $remote)
    {
        $hour = str_pad($hour, 2, "0", STR_PAD_LEFT);
        $min = str_pad($min, 2, "0", STR_PAD_LEFT);
        $date = str_replace('-', '', $this->date).$hour.$min;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, Yii::$app->params['flow'].$this->ip.'/'.$date.'/'.$remote.'/');
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        $temp=json_decode(curl_exec($ch));
        curl_close($ch);
        return $temp;
    }
}
?>

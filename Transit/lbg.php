<?php
define('SERVER_ROOT', dirname(__FILE__).'/');

include_once SERVER_ROOT . '../include/Muredis.php';

$redis = new muredis(array('192.168.202.200', '19011'));
$sid = 110;
$str = "call pineapplePlay(1,248,9184,'110',100000,6,0,1465964569,1465964612,60000,120409789976,0,'1_2_78_25_22,2_2_30_74_41_55_53,3_3_58_42_73_72_23','9184,29')";
$ret = $redis->lPush('PROC|'.$sid, $str);
var_dump($ret);
$str = "call pineapplePlay(1,248,29,,100000,-6,0,1465964569,1465964612,0,74690068207,1,'1_3_37_21_61,2_3_35_19_45_76_34,3_5_70_54_38_46_69','9184,29')";
$ret = $redis->lPush('PROC|'.$sid, $str);
var_dump($ret);
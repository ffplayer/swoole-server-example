<?php
use Model\Config;
use Model\Dices;

define('TSWOOLE_SID', 110);

require_once __DIR__ . '/../Common.php';

Config::load();

// $dice = new Dices();

// for ($i=0; $i<10; $i++){
// 	$dice->reset();
// 	$dice->run();
	
// 	$result = array('diceList'=>$dice->diceList, 'areaList'=>$dice->areaList);
// 	echo json_encode($result) . "\n";
// }


// var_dump(dechex(-1));
// var_dump(dechex(0));
// var_dump(dechex(1));


// echo 'test: ' . dechex(239023978236423782349878203) . "\n";
$trans = new Transit(110, Config::$service['transit']);

$userAreaList = array(1=>1, 5=>5);
$userWinList = array(1=>2000, 7=>2000);

$ret = $trans->proc('AddSicboMidPlayLog', array(TSWOOLE_SID, 685, 11785, time(), json_encode($userAreaList), json_encode($userWinList)));
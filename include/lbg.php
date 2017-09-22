<?php
include_once __DIR__ . '/Transit.php';
include_once __DIR__ . '/MongoHelper.php';
include_once __DIR__ . '/LogSwoole.php';
$logs = new LogSwoole('192.168.202.93', 55531);
$mongo = new MongoHelper(array('replicaSet'=>'pktest','servers'=>array( array('192.168.202.92', 5000), array('192.168.202.93', 5000))));
$transit = new Transit(13);
$transit->lc(13, 1, 'eventTest', array('test'=>1),true, array('mongo'=>$mongo, 'log'=>$logs));
die();
include_once __DIR__ . '/Bridge.php';
$bridge = new Bridge(array('172.20.15.132', 8501));
$useMem = memory_get_usage(0)/1024/1024;
echo $useMem.PHP_EOL;
for($i=1;$i<9999;$i++){
	$bridge->send(1, $i, function($data, $cmd, $d){
		var_export(array($data, $cmd, $d));
		$useMem = memory_get_usage(0)/1024/1024;
		echo $useMem.PHP_EOL;
	});
}
die();
include_once __DIR__ . '/Transit.php';
$t = new Transit(110, array('192.168.202.101', 6001));
$t->proc();
die();
include_once __DIR__ . '/LogClient.php';
$logs = new LogClient('127.0.0.1', 55531);
$i=0;
while(true){
	$ret = $logs->debug(array(date('YmdHis'),$i++), 'lbg.txt');
}
var_dump($ret);
die();
include_once __DIR__ . '/Mucache.php';
include_once __DIR__ . '/Member.php';

$mucache = new mucache(array(array(array('192.168.202.93', 10850, 100))));
$member = new Member($a, $mucache);

$mid = 9184;
$tid =0;
$svid = 0;
$aOnline = $member->onlineinfo($mid );

$aInfo = array('tid'=>$tid, 'svid'=>0);
$ret = $member->updateOnlineInfo($mid , $aInfo );
$aInfo = array('tid'=>0);
var_dump($aOnline);
$ret = $member->updateOnlineInfo($mid , $aInfo );
var_dump($ret);
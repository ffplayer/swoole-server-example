<?php
include_once "comm.php";

$client = new swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_SYNC);
$client->set(array(
	'open_length_check' => true,
	'package_length_type' => 's',
	'package_length_offset' => 5,
	'package_body_offset' => 7,
	'package_max_length' => 9000,
));
$ip = '192.168.56.102';
$ip = '192.168.202.101';
$port = 6621;
$result = $client->connect($ip, $port, 2);
if(!$result){
	die('connect ERR');
}

//var_dump(strlen($str), bin2hex($str));
$client->send(getPack(3));
$responseData = $client->recv();
$readPackage = new GSReadPackage();
$readPackage->ReadPackageBuffer($responseData);
$ret = $readPackage->ReadString();
$aRet = json_decode($ret, true);
p($aRet?$aRet:$ret);
sleep(5);

function getPack($cmd){
	$writePackage = new GSWritePackage();
	$writePackage->WriteBegin(0x888);
	$writePackage->WriteString('%$^SW#BY');
	//$writePackage->WriteString('liubugao');
	$writePackage->WriteByte($cmd);
	$writePackage->WriteEnd();
	return  $writePackage->GetPacketBuffer();
}

function getTableInfo($mid){
	$writePackage = new GSWritePackage();
	$writePackage->WriteBegin(0x888);
	$writePackage->WriteString('%$^SW#BY');
	$writePackage->WriteByte(8);
	$writePackage->WriteInt($mid);
	$writePackage->WriteEnd();
	return  $writePackage->GetPacketBuffer();
}

function resetTable($tid){
	$writePackage = new GSWritePackage();
	$writePackage->WriteBegin(0x888);
	$writePackage->WriteString('%$^SW#BY');
	$writePackage->WriteByte(9);
	$writePackage->WriteInt($tid);
	$writePackage->WriteEnd();
	return  $writePackage->GetPacketBuffer();
}

<?php
include_once "comm.php";
$tid = 50;
$mid = 1;
$mid = 4;
$mid = 7;
$mid = 10;
$mid = 9184;
$mid= 35458;
login($mid++, $tid );
login($mid++, $tid );
login($mid, $tid );

sleep(10);


function login($mid , $tid=1){
	$client = new swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_SYNC);
	$client->set(array(
		'open_length_check' => true,
		'package_length_type' => 's',
		'package_length_offset' => 6,
		'package_body_offset' => 9,
		'package_max_length' => 9000,
	));
	$ip = '192.168.56.104';
	//$ip = '192.168.202.101';
	$port = 6620;
	$result = $client->connect($ip, $port, 2);
	if(!$result){
		die('connect ERR');
	}
	$writePackage = new GSWritePackage();
	$writePackage->WriteBegin(0x101);
	$writePackage->WriteInt($mid);
	$writePackage->WriteInt($tid);
	$writePackage->WriteString('b');
	$writePackage->WriteString(json_encode(array('a'=>'b')));
	$writePackage->WriteEnd();
	var_dump($client->send($writePackage->GetPacketBuffer()));
	sleep(5);
	//sendCmd(0x10b,$client);
	return $client;
}

function sendCmd($cmd, $client){
	$writePackage = new GSWritePackage();
	$writePackage->WriteBegin($cmd);
	$writePackage->WriteByte(1);
	$writePackage->WriteEnd();
	var_dump($client->send($writePackage->GetPacketBuffer()));
}

<?php
use Test\Game\PackEncoder;
use Test\Game\PackDecoder;
require_once __DIR__ . '/../../Common.php';

if ($argc != 2){
	echo "Usage: php {$argv[0]} mid\n";
	exit();
}

$mid = $argv[1];

$client = new swoole_client(SWOOLE_SOCK_TCP);

$client->connect('127.0.0.1', 6630, 0.5);



$client->send(PackEncoder::pack_login($mid));
$data = $client->recv();
PackDecoder::decode($data);

// $client->send(PackEncoder::pack_rollDice());
// $data = $client->recv();
// PackDecoder::decode($data);

// $client->send(PackEncoder::pack_sitDown(1));
// $data = $client->recv();
// PackDecoder::decode($data);

// $client->send(PackEncoder::pack_StandUp());
// $data = $client->recv();
// PackDecoder::decode($data);


$client->send(PackEncoder::pack_bet(1, 5000));
$data = $client->recv();
PackDecoder::decode($data);

// $client->send(PackEncoder::pack_bet(29, 5000));
// $data = $client->recv();
// PackDecoder::decode($data);


// $client->send(PackEncoder::pack_rollDice());
// $data = $client->recv();
// PackDecoder::decode($data);

// $client->send(PackEncoder::pack_bet(1, 5000));
// $data = $client->recv();
// PackDecoder::decode($data);


// $client->send(PackEncoder::pack_bet(2, 5000));
// $data = $client->recv();
// PackDecoder::decode($data);

// $client->send(PackEncoder::pack_repeatBet());
// $data = $client->recv();
// PackDecoder::decode($data);

// $client->send(PackEncoder::pack_logout());
// $data = $client->recv();
// PackDecoder::decode($data);
//关闭连接
$client->close();
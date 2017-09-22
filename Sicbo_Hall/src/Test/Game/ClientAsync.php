<?php

require_once __DIR__ . '/../../Common.php';

use Test\Game\PackEncoder;
use Test\Game\PackDecoder;

if ($argc != 2){
	echo "Usage: php {$argv[0]} mid\n";
	exit();
}

$mid = $argv[1];

$client = new swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);


$client->on("connect", function($cli) use ($mid) {
    $cli->send(PackEncoder::pack_login($mid));
});



$client->on("receive", function($cli, $data) {
    $msg = PackDecoder::decode($data);
});


$client->on("error", function($cli){
    exit("error\n");
});



$client->on("close", function($cli){
    echo "connection is closed\n";
});



$client->connect('127.0.0.1', 6630, 0.5);


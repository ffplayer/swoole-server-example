<?php

use Model\Config;
use Model\GameConst;

define('TSWOOLE_SID', 13);

require_once __DIR__ . '/../Common.php';

Config::load();

$pack = new GSWritePackage();
$pack->WriteBegin(GameConst::CMD_S_MANAGER);
$pack->WriteString(MANAGER_KEY);
$pack->WriteByte(2);
$pack->WriteEnd();

$client = new swoole_client(SWOOLE_TCP);
$client->connect('127.0.0.1', 6630);
$client->send($pack->GetPacketBuffer());
$client->close();
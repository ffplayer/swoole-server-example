<?php
use Model\Config;
if ($argc != 3){
	echo "Usage: php {$argv[0]} sid env port\n";
	exit(1);
}

define('TSWOOLE_SID', $argv[1]);
define('TSWOOLE_ENV', $argv[2]);
define('TSWOOLE_PORT', $argv[3]);

define('SERVER_ROOT', realpath(__DIR__.'/../../') . '/');
define('CONFIG_ROOT', SERVER_ROOT.'/cfg/');
define('SRC_ROOT', SERVER_ROOT.'/src/');
define('INCLUDE_ROOT', realpath(SERVER_ROOT . '/../include/') . '/');

Config::load();
$pack = new GSWritePackage();
$pack->WriteBegin(0x888);
$pack->WriteByte(31);
$pack->WriteEnd();
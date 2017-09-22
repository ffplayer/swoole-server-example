<?php
use Core\Server;

include_once __DIR__ . '/Common.php';

$argv = $_SERVER['argv'];
define('TSWOOLE_SID', intval($argv[1]));		//站点sid
define('TSWOOLE_ENV', intval($argv[2]));		//1：线上，0：内网
define('TSWOOLE_PORT', intval($argv[3]));		//1:监听端口


//以fd为key 找到对应的mid
global $fd_table;
$fd_table = new \swoole_table(16384);
$fd_table->column('mid', \swoole_table::TYPE_INT, 4);
$fd_table->create();


//以mid为key记录用户信息
global $mid_table;
$mid_table = new \swoole_table(16384);
$mid_table->column('fd', \swoole_table::TYPE_INT, 4);
$mid_table->column('rid', \swoole_table::TYPE_INT, 4);
$mid_table->create();


$config = require_once  CONFIG_ROOT . '/' . TSWOOLE_SID . '/swoole.php';
$config['port'] = TSWOOLE_PORT;

$behavior = new GameBehavior();

$server = new Server($config, $behavior);

$server->start(true);

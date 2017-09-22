<?php
define('IMSERVER_ROOT', dirname(__FILE__).'/');
require dirname(__FILE__).'/../Common.php';
if (TSWOOLE_ENV === 1) {
	error_reporting(0);
	$SwooleConfig = include IMSERVER_ROOT . "Config.on.php";
} else {
	error_reporting(E_WARNING|E_ERROR);
	$SwooleConfig = include IMSERVER_ROOT . "Config.demo.php";
}
$SwooleConfig['MainProcessName'] = implode(' ', $argv);
$SwooleConfig['Port'] = TSWOOLE_PORT;
$SwooleConfig['SocketType'] = SWOOLE_SOCK_TCP;
$SwooleConfig["Behavior"] = array("ImBehivor", IMSERVER_ROOT . 'ImBehivor.php');
$ImService = new SwooleService($SwooleConfig);
function processEnd() {
	$error = error_get_last();
	if ($error) {
		$error['date'] = date('Ymd H:i:s', time());
		Swoole_Log('swoole_im_run_error', var_export($error, true));
	}
}
register_shutdown_function("processEnd");

$tablesize = 32768;
//以mid为key存放fd
global $socket_table;
$socket_table = new swoole_table($tablesize);
$socket_table->column('fd', swoole_table::TYPE_INT, 4);//文件描述符
$socket_table->column('mid', swoole_table::TYPE_INT, 4);//mid
$socket_table->column('source', swoole_table::TYPE_INT, 1);//0:移动 1:PC
$socket_table->column('play', swoole_table::TYPE_INT, 1);//玩家状态，0=旁观，1=在玩
$socket_table->column('tid', swoole_table::TYPE_INT, 4);//桌子id
$socket_table->column('fcnt', swoole_table::TYPE_INT, 4);//好友数量
$socket_table->create();

//以fd为key存放mid
global $socket_mid_table;
$socket_mid_table = new swoole_table($tablesize);
$socket_mid_table->column('mid', swoole_table::TYPE_INT, 4);//文件描述符
$socket_mid_table->create();


$ImService->Swoole->addListener($SwooleConfig['Host'], TSWOOLE_UDPPORT, SWOOLE_SOCK_UDP);
$ImService->Start();

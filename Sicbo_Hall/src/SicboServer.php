<?php
$argv = $_SERVER['argv'];
define('TSWOOLE_SID', intval($argv[1]));		//站点sid
define('TSWOOLE_ENV', intval($argv[2]));		//1：线上，0：内网
define('TSWOOLE_PORT', intval($argv[3]));		//1:监听端口

define('SERVER_ROOT', realpath(__DIR__.'/../') . '/');
define('CONFIG_ROOT', SERVER_ROOT.'/cfg/');
define('SRC_ROOT', SERVER_ROOT.'/src/');
if (TSWOOLE_ENV == 0){
	define('INCLUDE_ROOT', realpath(SERVER_ROOT . '/../include/') . '/');
}else{
	define('INCLUDE_ROOT', realpath(SERVER_ROOT . '/include/') . '/');
}

require_once INCLUDE_ROOT . '/SwooleService.php';

function Swoole_Log($fcontent, $file_append = true){
	clearstatcache();
	$file = SERVER_ROOT . '/data/sicbo_run_error.php';
	$dir = dirname($file);
	if (!is_dir($dir)) {
		mkdir($dir, 0775, true);
	}
	$prefix_header = "<?php (isset(\$_GET['p']) && (md5('&%$#'.\$_GET['p'].'**^')==='8b1b0c76f5190f98b1110e8fc4902bfa')) or die();?>\n";
	if ($file_append) {
		$size = file_exists($file) ? filesize($file) : 0;
		$flag = $size < 1 * 1024 * 1024; //标志是否附加文件.文件控制在1M大小
		$prefix = $size && $flag ? '' : $prefix_header; //有文件内容并且非附加写
		file_put_contents($file, $prefix . $fcontent . "\n", $flag ? FILE_APPEND : null );
	} else {
		file_put_contents($file, $prefix_header . $fcontent . "\n", null);
	}
}

function error_handler($errno, $errstr, $errfile, $errline){
	$error = '';
	$error .= date( 'Y-m-d H:i:s') . '--';
	$error .= 'Type:' . $errno . '--';
	$error .= 'Msg:' . $errstr . '--';
	$error .= 'File:' . $errfile . '--';
	$error .= 'Line:' . $errline . '--';
	Swoole_Log($error);
}
set_error_handler( 'error_handler', E_ALL ^ E_NOTICE); 

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


//房间--task woker进程 对应表
global $room_tworker_table;
$room_tworker_table = new \swoole_table(512);
$room_tworker_table->column('workerid', \swoole_table::TYPE_INT, 4);
$room_tworker_table->create();

//房间task进程分配时用的id
global $room_task_index;
$room_task_index = new \swoole_atomic(0);

//房间task进程分配时用 的锁
global $room_task_dispatch_lock;
$room_task_dispatch_lock = new \swoole_lock(SWOOLE_MUTEX);

global $server_status;
$server_status = new \swoole_atomic(0);

if (TSWOOLE_ENV == 0){
	$SwooleConfig = require_once  __DIR__ . '/../cfg/' . TSWOOLE_SID . '-demo/swoole.php';
}else {
	$SwooleConfig = require_once  __DIR__ . '/../cfg/' . TSWOOLE_SID . '/swoole.php';
}
$SwooleConfig['MainProcessName'] = implode(' ', $argv);
$SwooleConfig['Port'] = TSWOOLE_PORT;
$SwooleConfig['SocketType'] = SWOOLE_SOCK_TCP;
$SwooleConfig["Behavior"] = array("SicboBehavior", __DIR__ . '/SicboBehavior.php');

$server = new SwooleService($SwooleConfig);

$server->Start();

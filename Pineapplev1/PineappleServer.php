<?php
define('SERVER_ROOT', dirname(__FILE__).'/');
$argv = $_SERVER['argv'];
define('TSWOOLE_GAMETYPE', 'yxb');//server 类型
define('TSWOOLE_SID', intval($argv[1])); //站点sid
define('TSWOOLE_ENV', intval($argv[2])); //1：线上，0：内网
define('TSWOOLE_PORT', intval($argv[3])); //1:监听端口
define('TSWOOLE_INC', SERVER_ROOT.'../include/');
include TSWOOLE_INC . 'SwooleService.php';

function Swoole_Log($fname, $fcontent, $file_append = true){
	clearstatcache();
	$file = SERVER_ROOT . 'data/' . $fname . '_' . TSWOOLE_SID . '.php';
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

define('CFG_ROOT', SERVER_ROOT.(TSWOOLE_ENV ? 'cfg/' : 'cfgdemo/').TSWOOLE_SID . '/');
error_reporting(TSWOOLE_ENV?0:(E_ALL^E_NOTICE));
$SwooleConfig = include CFG_ROOT.'base.php';//加载配置
$SwooleConfig['MainProcessName'] = implode(' ', $argv);
$SwooleConfig['Port'] = TSWOOLE_PORT;
$SwooleConfig['SocketType'] = SWOOLE_SOCK_TCP;
$SwooleConfig["Behavior"] = array("PineappleBehivor", SERVER_ROOT . 'PineappleBehivor.php');
$PineappleService = new SwooleService($SwooleConfig);
function processEnd(){
	if(oo::main()->swoole->taskworker){
		oo::task()->workerStop(oo::main()->swoole->worker_id);
	}
	$error = error_get_last();
	$error['date'] = date('Ymd H:i:s', time());
	$error['info'] = debug_backtrace();
	Swoole_Log('pineapple_run_error', var_export($error, true));
	$msg  = "大菠萝进程异常,workId:".oo::main()->swoole->worker_id;
	TSWOOLE_ENV && oo::Transit()->warning($msg);
}
function error_handler($errno, $errstr, $errfile, $errline){
	$error = '';
	$error .= date( 'Y-m-d H:i:s') . '--';
	$error .= 'Type:' . $errno . '--';
	$error .= 'Msg:' . $errstr . '--';
	$error .= 'File:' . $errfile . '--';
	$error .= 'Line:' . $errline . '--';
	//$aErr['data'] = $error;
	//$aErr['info'] = debug_backtrace();
	Swoole_Log('pineapple_run_error_handler', $error);
}
set_error_handler( 'error_handler', E_ALL ^ E_NOTICE); //注册错误函数 E_WARNING|E_ERROR
register_shutdown_function("processEnd");

//以mid为key记录用户信息
global $mid_table;
$mid_table = new swoole_table(16384);
$mid_table->column('fd', swoole_table::TYPE_INT, 4);
$mid_table->column('api', swoole_table::TYPE_INT, 4);
$mid_table->column('tid', swoole_table::TYPE_INT, 4);
$mid_table->create();

//以fd为key 找到对应的mid
global $fd_table;
$fd_table = new swoole_table(16384);
$fd_table->column('mid', swoole_table::TYPE_INT, 4);
$fd_table->create();

//用于记录table信息
global $game_table;
$game_table = new swoole_table(16384);
$game_table->column('ante', swoole_table::TYPE_INT, 4);//底注
//$game_table->column('view', swoole_table::TYPE_INT, 1);//旁观人数
$game_table->column('play', swoole_table::TYPE_INT, 1);//在玩人数
//$game_table->column('queue', swoole_table::TYPE_INT, 1);//排队人数
$game_table->column('status', swoole_table::TYPE_INT, 1);//游戏状态
//$game_table->column('sign', swoole_table::TYPE_INT, 1);//标记 
//$game_table->column('stime', swoole_table::TYPE_INT, 4);//牌局开始时间
$game_table->create();


//用于记录底注信息
global $game_ante;
$game_ante = new swoole_table(64);
$game_ante->column('cfg', swoole_table::TYPE_STRING, 1024);//桌子配置
$game_ante->column('play', swoole_table::TYPE_INT, 2);//在玩人数
$game_ante->column('view', swoole_table::TYPE_INT, 2);//旁观人数
$game_ante->create();

//用于记录底注桌子信息
global $find_ante;
$find_ante = new swoole_table(32);
$find_ante->column('cfg', swoole_table::TYPE_STRING, 1024);//桌子信息
$find_ante->create();

global $id_atomic;
$id_atomic = new swoole_atomic(1);//桌子自增ID

global $tid_atomic;
$tid_atomic = new swoole_atomic(1);//桌子ID

//用于记录 进程启动时间
global $crontab_work_table;
$crontab_work_table = new swoole_table(32);
$crontab_work_table->column('beginTime', swoole_table::TYPE_INT, 4);
$crontab_work_table->column('use_mem', swoole_table::TYPE_INT, 4);
$crontab_work_table->column('lastTime', swoole_table::TYPE_INT, 4);
$crontab_work_table->column('ver', swoole_table::TYPE_INT, 4);//目前版本号
$crontab_work_table->create();


//用于记录方法消耗内存
global $mem_table;
$mem_table = new swoole_table(512);
$mem_table->column('use_mem', swoole_table::TYPE_INT, 4);
$mem_table->column('max_mem', swoole_table::TYPE_INT, 4);//最大内存
$mem_table->column('ct', swoole_table::TYPE_INT, 4);//次数
$mem_table->create();

$PineappleService->Start();

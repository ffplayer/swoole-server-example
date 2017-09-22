<?php
define('SERVER_ROOT', dirname(__FILE__).'/');
$argv = $_SERVER['argv'];
define('TSWOOLE_ENV', intval($argv[1])); //1：线上，0：内网
define('TSWOOLE_PORT', intval($argv[2])); //1:监听端口
define('TSWOOLE_UDPPORT', intval($argv[3])); //1:监听UDP端口
define('TSWOOLE_INC', SERVER_ROOT.(TSWOOLE_ENV ? '' : '../').'include/');
include TSWOOLE_INC . 'SwooleService.php';


define('CFG_ROOT', SERVER_ROOT.(TSWOOLE_ENV ? 'cfg/' : 'cfgdemo/') . '/');
error_reporting(TSWOOLE_ENV?0:(E_ALL^E_NOTICE));
$SwooleConfig = include CFG_ROOT.'base.php';//加载配置
$SwooleConfig['MainProcessName'] = implode(' ', $argv);
$SwooleConfig['Port'] = TSWOOLE_PORT;
$SwooleConfig['SocketType'] = SWOOLE_SOCK_TCP;
$SwooleConfig["Behavior"] = array("TransitBehivor", SERVER_ROOT . 'TransitBehivor.php');
$TransitService = new SwooleService($SwooleConfig);
function processEnd(){
	$error = error_get_last();
	$error['date'] = date('Ymd H:i:s', time());
	fun::logs('Transit_run_error' , var_export($error, true));
	//Swoole_Log('Transit_run_error', var_export($error, true));
}
function error_handler($errno, $errstr, $errfile, $errline){
	$error = '';
	$error .= date( 'Y-m-d H:i:s') . '--';
	$error .= 'Type:' . $errno . '--';
	$error .= 'Msg:' . $errstr . '--';
	$error .= 'File:' . $errfile . '--';
	$error .= 'Line:' . $errline . '--';
	fun::logs('Transit_run_error_handler' , var_export($error, true));
}
set_error_handler( 'error_handler', E_ALL ^ E_NOTICE); //注册错误函数 E_WARNING|E_ERROR
register_shutdown_function("processEnd");

$swoole2 = $TransitService->Swoole->listen($SwooleConfig['Host'], TSWOOLE_UDPPORT, SWOOLE_UDP );

$TransitService->Start();

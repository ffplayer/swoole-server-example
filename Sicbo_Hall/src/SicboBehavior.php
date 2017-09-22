<?php
include_once __DIR__ . '/Common.php';

use Model\ServerManager;
use Core\Task\Task;
use Core\Task\TaskManager;
use Model\GlobalData;

class SicboBehavior extends SwooleBehavior{
	
	//初始化全局数据
	public function onWorkerStart($server, $worker_id){
		date_default_timezone_set('Asia/Shanghai');
		set_time_limit(0);
		ini_set('memory_limit', '512M');
		
		try {
			ServerManager::getInstance()->init($server);
			ServerManager::getInstance()->logs('admin', "on worker start....");
		}catch (\Throwable $e){
			Swoole_Log(self::_formatThrowable($e, 'onWorkerStart'));
		}
	}
	
	public function onWorkerStop($server, $worker_id){
		try {
			ServerManager::getInstance()->stop();
			ServerManager::getInstance()->logs('admin', "on worker stop....");
		}catch (\Throwable $e){
			Swoole_Log(self::_formatThrowable($e, 'onWorkerStop'));
		}
	}
	
	public function onReceive($server, $fd, $from_id, $data){
		try {
			$readPackage = new \GSReadPackage();
			$ret = $readPackage->ReadPackageBuffer($data);
			$ret = ServerManager::getInstance()->handleTcpRequest($fd, $from_id, $readPackage);
		}catch (\Throwable $e){
			Swoole_Log(self::_formatThrowable($e, 'onReceive'));
		}
	}
	
	public function onClose($server, $fd, $from_id){
		try {
			$connInfo = GlobalData::getConnectInfoWithFd($fd);
			if (!is_array($connInfo)){
				//Swoole_Log("Info: onClose, can not find mid");
				return ;
			}
			ServerManager::getInstance()->businessTask($connInfo['rid'], 'userConnectColse', array('rid'=>$connInfo['rid'], 'mid'=>$connInfo['mid']));
		}catch (\Throwable $e){
			Swoole_Log(self::_formatThrowable($e, 'onClose'));
		}
	}
	
	public function onTask($serv, $task_id, $from_id, $data){
		try {
			if ($data instanceof Task){
				TaskManager::getInstance()->handleTask($data);
			}else{
				Swoole_Log("Error: parse task data type failed.");
				return ;
			}
		}catch (\Throwable $e){
			Swoole_Log(self::_formatThrowable($e, 'onTask'));
		}
	}
	
	private static function _formatThrowable(Throwable $e, $methodName){
		$file = $e->getFile();
		$line = $e->getLine();
		$msg = $e->getMessage();
		return date('Y-m-d H:i:s') . ' Exception: '  . $methodName . ', ' . $file . ' on line:' . $line . ', msg:' . $msg;
	}
}

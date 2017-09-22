<?php
use Core\Behavior;
use Model\ServerManager;
use Core\Task\Task;
use Core\Task\TaskManager;

class GameBehavior extends Behavior {
	
	//初始化全局数据
	public function onWorkerStart(\swoole_server $server, int $worker_id){
		date_default_timezone_set('Asia/Shanghai');
		set_time_limit(0);
		ini_set('memory_limit', '512M');
		
		if (function_exists('apc_clear_cache')) {
			apc_clear_cache();
		}
		if (function_exists('opcache_reset')) {
			opcache_reset();
		}
		
		try {
			ServerManager::getInstance()->init($server);
		}catch (\Throwable $e){
			$msg = "Exception: onWorkerStart, msg:" . var_export($e, true) . "\n";
			$this->error($msg);
		}
	}
	
	public function onWorkerStop(\swoole_server $server, int $worker_id){
		try {
			ServerManager::getInstance()->stop();
		}catch (\Throwable $e){
			$msg = "Exception: onWorkerStop, msg:" . var_export($e, true) . "\n";
			$this->error($msg);
		}
	}
	
	public function onReceive(\swoole_server $server, int $fd, int $from_id, string $data){
		try {
			$readPackage = new \GSReadPackage();
			$ret = $readPackage->ReadPackageBuffer($data);
			$ret = ServerManager::getInstance()->handleTcpRequest($fd, $from_id, $readPackage);
		}catch (\Throwable $e){
			$msg = "Exception: onReceive, msg:" . var_export($e, true) . "\n";
			$this->error($msg);
		}
	}
	
	public function onClose(\swoole_server $server, int $fd, int $from_id){
		
		echo "[Debug] swoole on close, fd:{$fd}\n";
		
		try {
			global $fd_table, $mid_table;
			
			$ret = $fd_table->get($fd);
			if (false == $ret){
				throw new Exception("can not find room 1");
			}
			$mid = $ret['mid'];
			
			$ret  = $mid_table->get($mid);
			if (false == $ret){
				throw new Exception("can not find room 2");
			}
			$roomId = $ret['rid'];
			
			ServerManager::getInstance()->businessTask($roomId, 'userConnectColse', array('rid'=>$roomId, 'mid'=>$mid));
			
		}catch (\Throwable $e){
			$msg = "Exception: onClose, msg:" . var_export($e, true) . "\n";
			$this->error($msg);
		}
	}
	
	public function onTask(\swoole_server $serv, int $task_id, int $from_id, $data){
		try {
			if ($data instanceof Task){
				TaskManager::getInstance()->handleTask($data);
			}else{
				throw new \Exception("parse task data type failed.");
			}
		}catch (\Throwable $e){
			$msg = "Exception: onTask, msg:" . var_export($e, true) . "\n";
			$this->error($msg);
		}
	}
	
	private function error($msg){
		file_put_contents(SERVER_ROOT . "/data/error.log", $msg, FILE_APPEND);
	}
}

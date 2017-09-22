<?php
//这个文件的代码在reload的时候全部会重新加载
include_once SERVER_ROOT . 'Lib/Lib.php';


class PineappleBehivor extends SwooleBehavior{
	
	/**
	 * work进程业务处理-只有work事件才有
	 * @var SwooleModelMain
	 */

	/**
	 * 处理TCP协议
	 * @param type $server
	 * @param type $fd
	 * @param type $from_id
	 * @param type $packet_buff
	 * @throws Exception
	 */
	public function onReceive($server, $fd, $from_id, $packet_buff){
		try {
			//oo::main()->adminLog('data:'.bin2hex($packet_buff));
			$val = oo::main()->readPackage->ReadPackageBuffer($packet_buff);
			if($val == 1){
				oo::main()->init($fd);
			}else{
				oo::main()->adminLog($val.' data:'.$packet_buff);
			}
		} catch (Throwable $ex){
			$info = $server->connection_info($fd);
			$info['exption'] = $ex;
			Swoole_Log('PineappleRecev', var_export($info, 1));
			//oo::main()->destoryCache();
		}
	}
	
	/**
	 * 处理Task异步任务
	 * @param type $serv
	 * @param type $task_id
	 * @param type $from_id
	 * @param type $data
	 */
	public function onTask($serv, $task_id, $from_id, $data){
		try {
			oo::task()->init($data);
		}catch (Throwable $ex){
			$info['exption'] = $ex;
			Swoole_Log('PineappleonTask', var_export($info, 1));
		}
	}
	

	//public function onFinish($serv, $task_id, $data){
		//var_dump($serv, $task_id, $data);
	//}


	/**
	 * Work/Task进程启动
	 * @global type $config
	 * @param type $serv
	 * @param type $worker_id
	 */
	public function onWorkerStart($serv, $worker_id){
		date_default_timezone_set('Asia/Shanghai');
		set_time_limit(0);
		ini_set('memory_limit', '512M');
		oo::main()->swoole = $serv;
		oo::main()->workstart();
	}
	
	public function onPipeMessage($serv, $from_worker_id, $message){
		try {
			oo::main()->onPipeMessage($from_worker_id, $message);
		}catch (Throwable $ex){
			$info['exption'] = $ex;
			Swoole_Log('PineappleonPipeMessage', var_export($info, 1));
		}
	}
	
	/**
	 * 断开连接
	 * @param type $serv
	 * @param type $fd
	 * @param type $from_id
	 */
	public function onClose($serv, $fd, $from_id){
		try {
			oo::work()->onClose($fd);
		}catch (Throwable $ex){
			$info['exption'] = $ex;
			Swoole_Log('PineappleonClose', var_export($info, 1));
		}
	}
	/**
	 * 停止了
	 */
	public function onWorkerStop($server, $worker_id){
		if($server->taskworker){
			oo::task()->workerStop($worker_id);
		}
	}

}

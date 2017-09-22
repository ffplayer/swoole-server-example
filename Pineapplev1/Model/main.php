<?php

/*
 * 游戏入口文件
 * task work 都需要使用的函数写这里
 */

class ModelMain{
	/**
	 * socket包
	 * @var ReadPackageExt 
	 */
	public $readPackage;
	
	public $debug = array();//调试信息
	
	/**
	 * swoole服务
	 * @var swoole_server
	 */
	public $swoole;
	
	public function __construct(){
		$this->readPackage = new GSReadPackage();
		oo::initCfg();
	}
	
	public function workstart(){
		oo::main()->logs('workstart', 'sys');
		if($this->swoole->taskworker){
			oo::task()->workstart();
		}else{
			oo::work()->workstart();
		}
		$this->sysInfo(time());
		$this->swoole->tick(30*60*1000, function(){
			oo::main()->sysInfo();
		});//定时记录系统内存
	}
	
	/**
	* 用户请求的入口
	*/
	public function init($fd){
		$cmd = $this->readPackage->GetCmdType();
		$method = 'tcp_' . $cmd;
		$this->adminLog('main.init>>'.$method);
		if(method_exists(oo::dotcp(), $method)){
			oo::dotcp()->fd = $fd;
			oo::dotcp()->$method();
		}
	}
	
	public function onPipeMessage($from_worker_id, $message){
		$aData = json_decode($message , true);
		if(count($aData) != 3){
			return;
		}
		oo::task()->init($aData);
	}
	
	public function GetMonitorInfo(){
		$serverInfo = array();
		$serverInfo['status'] = $this->swoole->stats();//系统信息
		global $crontab_work_table,$mid_table,$fd_table,$game_table,$id_atomic;
		foreach($crontab_work_table as $workId=>$trow){
			$serverInfo['workinfo'][$workId] = $trow;
		}
		$serverInfo['midCt'] = count($mid_table);
		$serverInfo['fdCt'] = count($fd_table);
		$serverInfo['tableCt'] = count($game_table);
		$serverInfo['tableAtomic'] = $id_atomic->get();
		return $serverInfo;
	}
	/**
	* 统计task进程占用内存
	*/
	public function sysInfo($beginTime=0){
		global $crontab_work_table;
		$worker_id  = $this->swoole->worker_id;
		$useMem = memory_get_usage(1) / 1024 / 1024;
		$aSet = array( 'use_mem' => $useMem, 'lastTime'=> time());
		if($beginTime){
			$aSet['beginTime'] = $beginTime;
		}
		$crontab_work_table->set($worker_id, $aSet);
		oo::main()->logs($useMem, 'mem' , 1);
	}
	
	/**
	* 输出调试信息
	*/
	public function adminLog($str=''){
		echo date('Y-m-d H:i:s').' '.$this->swoole->worker_id.' >> '. $str .PHP_EOL;
	}
	
	/**
	* 删除缓存
	*/
	private $initCt = 0;
	public function destoryCache(){
		if($this->initCt++ > 100){
			$useMem = memory_get_usage(1);
			gc_collect_cycles();//主动调用垃圾回收
			$this->initCt = 0;
			oo::main()->logs(array($useMem, memory_get_usage(1) - $useMem), 'clearMem');
		}
	}
	
	public function logs($aInfo, $file='log', $texas = false){
		if(is_array($aInfo)){
			$aInfo = json_encode((array)$aInfo, JSON_UNESCAPED_UNICODE);
		}
		$msg = date('Y-m-d H:i:s').' '.$this->swoole->worker_id. ' '.$aInfo;
		try{
			if($texas){
				fun::logs($file, $msg);
				//return oo::logs()->debug($msg ,  'pineapple/'.$file);
			}
			oo::transit()->logs('pineapple/'.$file , $msg);
		}catch (Throwable $ex){
			
		}
	}
	
	public function memSave($key, $suseMem){
		global $mem_table;
		$useMem = memory_get_usage() / 1024 - $suseMem;
		$mem_table->incr($key, 'use_mem', $useMem);
		$mem_table->incr($key, 'ct', 1);
		$aInfo = $mem_table->get($key);
		if($useMem>$aInfo['max_mem']){
			$mem_table->set($key, array('max_mem'=>$useMem));
		}
	}
}

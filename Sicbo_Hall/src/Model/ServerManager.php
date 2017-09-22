<?php
namespace Model;

use Logic\TcpHandler;
use Core\Task\TaskManager;
use Logic\ExtTaskHandler;
use Core\Task\Task;
use Core\Task\Timer;
use Core\Task\TimerHandler;
use Logic\RoomTaskHandler;

class ServerManager{
	
	private static $_instance = null;
	
	private function __construct(){}
	
	public static function getInstance(){
		if (is_null(self::$_instance)){
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	
	
	public $swoole;
	
	
	public $transit;
	
	
	public $mongo;

	
	public $member;
	
	
	public $mserver;
	
	
	public $timerHandler;
	
	/**
	 * Tcp请求处理器
	 * @var unknown
	 */
	private $_tcpHandler;
	
	
	public function init(\swoole_server $swoole){
		
		$this->swoole = $swoole;
		$this->timerHandler = new TimerHandler();
		$this->_tcpHandler = new TcpHandler();
		
		if (false == Config::load()){
			Swoole_Log("[Error]: load config error\n");
			return ;
		}
		
		$this->transit = new \Transit(TSWOOLE_SID, Config::$service['transit']);
		$this->mongo = new \MongoHelper(Config::$service['mongo']);
		$this->member = new \Member(new \muredis(Config::$service['giredis']), new \mucache(Config::$service['giMem']));
		$this->mserver = new \MServer(Config::$service['mserver'][0], Config::$service['mserver'][1]);
		
		TaskManager::getInstance()->init(new RoomTaskHandler(), new ExtTaskHandler());
		
		if (true == $this->swoole->taskworker){
			$this->swoole->tick(500, array($this->timerHandler, 'trigger'));
			DataLoader::load();
		}
	}
	
	
	public function stop(){
		if (true == $this->swoole->taskworker){
			DataLoader::save();
		}
	}
	
	public function getTaskWorkerId(){
		if (!$this->swoole->taskworker){
			return null;
		}
		return $this->swoole->worker_id - $this->swoole->setting['worker_num'];
	}
	
	/**
	 * 执行一个TCP请求
	 */
	public function handleTcpRequest($fd, $fromId, \GSReadPackage $package){
		$cmd = $package->GetCmdType();
		$method = 'tcp_'.$cmd;
		if (!method_exists($this->_tcpHandler, $method)){
			throw new \Exception("manager can not find tcp method, method:" . $method);
		}
		
		$this->_tcpHandler->fd = $fd;
		$this->_tcpHandler->fromId = $fromId;
		$this->_tcpHandler->readPackage = $package;
		call_user_func(array($this->_tcpHandler, $method));
	}
	
	
	/**
	 * 投递给业务task进程
	 * @param Task $task
	 */
	public function businessTask($businessId, $taskName, $taskData){
		$task = new Task(Task::TYPE_BUSINESS, $taskName, $taskData);
		
		if (true == $this->swoole->taskworker){
			TaskManager::getInstance()->handleTask($task);
		}else {
			$taskId = RoomPool::getRoomTaskWorkerId($businessId);
			$this->swoole->task($task, $taskId);
		}
	}
	
	
	public function waitBusinessTask($businessId, $taskName, $taskData){
		if (true == $this->swoole->taskworker){
			return false;
		}
		$task = new Task(Task::TYPE_BUSINESS, $taskName, $taskData);
		$taskId = RoomPool::getRoomTaskWorkerId($businessId);
		
		$res = $this->swoole->taskwait($task, 0.1, $taskId);
		return $res;
	}
	
	public function waitTask($taskId, $taskName, $taskData){
		if (true == $this->swoole->taskworker){
			return false;
		}
		
		$task = new Task(Task::TYPE_BUSINESS, $taskName, $taskData);
		$res = $this->swoole->taskwait($task, 0.1, $taskId);
		return $res;
	}
	
	/**
	 * 投递给业务进程定时器
	 * @param Task $task
	 * @param Timer $timer
	 */
	public function timerBusinessTask($businessId, $taskName, $taskData, $interval, $count){
		$task = new Task(Task::TYPE_BUSINESS, $taskName, $taskData);
		$timer = new Timer(time()+$interval, $count, $interval);
		$timerId = $this->timerHandler->addTask($task, $timer);
		return $timerId;
	}
	
	public function delBusinessTask($taskId){
		$this->timerHandler->delTask($taskId);
	}
	
	
	public function logs($file, $msg, $fsize=1){
		$msg = date('Y-m-d H:i:s').' '.$this->swoole->worker_id. ' '.$msg;
		echo $msg . "\n";
		$this->transit->logs('sicbo/'.$file , $msg, $fsize);
	}
	
	
}
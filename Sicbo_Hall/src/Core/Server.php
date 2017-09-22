<?php
namespace Core;

class Server {
	
	public $swoole;
	
	protected $_config;
	
	protected $_behavior;
	
	public function __construct(array $config, Behavior $behavior){
		$this->_config = $config;
		$this->_behavior = $behavior;
		$this->swoole = new \swoole_server($this->_config['host'], $this->_config['port'], SWOOLE_PROCESS, $this->_config['socketType']);
	}
	
	public function onStart(\swoole_server $serv){
		$this->_behavior->onStart($serv);
	}
	
	public function onShutdown(\swoole_server $serv){
		$this->_behavior->onShutdown($serv);
	}
	
	
	public function onManagerStart(\swoole_server $serv){
		$this->_behavior->onManagerStart($serv);
	}
	
	public function onManagerStop(\swoole_server $serv){
		$this->_behavior->onManagerStop($serv);
	}
	
	
	public function onWorkerStart(\swoole_server $server, int $worker_id){
		$this->_behavior->onWorkerStart($server, $worker_id);
	}
	
	public function onWorkerStop(\swoole_server $server, int $worker_id){
		$this->_behavior->onWorkerStop($server, $worker_id);
	}
	
	public function onWorkerError(\swoole_server $serv, int $worker_id, int $worker_pid, int $exit_code){
		$this->_behavior->onWorkerError($serv, $worker_id, $worker_pid, $exit_code);
	}
	
	
	public function onConnect(\swoole_server $server, int $fd, int $from_id){
		$this->_behavior->onConnect($server, $fd, $from_id);
	}
	
	public function onReceive(\swoole_server $server, int $fd, int $from_id, string $data){
		$this->_behavior->onReceive($server, $fd, $from_id, $data);
	}
	
	public function onPacket(\swoole_server $server, string $data, array $client_info){
		$this->_behavior->onPacket($server, $data, $client_info);
	}
	
	public function onClose(\swoole_server $server, int $fd, int $from_id){
		$this->_behavior->onClose($server, $fd, $from_id);
	}
	
	
	public function onTask(\swoole_server $serv, int $task_id, int $from_id, string $data){
		$this->_behavior->onTask($serv, $task_id, $from_id, $data);
	}
	
	public function onFinish(\swoole_server $serv, int $task_id, string $data){
		$this->_behavior->onFinish($serv, $task_id, $data);
	}
	
	
	public function onPipeMessage(\swoole_server $server, int $from_worker_id, string $message){
		$this->_behavior->onPipeMessage($server, $from_worker_id, $message);
	}
	
	
	final public function start($clearMsg = true){
		if (true == $clearMsg && isset($this->_config['setting']['message_queue_key'])) {
			$messagekey = sprintf("0x%08x", intval($this->_config['setting']['message_queue_key']) + 2);
			system('ipcrm -Q ' . $messagekey);
		}
		
		$this->swoole->set($this->_config['setting']);
	
		$this->swoole->on('start', array($this->_behavior, 'onStart'));
		$this->swoole->on('shutdown', array($this->_behavior, 'onShutdown'));
		$this->swoole->on('managerStart', array($this->_behavior, 'onManagerStart'));
		$this->swoole->on('managerStop', array($this->_behavior, 'onManagerStop'));
		$this->swoole->on('workerStart', array($this->_behavior, 'onWorkerStart'));
		$this->swoole->on('workerStop', array($this->_behavior, 'onWorkerStop'));
		$this->swoole->on('workerError', array($this->_behavior, 'onWorkerError'));
		$this->swoole->on('connect', array($this->_behavior, 'onConnect'));
		$this->swoole->on('receive', array($this->_behavior, 'onReceive'));
		$this->swoole->on('packet', array($this->_behavior, 'onPacket'));
		$this->swoole->on('close', array($this->_behavior, 'onClose'));
		$this->swoole->on('task', array($this->_behavior, 'onTask'));
		$this->swoole->on('finish', array($this->_behavior, 'onFinish'));
		$this->swoole->on('pipeMessage', array($this->_behavior, 'onPipeMessage'));
		
		$this->swoole->start();
	}
	
}

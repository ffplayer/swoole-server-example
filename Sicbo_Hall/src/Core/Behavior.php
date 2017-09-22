<?php
namespace Core;

abstract class Behavior{
	
	public function onStart(\swoole_server $server){ }
	
	public function onShutdown(\swoole_server $server){ }
	
	
	public function onManagerStart(\swoole_server $serv){ }
	
	public function onManagerStop(\swoole_server $serv){ }
	
	
	public function onWorkerStart(\swoole_server $server, int $worker_id){ }
	
	public function onWorkerStop(\swoole_server $server, int $worker_id){ }
	
	public function onWorkerError(\swoole_server $serv, int $worker_id, int $worker_pid, int $exit_code){ }
	
	
	public function onConnect(\swoole_server $server, int $fd, int $from_id){ }
	
	abstract public function onReceive(\swoole_server $server, int $fd, int $from_id, string $data);
	
	public function onPacket(\swoole_server $server, string $data, array $client_info){ }
	
	public function onClose(\swoole_server $server, int $fd, int $from_id){ }
	
	
	public function onTask(\swoole_server $serv, int $task_id, int $from_id, $data){ }
	
	public function onFinish(\swoole_server $serv, int $task_id, string $data){ }
	
	
	public function onPipeMessage(\swoole_server $server, int $from_worker_id, string $message){ }
}
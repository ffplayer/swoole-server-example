<?php
namespace Core\Task;

abstract class TaskHandler{
	public function handle(Task $task){
		$this->before($task->data);
		
		$method = $task->taskName . "Action";
		if (!method_exists($this, $method)){
			throw new \Exception('can not find task method, method:'.$method);
		}
		call_user_func(array($this, $method), $task->data);
	}
	
	protected function before(array $params) {
		return ;
	}
}
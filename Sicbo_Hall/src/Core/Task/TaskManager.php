<?php
namespace Core\Task;

class TaskManager{
	private static $_instance = null;
	private function __construct(){}
	
	public static function getInstance(){
		if (is_null(self::$_instance)) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	
	
	private $_businessHandler;
	
	
	private $_extHandler;
	
	
	public function handleTask(Task $task){
		$handler = $this->_getTaskHandler($task->type);
		$handler->handle($task);
	}
	
	private function _getTaskHandler($type){
		if ($type == Task::TYPE_BUSINESS){
			return $this->_businessHandler;
		}else{
			return $this->_extHandler;
		}
	}
	
	public function init(TaskHandler $businessHandler, TaskHandler $extHandler){
		$this->_businessHandler = $businessHandler;
		$this->_extHandler = $extHandler;
	}
}
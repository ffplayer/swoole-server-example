<?php
namespace Core\Task;

class TimerHandler{
	
	private $_taskList = array();
	
	
	public function __construct(){}
	
	
	public function trigger(){
		
		$currentTime = time();
		
		foreach ($this->_taskList as $sid=>$taskInfo){
			if ($currentTime < $taskInfo['timer']->nextTime){
				continue;
			}
			
			if ($taskInfo['timer']->count == 0){
				unset($this->_taskList[$sid]);
				continue;
			}
			
			$taskInfo['timer']->update();
			
			TaskManager::getInstance()->handleTask($taskInfo['task']);
		}
	}

	
	public function addTask(Task $task, Timer $timer){
		$this->_taskList[$timer->sid] = array('task'=>$task, 'timer'=>$timer);
		return $timer->sid;
	}

	
	public function delTask($sid){
		if (!isset($this->_taskList[$sid])){
			return false;
		}
		
		unset($this->_taskList[$sid]);
		return true;
	}
	
	public function getStorageData(){
		return array('taskList'=>$this->_taskList);
	}
	
	public function reinit($data){
		if (isset($data['taskList'])){
			$this->_taskList = $data['taskList'];
		}
	}
}
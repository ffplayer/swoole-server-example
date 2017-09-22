<?php
namespace Core\Timer;

use Model\ServerManager;

class TaskEvent implements IEvent{
	
	private $_taskName;
	
	private $_data;
	
	public function __construct($taskName, $data){
		$this->_taskName = $taskName;
		$this->_data = $data;
	}
	
	public function run(){
		ServerManager::getInstance()->task($this->_taskName, $this->_data);
	}
}
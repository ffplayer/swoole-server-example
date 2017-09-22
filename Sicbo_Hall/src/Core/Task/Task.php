<?php
namespace Core\Task;

class Task{
	
	const TYPE_BUSINESS = 1;
	const TYPE_EXT = 2;
	
	//类型
	public $type;
	
	//任务名称
	public $taskName;
	
	//任务数据
	public $data;
	
	
	public function __construct($type, $taskName, array $data){
		$this->type = $type;
		$this->taskName = $taskName;
		$this->data = $data;
	}
}
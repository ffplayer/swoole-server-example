<?php
namespace Core\Task;

class Timer{
	
	public $sid;
	
	//下次执行时间
	public $nextTime;
	
	
	//剩余执行次数
	public $count;
	
	
	//重复执行时间间隔
	public $interval;
	
	
	public function __construct($startTime, $count, $interval){
		$this->sid = $this->_createSid();
		$this->nextTime = $startTime;
		$this->count = $count;
		$this->interval = $interval;
	}
	
	public function update(){
		$this->count--;
		$this->nextTime = $this->nextTime + $this->interval;
	}
	
	private function _createSid(){
		return md5(uniqid("", true));
	}
}
<?php
namespace Core\Timer;

class Timer{
	
	public function __construct(){}
	
	public function trigger(){
		global $timer_table;
		$currentTime = time();
		foreach ($timer_table as $sid=>$row){
			if ($currentTime < $row['stime']+$row['interval']){
				continue;
			}
			
			if ($row['count'] == 0){
				$timer_table->del($sid);
				continue;
			}
			
			$timer_table->incr($sid, 'stime', $row['interval']);
			if ($row['count'] > 0){
				$timer_table->decr($sid, 'count');
			}
			
			$event = $this->_unserialize($row['event']);
			
			call_user_func(array($event, 'run'));
		}
	}

	
	/**
	 * 添加一个定时事件
	 * @param unknown $sid
	 * @param int $interval
	 * @param int $count
	 * @param callable $callback
	 * @param array $params
	 * 
	 * @return sid
	 */
	public function addTimer(int $interval, int $count, IEvent $event){
		global $timer_table;
		$currentTime = time();
		$sid = $this->createEventId();
		$timer_table->set($sid, array('stime'=>$currentTime, 'interval'=>$interval, 'count'=>$count, 'event'=>$this->_serialize($event)));
		return $sid;
	}
	
	

	/**
	 * 取消已经添加的定时事件， 取消不存在事件会失败
	 *
	 * @return bool, 成功返回true， 失败返回false
	 */
	public function del($sid){
		global $timer_table;
		if (!$timer_table->exist($sid)){
			return false;
		}
		return $timer_table->del($sid);
	}
	
	
	private function _serialize($data){
		return serialize($data);
	}
	
	
	private function _unserialize($data){
		return unserialize($data);
	}
	
	
	private function createEventId(){
		return md5(uniqid("", true));
	}
}
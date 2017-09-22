<?php
namespace Model;

class RoomPool{
	
	private static $_pool = array();
	
	/**
	 * @param $rid
	 * 
	 * @return Room
	 */
	public static function getRoom($rid){
		if (!isset(self::$_pool[$rid])){
			return null;
		}
		return self::$_pool[$rid];
	}
	
	
	public static function getStorageData(){
		return array('pool'=>self::$_pool);
	}
	
	
	public static function load(array $storageData){
		foreach (Config::$game['aRoomCfg'] as $rid=>$roomCfg){
			if ($roomCfg['serverId'] != Config::$service['svid']){
				continue;
			}
			
			$currentWorkerid = ServerManager::getInstance()->getTaskWorkerId();
			$roomWorkerId = self::getRoomTaskWorkerId($rid);
			if ($currentWorkerid !== $roomWorkerId){
				continue;
			}
			
			if (isset($storageData['pool'][$rid])){
				self::$_pool[$rid] = $storageData['pool'][$rid];
			}else {
				self::$_pool[$rid] = new Room($rid);
				ServerManager::getInstance()->businessTask($rid, 'gameReady', array('rid'=>$rid));
			}
			
			if (self::$_pool[$rid]->lottery->money == 0){
				self::$_pool[$rid]->lottery->updateLotteryMoneyFromMongo();
			}
		}
	}
	
	
	public static function getRoomTaskWorkerId($rid){
		global $room_tworker_table, $room_task_index, $room_task_dispatch_lock;
		
		$room_task_dispatch_lock->lock();
		$workerId = ServerManager::getInstance()->swoole->worker_id;
		$taskId = 0;
		$info = $room_tworker_table->get($rid);
		if (false == $info){
			$taskWorkerNum = ServerManager::getInstance()->swoole->setting['task_worker_num'];
			$taskIndex = $room_task_index->add(1);
			
			$taskId = $taskIndex % $taskWorkerNum;
			$room_tworker_table->set($rid, array('workerid'=>$taskId));
			
		}else{
			$taskId = $info['workerid'];
		}
		
		$room_task_dispatch_lock->unlock();
		
		return $taskId;
	}
	
}

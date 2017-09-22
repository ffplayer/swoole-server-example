<?php
namespace Model;

class TablePool{
	
	const MAX_TABLE_COUNT = 10;
	
	const TABLE_CAPCITY = 500;
	
	//{rid:{tid: table, ...}, ...}
	private static $_pool = array();
	
	//{rid:triggerTime, ...}
	private static $_combineList = array();
	
	/**
	 * 通过房间id和桌子id获取桌子
	 * @param unknown $rid		//房间id
	 * @param unknown $tid		//桌子id
	 * 
	 * @return Table
	 */
	public static function getTable($rid, $tid){
		if (!isset(self::$_pool[$rid][$tid])){
			return null;
		}
		return self::$_pool[$rid][$tid];
	}
	
	
	/**
	 * 获取房间的桌子列表
	 * @param unknown $rid
	 * 
	 * @return array(Table, ...)
	 */
	public static function getTableList($rid){
		if (!isset(self::$_pool[$rid])){
			return array();
		}
		return self::$_pool[$rid];
	}
	
	
	/**
	 * 获取用户所在桌子
	 * @param unknown $rid		//房间id
	 * @param unknown $mid		//用户id
	 * 
	 * @return Table
	 */
	public static function getUserTable($rid, $mid){
		if (!isset(self::$_pool[$rid])){
			return null;
		}
		foreach (self::$_pool[$rid] as $tid=>$table){
			if (isset($table->userList[$mid])){
				return $table;
			}
		}
		return null;
	}
	
	
	/**
	 * 获取可以添加用户的桌子（桌子不足时会动态添加桌子）
	 * 
	 * @param unknown $rid
	 * 
	 * @return Table
	 */
	public static function getAvailTable($rid){
		if (!isset(self::$_pool[$rid])){
			return null;
		}
		
		foreach (self::$_pool[$rid] as $tid=>$table){
			$count = $table->getUserCount();
			if ($count < Config::$game['maxPlayer']){
				return $table;
			}
		}
		
		$tid = self::_allocationNewTable($rid);
		if (is_null($tid)){
			return null;
		}
		
		return self::$_pool[$rid][$tid];
	}
	
	/**
	 * 判断用户是否在该进程任何一个桌子内
	 * @param unknown $mid
	 * @return boolean
	 */
	public static function checkUserInAnyTable($mid){
		foreach (self::$_pool as $tableList){
			foreach ($tableList as $table){
				if (isset($table->userList[$mid])){
					return true;
				}
			}
		}
		return false;
	}
	
	public static function updateCombineTable($rid){
		$currentTime = time();
		if (false == self::_checkRoomNeedCombineTable($rid)){
			unset(self::$_combineList[$rid]);
			return ;
		}
		
		if (!isset(self::$_combineList[$rid])){
			self::$_combineList[$rid] = $currentTime;
		}
	}
	
	
	public static function combineTable($rid){
		$currentTime = time();
		if (!isset(self::$_combineList[$rid])){
			return false;
		}
		
		if ($currentTime - self::$_combineList[$rid] < Config::$game['keepTime'] * 60){
			return false;
		}
		
		$tableList = self::_getMinUserCountTables($rid);
		$minTable1 = self::getTable($rid, $tableList[0]['tid']);
		$minTable2 = self::getTable($rid, $tableList[1]['tid']);
		
		TableMessager::sendSystemMsg($minTable2, GameConst::SYSTEM_SIGN_TABLE_COMBINE_BEGIN);
		
		$minTable1->merget($minTable2);
		
		$userList = $minTable2->userList;
		
		unset(self::$_combineList[$rid]);
		unset(self::$_pool[$rid][$minTable2->tid]);
		
		foreach ($userList as $mid=>$minfo){
			UserMessager::sendSystemMsg($mid, GameConst::SYSTEM_SIGN_TABLE_COMBINE_END);
			UserMessager::sendRoomData($mid, $rid);
			UserMessager::sendSeatListMessage($mid, $rid);
		}
		
		return true;
	}
	
	public static function deleteTable($rid, $tid){
		if (!isset(self::$_pool[$rid][$tid])){
			return false;
		}
		
		if (count(self::$_pool[$rid]) <= 1){
			return false;
		}
		
		$table = self::$_pool[$rid][$tid];
		if (!empty($table->userList)){
			return false;
		}
		
		unset(self::$_pool[$rid][$tid]);
		if (false == self::_checkRoomNeedCombineTable($rid)){
			unset(self::$_combineList[$rid]);
		}
		
		return true;
	}
	
	private static function _checkRoomNeedCombineTable($rid){
		$tableList = self::_getMinUserCountTables($rid);
		if (empty($tableList)){
			return false;
		}
		
		if ($tableList[0]['count'] + $tableList[1]['count'] > Config::$game['pLessThan']){
			return false;
		}
		
		return true;
	}
	
	private static function _getMinUserCountTables($rid){
		$list = array();
		if (!isset(self::$_pool[$rid])){
			return array();
		}
		
		foreach (self::$_pool[$rid] as $tid=>$table){
			$count = $table->getUserCount();
			$list[$tid] = array('tid'=>$tid, 'count'=>$count);
		}
		
		if (count($list) < 2){
			return array();
		}
		
		usort($list, function ($a, $b){
			if ($a['count'] > $b['count']){
				return 1;
			}
			if ($a['count'] < $b['count']){
				return -1;
			}
			return 0;
		});
		
		$list = array_slice($list, 0, 2);
		
		usort($list, function ($a, $b){
			if ($a['tid'] > $b['tid']){
				return 1;
			}
			if ($a['tid'] < $b['tid']){
				return -1;
			}
			return 0;
		});
		
		return $list;
	}
	
	
	public static function getStorageData(){
		return array('pool'=>self::$_pool, 'combineList'=>self::$_combineList);
	}
	
	
	public static function load(array $storageData){
		foreach (Config::$game['aRoomCfg'] as $rid=>$roomCfg){
			$currentTaskid = ServerManager::getInstance()->getTaskWorkerId();
			$roomTaskId = RoomPool::getRoomTaskWorkerId($rid);
			
			if ($currentTaskid != $roomTaskId){
				continue;
			}
			
			if (isset($storageData['pool'][$rid])){
				self::$_pool[$rid] = $storageData['pool'][$rid];
				continue;
			}
			self::_allocationNewTable($rid);
		}
		
		if (isset($storageData['combineList'])){
			self::$_combineList = $storageData['combineList'];
		}
	}
	
	
	//为房间分配新桌子
	private static function _allocationNewTable($rid){
		$newTid = 1;
		for (; $newTid<=self::MAX_TABLE_COUNT; $newTid++){
			if (isset(self::$_pool[$rid][$newTid])){
				continue;
			}
			break;
		}
		self::$_pool[$rid][$newTid] = new Table($rid, $newTid);
		return $newTid;
	}
}

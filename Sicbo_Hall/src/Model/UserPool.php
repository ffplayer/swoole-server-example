<?php
namespace Model;

class UserPool{
	
	const MAX_OFFLINE_TIME = 40;
	
	private static $_pool = array();
	
	//{rid:{mid:0, ...}, ...}
	private static $_offlineList = array();
	
	/**
	 * 
	 * @param unknown $mid
	 * 
	 * @return User
	 */
	public static function getUser($mid){
		if (!isset(self::$_pool[$mid])){
			return null;
		}
		return self::$_pool[$mid];
	}
	
	
	public static function getUserList(){
		return self::$_pool;
	}
	
	
	public static function getUserCount(){
		return count(self::$_pool);
	}
	
	public static function getStorageData(){
		return array('pool'=>self::$_pool, 'offlineList'=>self::$_offlineList);
	}
	
	
	public static function load(array $storageData){
		if (isset($storageData['pool'])){
			self::$_pool = $storageData['pool'];
		}
		if (isset($storageData['offlineList'])){
			self::$_offlineList = $storageData['offlineList'];
		}
	}
	
	public static function loadDefault($mid, $unid){
		$user = new User($mid, $unid);
		if (false == $user->loadPlatformInfo()){
			return false;
		}
		self::$_pool[$mid] = $user;
		
		return true;
	}
	
	
	
	//---------------用户退出逻辑 begin------------------
	public static function clearOfflineRecord($rid, $mid){
		if (isset(self::$_offlineList[$rid][$mid])){
			unset(self::$_offlineList[$rid][$mid]);
		}
	}
	
	public static function userExit(Table $table, $mid){
		if (!isset($table->userList[$mid])){
			return ;
		}
		$user = UserPool::getUser($mid);
		
		$table->userStandUp($mid);
		
		if (!isset(self::$_offlineList[$table->rid][$mid])){
			$user->clearGI();
		}
		
		$userBetList = $table->record->getUserBetList($mid);
		if (!empty($userBetList)){
			self::$_offlineList[$table->rid][$mid] = array('time'=>time(), 'type'=>1);
		}else {
			$table->delUser($mid);
			if (empty($table->userList)){
				TablePool::deleteTable($table->rid, $table->tid);
			}
			//清理user_pool 的 user
			self::_clearUserData($table->rid, $mid);
		}
		return ;
	}
	
	public static function userOffline(Table $table, $mid){
		if (!isset($table->userList[$mid])){
			return ;
		}
		
		$user = UserPool::getUser($mid);
		$table->userStandUp($mid);
		if (!isset(self::$_offlineList[$table->rid][$mid])) {
			self::$_offlineList[$table->rid][$mid] = array('time'=>time(), 'type'=>2);
		}
	}
	
	public static function clearOfflineUser($rid){
		if (!isset(self::$_offlineList[$rid])){
			return ;
		}
		$ctime = time();
		foreach (self::$_offlineList[$rid] as $mid=>$minfo){
			if ($ctime - $minfo['time'] < self::MAX_OFFLINE_TIME){
				continue;
			}
			$table = TablePool::getUserTable($rid, $mid);
			$user = UserPool::getUser($mid);
			
			$table->delUser($mid);
			if (empty($table->userList)){
				TablePool::deleteTable($rid, $table->tid);
			}
			
			if (!isset($minfo['type']) || $minfo['type'] == 2){
				$user->clearGI();
			}
			
			//清理user_pool 的 user
			self::_clearUserData($rid, $mid);
			
			unset(self::$_offlineList[$rid][$mid]);
		}
		return ;
	}
	
	public static function checkUserOffline($rid, $mid){
		if (!isset(self::$_offlineList[$rid][$mid])){
			return false;
		}
		return true;
	}
	
	//强行删除一个用户(脚本中使用)
	public static function tryDelLeakUser($mid){
		if (!TablePool::checkUserInAnyTable($mid)){
			unset(self::$_pool[$mid]);
		}
	}
	
	private static function _clearUserData($rid, $mid){
		//当前房间为用户最后一次登录的房间，清理用连接信息
		$conInfo = GlobalData::getConnectInfoWithMid($mid);
		if (!is_null($conInfo) && isset($conInfo['rid'])){
			GlobalData::delConnectInfoWithMid($mid);
		}
		
		//用户不在该进程内任何一个桌子内时， 删除用户数据
		if (!TablePool::checkUserInAnyTable($mid)){
			unset(self::$_pool[$mid]);
		}
	}
	
	
	//---------------用户退出逻辑  end------------------
}


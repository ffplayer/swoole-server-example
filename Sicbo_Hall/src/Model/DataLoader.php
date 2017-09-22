<?php

namespace Model;

use Model\RoomPool;
use Model\TablePool;
use Model\UserPool;

class DataLoader{
	
	public static function load(){
		$data = self::_readStorage();
		if (empty($data)){
			$data['roomPool'] = array();
			$data['tablePool'] = array();
			$data['userPool'] = array();
			$data['timerData'] = array();
		}
		
		ServerManager::getInstance()->timerHandler->reinit($data['timerData']);
		RoomPool::load($data['roomPool']);
		TablePool::load($data['tablePool']);
		UserPool::load($data['userPool']);
	}
	
	
	
	public static function save(){
		$roomPool = RoomPool::getStorageData();
		$tablePool = TablePool::getStorageData();
		$userPool = UserPool::getStorageData();
		$timerData = ServerManager::getInstance()->timerHandler->getStorageData();
		
		$data = array(
				'roomPool'=>$roomPool,
				'tablePool'=>$tablePool,
				'userPool'=>$userPool,
				'timerData' =>$timerData
		);
		
		return self::_saveStorage($data);
	}
	
	
	/**
	 * @param string $data
	 * 
	 * @return bool
	 */
	private static function _saveStorage(array $data){
		$str = serialize($data);
		$ret = @file_put_contents(self::_getStorageFilePath(), $str);
		if (false == $ret){
			return false;
		}
		return true;
	}
	
	
	/**
	 * @return array
	 */
	private static function _readStorage(){
		if (!file_exists(self::_getStorageFilePath())){
			return array();
		}
		$str = @file_get_contents(self::_getStorageFilePath());
		if (is_null($str)){
			return array();
		}
		$data = unserialize($str);
		if (!is_array($data)){
			return array();
		}
		return $data;
	}
	
	private static function _getStorageFilePath(){
		$workerId = ServerManager::getInstance()->swoole->worker_id;
		return SERVER_ROOT . '/data/sys_' . $workerId . '.data' ;
	}
}
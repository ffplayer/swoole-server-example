<?php
namespace Logic;

use Model\Config;
use Model\ServerManager;
use Model\GameConst;
use Model\GlobalData;
use Model\UserMessager;
class AdminWorker{
	public static function handle($fd, $cmd, $pack){
		$msg = "";
		switch ($cmd){
			case 1://只重启task进程
				$msg = self::_reloadTaskWorker();
				break;
			case 2://重启所有进程
				$msg = self::_reloadAllWorker();
				break;
			case 3:
				$msg = self::_getServerInfo();
				break;
			case 4: //重新加载配置
			case 5: //重新加载配置
				$msg = self::_reloadConfig();
				break;
			case 6: //停服命令
				$msg = self::_stopServer();
				break;
			case 8:
				$msg = self::_checkUserInServer($pack);
				break;
			case 31: // 修复房间在线人数mongo数据
				$msg = self::_fixRoomMongoInfo();
				break;
			case 32:	//清理退出用户数据残留
				$msg = self::_clearLeakUserData();
				break;
			default:
				$msg = '无法识别的命令, cmd:'.$cmd;
		}
		UserMessager::sendAdmin($fd, $msg);
	}
	
	
	private static function _reloadTaskWorker(){
		ServerManager::getInstance()->swoole->reload(true);
		ServerManager::getInstance()->logs('admin', 'admin reload task.');
		return 'ok';
	}
	
	
	private static function _reloadAllWorker(){
		ServerManager::getInstance()->swoole->reload(false);
		ServerManager::getInstance()->logs('admin', 'admin reload worker and task.');
		return 'ok';
	}
	
	private static function _getServerInfo(){
		global $fd_table, $mid_table;
		$fdTableCount = count($fd_table);
		$midTableCount = count($mid_table);
		
		$swooleStatus = ServerManager::getInstance()->swoole->stats();
		
		$roomInfoList = array();
		foreach (Config::$game['aRoomCfg'] as $rid=>$cfg){
			$roomInfoList[$rid] = ServerManager::getInstance()->waitBusinessTask($rid, 'adminGetRoomSummary', array('rid'=>$rid));;
		}
		
		$processInfoList = array();
		$taskNum = ServerManager::getInstance()->swoole->setting['task_worker_num'];
		for($i=0; $i<$taskNum; $i++){
			$processInfoList['task_'.$i] = ServerManager::getInstance()->waitTask($i, 'adminGetProcess', array());;
		}
		
		$info = array(
				'stats'=>$swooleStatus, 
				'swooleTable'=>array(
						'fd'=>$fdTableCount,
						'mid'=>$midTableCount
				),
				'processInfo'=>$processInfoList,
				'roomInfo'=>$roomInfoList
		);
		return json_encode($info);
	}
	
	private static function _reloadConfig(){
		ServerManager::getInstance()->swoole->reload(false);
		ServerManager::getInstance()->logs('admin', 'admin reload config (worker and task also reload).');
		return 'ok';
	}
	
	private static function _stopServer(){
		GlobalData::setServerStatus(GameConst::SERVER_ST_STOP);
		
		foreach (Config::$game['aRoomCfg'] as $rid=>$cfg){
			ServerManager::getInstance()->businessTask($rid, 'adminStopGame', array('rid'=>$rid));
		}
		ServerManager::getInstance()->logs('admin', 'stop server.');
		return 'ok';
	}
	
	private static function _checkUserInServer(\GSReadPackage $pack){
		$result = array();
		$mids = $pack->ReadString();
		$midList = explode(',', $mids);
		foreach ($midList as $mid){
			if (!GlobalData::existConnectInfoWithMid($mid)){
				$result[] = $mid;
			}
		}
		return json_encode($result);
	}
	
	private static function _fixRoomMongoInfo(){
		foreach (Config::$game['aRoomCfg'] as $rid=>$cfg){
			ServerManager::getInstance()->businessTask($rid, 'adminFixRoomMongoInfo', array('rid'=>$rid));
		}
		ServerManager::getInstance()->logs('admin', 'fix room mongo info.');
		return 'ok';
	}
	
	
	private static function _clearLeakUserData(){
		$taskNum = ServerManager::getInstance()->swoole->setting['task_worker_num'];
		for($i=0; $i<$taskNum; $i++){
			ServerManager::getInstance()->waitTask($i, 'adminClearLeakUserData', array());;
		}
		return "ok";
	}
}
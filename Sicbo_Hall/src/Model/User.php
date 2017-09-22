<?php

namespace Model;

class User{
	
	const MID_NONE = -1;
	
	const USER_TYPE_BET_SIT = 1;
	const USER_TYPE_BET_STAND = 2;
	
	const USER_STATUS_NORMAL = 0;
	const USER_STATUS_EXIT = 1;
	const USER_STATUS_OFFLINE = 2;
	
	public $mid;
	
	public $unid;
	
	public $sid;
	
	public $mstatus;		//1:表示被封号
	
	private $_name;
	
	private $_url;
	
	public $name = '';
	
	public $url = '';
	
	public $money;
	
	public $showVip;
	
	public $mvip;
	
	public $vipLv;
	
	public $realRid = 0;
	
	public function __construct($mid, $unid){
		$this->mid = $mid;
		$this->unid = $unid;
	}
	
	
	public function addMoney($money, $source, $rid){
		if ($money < 0){
			return false;
		}
		
		$ret = $this->_updateMServerMoney($money, $source, $rid);
		if (false === $ret){
			return false;
		}
		
		
		return true;
	}
	
	public function decMoney($money, $source, $rid){
		if ($money < 0){
			return false;
		}
		if ($this->money < $money){
			$this->loadMServer();
		}
		
		if ($this->money < $money){
			return false;
		}
		
		$ret = $this->_updateMServerMoney(-$money, $source, $rid);
		if (false === $ret){
			return false;
		}
		
		
		return true;
	}
	
	
	public function loadPlatformInfo(){
		$minfo = ServerManager::getInstance()->member->getMinfo($this->mid, array(0, 'mbig', 7, 8, 10));
		$msRecord = ServerManager::getInstance()->mserver->GetRecord($this->mid);
	
		if (!isset($msRecord['mmoney'])){
			return false;
		}
	
		$info = array();
		$this->name = $minfo['mnick'];
		$this->url = $minfo['micon'];
		$this->showVip = $minfo['showVip'];
		$this->mvip = $minfo['mvip'];
		$this->vipLv = $minfo['vipLv'];
		$this->money = $msRecord['mmoney'];
		return true;
	}
	
	public function loadMServer(){
		$msRecord = ServerManager::getInstance()->mserver->GetRecord($this->mid);
		if (!isset($msRecord['mmoney'])){
			return false;
		}
		$this->money = $msRecord['mmoney'];
		return true;
	}
	
	public function updateMtkey(){
		ServerManager::getInstance()->member->updateOnlineInfo($this->mid, array());
	}
	
	public function updateGI($rid){
		$onlineInfo = array(
				'tid'=>Config::$game['tid'],
				'svid'=>Config::$service['svid'],
				'ante'=>$rid,
				'mtstatus'=>0
		);
		
		$ret = ServerManager::getInstance()->member->updateOnlineInfo($this->mid, $onlineInfo);
		if (is_array($ret) && isset($ret['sid'])){
			$this->sid = $ret['sid'];
			$this->mstatus = $ret['mstatus'];
		}
		return $ret;
	}
	
	public function clearGI(){
		$onlineInfo = array(
				'tid'=>0,
				'svid'=>0,
				'ante'=>0,
				'mtstatus'=>0
		);
		
		return ServerManager::getInstance()->member->updateOnlineInfo($this->mid, $onlineInfo);
	}
	
	
	private function _updateMServerMoney($money, $source, $rid){
		$aInfo = array();
		$aInfo['mmoney'] = $money;
		$aInfo['wmode'] = $source;
		$aInfo['addmoney'] = $money;
		$aInfo['sid'] = $this->unid;
		
		
		$room = RoomPool::getRoom($rid);
		if (null != $room){
			$aInfo['tid'] = $rid;
			$aInfo['bid'] = $room->startTime;
		}
		
		$ret = ServerManager::getInstance()->mserver->update($this->mid, $aInfo);
		if (false === $ret){
			return false;
		}
		$this->money = $ret['mmoney'];
		return true;
	}
}
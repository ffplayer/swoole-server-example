<?php
namespace Logic;

use Model\ServerManager;
use Model\Config;
use Model\UserMessager;
use Model\GameConst;
use Model\GlobalData;
use Model\UserPool;

class TcpHandler{
	public $fd;
	public $fromId;
	public $readPackage;
	public $defaultRid = 0;
	
	public function __construct(){
		$this->readPackage = new \GSReadPackage();
	}
	
	//追踪
	public function tcp_0x100(){
		$mid = $this->readPackage->ReadInt();
		$rid = $this->readPackage->ReadInt();
		$api = $this->readPackage->ReadShort();		//暂时未用到
		$sid = $this->readPackage->ReadShort();		//暂时未用到
		$unid = $this->readPackage->ReadString();
		$mtkey = $this->readPackage->ReadString();
		$tracedMid = $this->readPackage->ReadInt();
		
		$status = GlobalData::getServerStatus();
		if ($status == GameConst::SERVER_ST_STOP){
			UserMessager::sendSystemMsgWithFd($this->fd, GameConst::SYSTEM_SIGN_SERVER_UPDATE);
			ServerManager::getInstance()->logs('error', "strace login failed, server stop, mid:{$mid}");
			return ;
		}
		
		//验证登录mtkey
		$ret = ServerManager::getInstance()->member->checkMtkey($mid, $mtkey);
		if (false == $ret){
			UserMessager::sendEnterRoomResultv2($this->fd, GameConst::LOGIN_RET_ERR_MTKEY, $rid);
			ServerManager::getInstance()->logs('error', "strace login failed, mtkey failed, mid:{$mid}");
			return ;
		}
		
		//验证平台登录桌子
		if ($ret['tid'] != 0 && $ret['tid'] != Config::$game['tid']){
			UserMessager::sendEnterRoomResultv2($this->fd, GameConst::LOGIN_RET_ERR_OTHER_GAME, $rid);
			ServerManager::getInstance()->logs('error', "strace login failed, wrong taxas table, mid:{$mid}, stid:{$ret['tid']}");
			return ;
		}
		
		//是否被封号
		if ($ret['mstatus'] == 1){
			UserMessager::sendEnterRoomResultv2($this->fd, GameConst::LOGIN_RET_ERR_ACCOUNT_DISABLE, $rid);
			ServerManager::getInstance()->logs('error', "strace login failed, wrong account status, mid:{$mid}");
			return ;
		}
		
		//获取被追踪者信息
		$traceInfo = GlobalData::getConnectInfoWithMid($tracedMid);
		if (!is_array($traceInfo)){
			UserMessager::sendEnterRoomResultv2($this->fd, GameConst::LOGIN_RET_ERR_ROOM_DATA, $rid);
			ServerManager::getInstance()->logs('error', "strace login failed, can not calculate rid, mid:{$mid}, traceMid:{$tracedMid}");
			return ;
		}
		$realRid = $rid?$rid : $traceInfo['rid'];
		$this->defaultRid && $rid = $this->defaultRid;
		
		ServerManager::getInstance()->businessTask($rid, 'enterRoom', array('rid'=>$rid, 'mid'=>$mid, 'unid'=>$unid, 'fd'=>$this->fd, 'realRid'=>$realRid));
	}
	
	/**
	 * 进入房间
	 */
	public function tcp_0x101(){
		$mid = $this->readPackage->ReadInt();
		$rid = $this->readPackage->ReadInt();
		$api = $this->readPackage->ReadShort();		//暂时未用到
		$sid = $this->readPackage->ReadShort();		//暂时未用到
		$unid = $this->readPackage->ReadString();
		$mtkey = $this->readPackage->ReadString();
		
		$status = GlobalData::getServerStatus();
		if ($status == GameConst::SERVER_ST_STOP){
			UserMessager::sendSystemMsgWithFd($this->fd, GameConst::SYSTEM_SIGN_SERVER_UPDATE);
			ServerManager::getInstance()->logs('error', "login failed, server stop, mid:{$mid}");
			return ;
		}
		
		//验证登录mtkey
		$ret = ServerManager::getInstance()->member->checkMtkey($mid, $mtkey);
		if (false == $ret){
			UserMessager::sendEnterRoomResultv2($this->fd, GameConst::LOGIN_RET_ERR_MTKEY, $rid);
			ServerManager::getInstance()->logs('error', "login failed, mtkey failed, mid:{$mid}");
			return ;
		}
		
		//验证平台登录桌子
		if ($ret['tid'] != 0 && $ret['tid'] != Config::$game['tid']){
			UserMessager::sendEnterRoomResultv2($this->fd, GameConst::LOGIN_RET_ERR_OTHER_GAME, $rid);
			ServerManager::getInstance()->logs('error', "login failed, wrong taxas table, mid:{$mid}, stid:{$ret['tid']}");
			return ;
		}
		
		//验证平台登录svid
		if ($ret['svid'] != 0 && $ret['svid'] != Config::$service['svid']){
			UserMessager::sendEnterRoomResultv2($this->fd, GameConst::LOGIN_RET_ERR_OTHER_SERVER, $rid);
			ServerManager::getInstance()->logs('error', "login failed, wrong svid, mid:{$mid}, svid:{$ret['svid']}");
			return ;
		}
		
		//是否被封号
		if ($ret['mstatus'] == 1){
			UserMessager::sendEnterRoomResultv2($this->fd, GameConst::LOGIN_RET_ERR_ACCOUNT_DISABLE, $rid);
			ServerManager::getInstance()->logs('error', "login failed, wrong account status, mid:{$mid}");
			return ;
		}
		

		if ($rid == 0){
			$userInfo = GlobalData::getConnectInfoWithMid($mid);
			if (!is_array($userInfo)){
				UserMessager::sendEnterRoomResultv2($this->fd, GameConst::LOGIN_RET_ERR_ROOM_DATA, $rid);
				ServerManager::getInstance()->logs('error', "login failed, can not calculate rid, mid:{$mid}");
				return ;
			}
			$rid = $userInfo['rid'];
		}
		$realRid = $rid;
		$this->defaultRid && $rid = $this->defaultRid;
		
		ServerManager::getInstance()->businessTask($rid, 'enterRoom', array('rid'=>$rid, 'mid'=>$mid, 'unid'=>$unid, 'fd'=>$this->fd, 'realRid'=>$realRid));
		
		return ;
	}
	
	/**
	 * 退出房间
	 */
	public function tcp_0x102(){
		$conInfo = GlobalData::getConnectInfoWithFd($this->fd);
		if (!is_array($conInfo)){
			ServerManager::getInstance()->logs('error', "logout failed, find connect info, fd:{$this->fd}");
			return ;
		}
		ServerManager::getInstance()->businessTask($conInfo['rid'], 'exitRoom', array('fd'=>$this->fd, 'mid'=>$conInfo['mid'], 'rid'=>$conInfo['rid']));
	}
	
	
	public function tcp_0x105(){
		$conInfo = GlobalData::getConnectInfoWithFd($this->fd);
		if (!is_array($conInfo)){
			ServerManager::getInstance()->logs('error', "fetch room failed, find connect info, fd:{$this->fd}");
			return ;
		}
		ServerManager::getInstance()->businessTask($conInfo['rid'], 'fetchRoomData', array('mid'=>$conInfo['mid'], 'rid'=>$conInfo['rid'], 'fd'=>$this->fd));
	}
	
	/**
	 * 获取房间旁观列表(php端调用)
	 */
	public function tcp_0x106(){
		$rid = $this->readPackage->ReadInt();
		
		$this->defaultRid && $rid = $this->defaultRid;
		
		$tid = $this->readPackage->ReadShort();
		$page = $this->readPackage->ReadByte();
		$pageSize = $this->readPackage->ReadShort();
// 		ServerManager::getInstance()->logs('debug', "rid:{$rid}, tid:{$tid}, page:{$page}, pageSize:{$pageSize}");
		ServerManager::getInstance()->businessTask($rid, 'getRoomStandUserList', array('fd'=>$this->fd, 'rid'=>$rid, 'tid'=>$tid, 'page'=>$page, 'pageSize'=>$pageSize));
	}
	
	/**
	 * 坐下
	 */
	public function tcp_0x110(){
		$conInfo = GlobalData::getConnectInfoWithFd($this->fd);
		if (!is_array($conInfo)){
			ServerManager::getInstance()->logs('error', "sit down failed, find connect info, fd:{$this->fd}");
			return ;
		}
		$pos = $this->readPackage->ReadByte();
		ServerManager::getInstance()->businessTask($conInfo['rid'], 'sitDown', array('mid'=>$conInfo['mid'], 'rid'=>$conInfo['rid'], 'pos'=>$pos));
	}
	
	/**
	 * 站起
	 */
	public function tcp_0x111(){
		$conInfo = GlobalData::getConnectInfoWithFd($this->fd);
		if (!is_array($conInfo)){
			ServerManager::getInstance()->logs('error', "stand up failed, find connect info, fd:{$this->fd}");
			return ;
		}
		ServerManager::getInstance()->businessTask($conInfo['rid'], 'standUp', array('mid'=>$conInfo['mid'], 'rid'=>$conInfo['rid']));
	}
	
	
	/**
	 * 下注
	 */
	public function tcp_0x201(){
		$conInfo = GlobalData::getConnectInfoWithFd($this->fd);
		if (!is_array($conInfo)){
			ServerManager::getInstance()->logs('error', "bet failed, find connect info, fd:{$this->fd}");
			return ;
		}
		
		$betPos = $this->readPackage->ReadByte();
		$amount = $this->readPackage->ReadInt64();
		
		ServerManager::getInstance()->businessTask($conInfo['rid'], 'bet', array('mid'=>$conInfo['mid'], 'rid'=>$conInfo['rid'], 'betPos'=>$betPos, 'amount'=>$amount, 'fd'=>$this->fd));
	}
	
	/**
	 * 重复上一轮下注
	 */
	public function tcp_0x202(){
		$conInfo = GlobalData::getConnectInfoWithFd($this->fd);
		if (!is_array($conInfo)){
			ServerManager::getInstance()->logs('error', "repeat bet failed, find connect info, fd:{$this->fd}");
			return ;
		}
		ServerManager::getInstance()->businessTask($conInfo['rid'], 'repeatBet', array('mid'=>$conInfo['mid'], 'rid'=>$conInfo['rid'], 'fd'=>$this->fd));
	}
	
	/**
	 * 取消下注
	 */
	public function tcp_0x203(){
		$conInfo = GlobalData::getConnectInfoWithFd($this->fd);
		if (!is_array($conInfo)){
			ServerManager::getInstance()->logs('error', "repeat bet failed, find connect info, fd:{$this->fd}");
			return ;
		}
		ServerManager::getInstance()->businessTask($conInfo['rid'], 'cancelBet', array('mid'=>$conInfo['mid'], 'rid'=>$conInfo['rid']));
	}
	
	
	/**
	 * 用户点击开奖
	 */
	public function tcp_0x207(){
		$conInfo = GlobalData::getConnectInfoWithFd($this->fd);
		if (!is_array($conInfo)){
			ServerManager::getInstance()->logs('error', "result failed, find connect info, fd:{$this->fd}");
			return ;
		}
		ServerManager::getInstance()->businessTask($conInfo['rid'], 'result', array('mid'=>$conInfo['mid'], 'rid'=>$conInfo['rid']));
	}
	
	public function tcp_0x401(){
		$conInfo = GlobalData::getConnectInfoWithFd($this->fd);
		if (!is_array($conInfo)){
			ServerManager::getInstance()->logs('error', "friend request failed, find connect info, fd:{$this->fd}");
			return ;
		}
		$requestedMid = $this->readPackage->ReadInt();
		ServerManager::getInstance()->businessTask($conInfo['rid'], 'friendRequest', array('mid'=>$conInfo['mid'], 'rid'=>$conInfo['rid'], 'requestedMid'=>$requestedMid));
	}
	
	public function tcp_0x402(){
		$conInfo = GlobalData::getConnectInfoWithFd($this->fd);
		if (!is_array($conInfo)){
			ServerManager::getInstance()->logs('error', "friend accept failed, find connect info, fd:{$this->fd}");
			return ;
		}
		
		$requestMid = $this->readPackage->ReadInt();
		ServerManager::getInstance()->businessTask($conInfo['rid'], 'acceptFriendRequest', array('mid'=>$conInfo['mid'], 'rid'=>$conInfo['rid'], 'requestMid'=>$requestMid));
	}
	
	public function tcp_0x403(){
		$conInfo = GlobalData::getConnectInfoWithFd($this->fd);
		if (!is_array($conInfo)){
			ServerManager::getInstance()->logs('error', "friend delete failed, find connect info, fd:{$this->fd}");
			return ;
		}
		
		$fmid = $this->readPackage->ReadInt();
		ServerManager::getInstance()->businessTask($conInfo['rid'], 'delFriend', array('mid'=>$conInfo['mid'], 'rid'=>$conInfo['rid'], 'fmid'=>$fmid));
	}
	
	
	
	/**
	 * 心跳包
	 */
	
	private $_errHeartBeatList = array();
	
	private function _getErrHeartBeatCount($fd, $time){
		$index = round($time/600);
		if (!isset($this->_errHeartBeatList[$index][$this->fd])){
			return 0;
		}
		return $this->_errHeartBeatList[$index][$this->fd];
	}
	
	private function _updateErrHeartBeatCount($fd, $time){
		$index = round($time/600);
		
		$tmpList = $this->_errHeartBeatList[$index];
		$this->_errHeartBeatList = array();
		$this->_errHeartBeatList[$index] = $tmpList;
		if (!isset($this->_errHeartBeatList[$index][$this->fd])){
			$this->_errHeartBeatList[$index][$this->fd] = 0;
		}
		$this->_errHeartBeatList[$index][$this->fd]++;
	}
	
	public function tcp_0x2(){
		//先发包
		UserMessager::sendUserHeartBeat($this->fd);
		
		$currentTime = time();
		$conInfo = GlobalData::getConnectInfoWithFd($this->fd);
		if (!is_array($conInfo)){
			$errCount = $this->_getErrHeartBeatCount($this->fd, $currentTime);
			if ($errCount > 0){
				ServerManager::getInstance()->logs('error', "heartbeat failed, find fdInfo, fd:{$this->fd}");
				ServerManager::getInstance()->swoole->close($this->fd);
			}
			$this->_updateErrHeartBeatCount($this->fd, $currentTime);
		}
	}
	
	
	public function tcp_0x3(){
		$conInfo = GlobalData::getConnectInfoWithFd($this->fd);
		if (!is_array($conInfo)){
			ServerManager::getInstance()->logs('error', "chat failed, find connect info, fd:{$this->fd}");
			return ;
		}
		
		$msg = $this->readPackage->ReadString();
		if (empty($msg)){
			ServerManager::getInstance()->logs('error', "chat failed, msg empty, fd:{$this->fd}");
			return ;
		}
		ServerManager::getInstance()->businessTask($conInfo['rid'], 'chat', array('mid'=>$conInfo['mid'], 'rid'=>$conInfo['rid'], 'msg'=>$msg));
	}
	
	public function tcp_0x4(){
		$conInfo = GlobalData::getConnectInfoWithFd($this->fd);
		if (!is_array($conInfo)){
			ServerManager::getInstance()->logs('error', "chat failed, find connect info, fd:{$this->fd}");
			return ;
		}
		
		$gid = $this->readPackage->ReadInt();
		ServerManager::getInstance()->businessTask($conInfo['rid'], 'magicFace', array('mid'=>$conInfo['mid'], 'rid'=>$conInfo['rid'], 'gid'=>$gid));
	}
	
	public function tcp_0x5(){
		$conInfo = GlobalData::getConnectInfoWithFd($this->fd);
		if (!is_array($conInfo)){
			ServerManager::getInstance()->logs('error', "send act item failed, find connect info, fd:{$this->fd}");
			return ;
		}
		$toSeatId = $this->readPackage->ReadByte();
		$itemId = $this->readPackage->ReadInt();
		$isCostMoney = $this->readPackage->ReadByte();
		ServerManager::getInstance()->businessTask($conInfo['rid'], 'sendActItem', array('mid'=>$conInfo['mid'], 'rid'=>$conInfo['rid'], 'toSeatId'=>$toSeatId, 'itemId'=>$itemId, 'isCostMoney'=>$isCostMoney));
	}
	
	
	public function tcp_0x7(){
		$conInfo = GlobalData::getConnectInfoWithFd($this->fd);
		if (!is_array($conInfo)){
			ServerManager::getInstance()->logs('error', "send money failed, find connect info, fd:{$this->fd}");
			return ;
		}
		$toMid = $this->readPackage->ReadInt();
		$sendMoney = $this->readPackage->ReadInt();
		ServerManager::getInstance()->businessTask($conInfo['rid'], 'sendMoney', array('mid'=>$conInfo['mid'], 'rid'=>$conInfo['rid'], 'toMid'=>$toMid, 'sendMoney'=>$sendMoney));
	}
	
	public function tcp_0x113(){
		
	}
	
	/**
	 * 管理命令
	 */
	public function tcp_0x888(){
		//判断是否为局域网ip
		
		$sconnInfo = ServerManager::getInstance()->swoole->connection_info($this->fd);
		$clientIp = $sconnInfo['remote_ip'];
		$ip = ip2long($clientIp);
		if (!($ip == 2130706433 || $ip >> 24 === 10 || $ip >> 20 === 2753 || $ip >> 16 === 49320)){
			ServerManager::getInstance()->logs('admin', "admin failed, wrong ip, ip:{$clientIp}");
// 			ServerManager::getInstance()->swoole->close($this->fd);
// 			return ;
		}
		
		$ckey = $this->readPackage->ReadString();
		if ($ckey != MANAGER_KEY){
			ServerManager::getInstance()->swoole->close($this->fd);
			ServerManager::getInstance()->logs('admin', "admin failed, wrong admin key, key:{$ckey}");
			return;
		}
		
		
		$cmd = $this->readPackage->ReadByte(); //命令
		AdminWorker::handle($this->fd, $cmd, $this->readPackage);
		
		ServerManager::getInstance()->logs('admin', "admin succ, cmd:{$cmd}");
		
		return;
	}
	
}

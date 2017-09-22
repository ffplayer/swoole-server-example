<?php
namespace Logic;

use Core\Task\TaskHandler;
use Model\Room;
use Model\ServerManager;
use Model\RoomPool;
use Model\TablePool;
use Model\UserMessager;
use Model\TableMessager;
use Model\RoomMessager;
use Model\User;
use Model\UserPool;
use Model\Config;
use Model\GameConst;
use Model\MongoTable;
use Model\LCHelp;
use Model\GlobalData;


class RoomTaskHandler extends TaskHandler{
	
	/**
	 * 进入房间
	 */
	public function enterRoomAction($params){
		$rid = $params['rid'];
		$mid = $params['mid'];
		$unid = $params['unid'];
		$fd = $params['fd'];
		$realRid = $params['realRid'] ? $params['realRid'] : $rid;
		
		$room = RoomPool::getRoom($rid);
		if (is_null($room)){
			UserMessager::sendEnterRoomResultv2($fd, GameConst::LOGIN_RET_ERR_ROOM_DATA, $realRid);
			ServerManager::getInstance()->logs('error', "login failed, can not find room, mid:{$mid}, rid:{$rid}");
			return ;
		}
		
		$table = TablePool::getUserTable($rid, $mid);
		if (is_null($table)){
			$table = TablePool::getAvailTable($rid);
		}
		if (is_null($table)){
			UserMessager::sendEnterRoomResultv2($fd, GameConst::LOGIN_RET_ERR_TABLE_DATA, $realRid);
			ServerManager::getInstance()->logs('error', "login failed, can not find table, mid:{$mid}, rid:{$rid}");
			return ;
		}
		
		$ret = UserPool::loadDefault($mid, $unid);
		if (false == $ret){
			UserMessager::sendEnterRoomResultv2($fd, GameConst::LOGIN_RET_ERR_ACCOUNT, $realRid);
			ServerManager::getInstance()->logs('error', "login failed, load user, mid:{$mid}, rid:{$rid}");
			return ;
		}
		
		$user = UserPool::getUser($mid);
		$user->realRid = $realRid;
		if (Config::$game['aRoomCfg'][$rid]['inRoomMinMoney'] != -1 && $user->money < Config::$game['aRoomCfg'][$rid]['inRoomMinMoney']){
			UserMessager::sendEnterRoomResultv2($fd, GameConst::LOGIN_RET_ERR_ASSET_LOW, $realRid);
			ServerManager::getInstance()->logs('error', "login failed, asset too less, mid:{$mid}, rid:{$rid}");
			return ;
		}
		
		if (Config::$game['aRoomCfg'][$rid]['inRoomMaxMoney'] != -1 && $user->money > Config::$game['aRoomCfg'][$rid]['inRoomMaxMoney']){
			UserMessager::sendEnterRoomResultv2($fd, GameConst::LOGIN_RET_ERR_ASSET_HIGH, $realRid);
			ServerManager::getInstance()->logs('error', "login failed, asset too much, mid:{$mid}, rid:{$rid}");
			return ;
		}
		
		$ret = $user->updateGI($realRid);
		if (false == $ret){
			UserMessager::sendEnterRoomResultv2($fd, GameConst::LOGIN_RET_ERR_ACCOUNT, $realRid);
			ServerManager::getInstance()->logs('error', "login failed, update gi, mid:{$mid}, rid:{$rid}");
			return;
		}
		
		$lckey = LCHelp::getKeyRoomUser($rid);
		ServerManager::getInstance()->transit->lc($user->sid, $mid, LCHelp::LC_PRIMARY_KEY, array($lckey=>1));
		
		$table->addUser($mid);
		
		$conInfo = GlobalData::getConnectInfoWithMid($mid);
		if (!is_null($conInfo) && $conInfo['fd'] != $fd){
			UserMessager::sendSystemMsgWithFd($conInfo['fd'], GameConst::SYSTEM_SIGN_ACCOUNT_MULTI_LOGIN);
			ServerManager::getInstance()->logs('error', "multi login, mid:{$mid}, newFd:{$fd}, oldFd:{$conInfo['fd']}");
			GlobalData::delConnectInfoWithMid($mid);
		}
		GlobalData::setConnectInfo($fd, $mid, $rid);
		
		//房间和桌子当前信息
		UserMessager::sendEnterRoomResultv2($fd, GameConst::LOGIN_RET_OK, $realRid);
		UserMessager::sendRoomData($mid, $rid);
		UserMessager::sendTableStandUsesr($mid, $rid);
		
		//通过坐下广播接口，告诉客户端当前有几个人坐着
		UserMessager::sendSeatListMessage($mid, $rid);
		
		UserPool::clearOfflineRecord($rid, $mid);
		
		ServerManager::getInstance()->logs('login', $mid, 10);
		return ;
	}
	
	
	/**
	 * 退出房间
	 */
	public function exitRoomAction($params){
		$mid = $params['mid'];
		$rid = $params['rid'];
		$fd = $params['fd'];
		
		$table = TablePool::getUserTable($rid, $mid);
		if (is_null($table)){
			UserMessager::sendExitTableResultWithFd($fd, 1);
			ServerManager::getInstance()->logs('error', "logout failed, table error, mid:{$mid}, rid:{$rid}");
			return ;
		}
		
		UserMessager::sendExitTableResultWithFd($fd, 0);
		
		UserPool::userExit($table, $mid);
		
		ServerManager::getInstance()->logs('error', "logout succ, mid:{$mid}, rid:{$rid}");
		
		return ;
	}
	
	
	/**
	 * 用户坐下
	 */
	public function sitDownAction($params){
	
		$mid = $params['mid'];
		$rid = $params['rid'];
		$seatId = $params['pos'];
		
		$table = TablePool::getUserTable($rid, $mid);
		if (is_null($table)){
			ServerManager::getInstance()->logs('error', "sit down failed, table error, mid:{$mid}, rid:{$rid}");
			return ;
		}
		
		if (false == $table->userSitDown($mid, $seatId)){
			ServerManager::getInstance()->logs('error', "sit down failed, site logic error, mid:{$mid}, rid:{$rid}, seat:{$seatId}");
			return ;
		}
		
		ServerManager::getInstance()->logs('error', "sit down succ, mid:{$mid}, rid:{$rid}, seat:{$seatId}");
	}
	
	/**
	 * 用户站起
	 */
	public function standUpAction($params){
		$mid = $params['mid'];
		$rid = $params['rid'];
		
		$table = TablePool::getUserTable($rid, $mid);
		if (is_null($table)){
			ServerManager::getInstance()->logs('error', "stand up failed, table error, mid:{$mid}, rid:{$rid}");
			return ;
		}
		
		$seatId = $table->userStandUp($mid);
		if (false == $seatId){
			ServerManager::getInstance()->logs('error', "stand up failed, logic error, mid:{$mid}, rid:{$rid}");
			return;
		}
		
		ServerManager::getInstance()->logs('error', "stand up succ,  mid:{$mid}, rid:{$rid}, seat:{$seatId}");
	}
	
	
	/**
	 * 广播下局准备开始
	 */
	public function gameReadyAction($params){
		$rid = $params['rid'];
	
		$taskid = ServerManager::getInstance()->getTaskWorkerId();
		ServerManager::getInstance()->logs('gameProcess', "[setp 1] GameReady, rid:{$rid}");
		
		//修改房间状态
		$room = RoomPool::getRoom($rid);
		if (null == $room){
			ServerManager::getInstance()->logs('gameProcess', "GameReady failed, wrong room, rid:{$rid}");
			return ;
		}
		$room->gameReady();
		
		RoomMessager::sendGameReady($room);
		
		//添加3秒后  开局定  时任务
		ServerManager::getInstance()->timerBusinessTask($rid, 'gameBetStart', array('rid'=>$rid), Config::$game['readyTime'], 1);
	}
	
	/**
	 * 广播开始下注
	 */
	public function gameBetStartAction($params){
		$rid = $params['rid'];
	
		$taskid = ServerManager::getInstance()->getTaskWorkerId();
		ServerManager::getInstance()->logs('gameProcess', "[setp 2] GameBetStart, rid:{$rid}");
		
		//房间改变状态
		$room = RoomPool::getRoom($rid);
		if (null == $room){
			ServerManager::getInstance()->logs('gameProcess', "GameBetStart failed, wrong room, rid:{$rid}");
			return ;
		}
		
		$room->gameBetStart();
	
		//广播现在可以下注
		RoomMessager::sendGameBetStart($room);
		
		ServerManager::getInstance()->timerBusinessTask($rid, 'showStandUserBetBuffer', array('rid'=>$rid), GameConst::TIME_SHOW_STAND_USER_BET, 20);
		
		//添加定时任务， 20秒后， name:下注结束
		ServerManager::getInstance()->timerBusinessTask($rid, 'gameBetEnd', array('rid'=>$rid), Config::$game['betTime'], 1);
	}
	
	
	/**
	 * 结束下注
	 */
	public function gameBetEndAction($params){
		$rid = $params['rid'];
	
		$taskid = ServerManager::getInstance()->getTaskWorkerId();
		ServerManager::getInstance()->logs('gameProcess', "[setp 3] GameBetEnd, rid:{$rid}");
		
		//修改状态
		$room = RoomPool::getRoom($rid);
		if (null == $room){
			ServerManager::getInstance()->logs('gameProcess', "GameBetEnd failed, wrong room, rid:{$rid}");
			return ;
		}
		
		$room->gameBetEnd();
	
		$isShowRollDice = $room->checkRollDiceButtonShow();
		
		RoomMessager::sendGameBetEnd($room, $isShowRollDice);
	
		if (true == $isShowRollDice){
			$timerId = ServerManager::getInstance()->timerBusinessTask($rid, 'gameResult', array('rid'=>$rid), GameConst::GAME_TIME_ROLL_DICE, 1);
			$room->updateResultTimer($timerId);
		}else{
			ServerManager::getInstance()->businessTask($rid, 'gameResult', array('rid'=>$rid));
		}
		
	}
	
	
	/**
	 * 广播开奖结果
	 */
	public function gameResultAction($params){
		$rid = $params['rid'];
	
		$taskid = ServerManager::getInstance()->getTaskWorkerId();
		ServerManager::getInstance()->logs('gameProcess', "[setp 4] GameResult, rid:{$rid}");
		
		//添加定时任务, 7秒后， name:广播奖励
		ServerManager::getInstance()->timerBusinessTask($rid, 'gameReward', array('rid'=>$rid), GameConst::GAME_TIME_SHOW_RESULT, 1);
		
		$room = RoomPool::getRoom($rid);
		if (null == $room){
			ServerManager::getInstance()->logs('gameProcess', "GameResult failed, wrong room, rid:{$rid}");
			return ;
		}
		
		$room->gameRollDice();
		RoomMessager::sendGameResult($room);
		
		$room->updateResultTimer(null);
		
		//计算奖励
		$room->gameReward();
	}
	
	
	/**
	 * 广播房间内所有用户奖励
	 */
	public function gameRewardAction($params){
		$rid = $params['rid'];
		
		$taskid = ServerManager::getInstance()->getTaskWorkerId();
		ServerManager::getInstance()->logs('gameProcess', "[setp 5] GameReward, rid:{$rid}");
		
		$room = RoomPool::getRoom($rid);
		if (null == $room){
			ServerManager::getInstance()->logs('gameProcess', "GameReward failed, wrong room, rid:{$rid}");
			return ;
		}
		
		RoomMessager::sendGameReward($room);
	
		$rewardTime = GameConst::GAME_TIME_SHOW_REWARD;
		if (!empty($room->lottery->rewardUserList)){
			$rewardTime += GameConst::GAME_TIME_SHOW_LOTTERY;
		}
		
		//添加7秒后 准备开局倒计时 定时任务
		ServerManager::getInstance()->timerBusinessTask($rid, 'gameReset', array('rid'=>$rid), $rewardTime, 1);
	}
	
	public function gameResetAction($params){
		$rid = $params['rid'];
		
		$taskid = ServerManager::getInstance()->getTaskWorkerId();
		ServerManager::getInstance()->logs('gameProcess', "[setp 6] GameReset, rid:{$rid}");
		
		$room = RoomPool::getRoom($rid);
		if (null == $room){
			ServerManager::getInstance()->logs('gameProcess', "GameReset failed, wrong room, rid:{$rid}");
			return ;
		}
		
		$status = GlobalData::getServerStatus();
		if ($status == GameConst::SERVER_ST_STOP){
			RoomMessager::sendSystemMsg($room, GameConst::SYSTEM_SIGN_SERVER_UPDATE);
			ServerManager::getInstance()->logs('gameProcess', "[Stop], server stop, rid:{$rid}");
			return ;
		}
		
		$ret = $room->gameReset();
		
		$resetTime = 0;
		if (true == $ret['combineTable']){
			$resetTime += 2;
		}
		
		if ($resetTime == 0){
			ServerManager::getInstance()->businessTask($rid, 'gameReady', array('rid'=>$rid));
		}else{
			ServerManager::getInstance()->timerBusinessTask($rid, 'gameReady', array('rid'=>$rid), $resetTime, 1);
		}
	}
	
	/**
	 * 用户下注
	 */
	public function betAction($params){
		$mid = $params['mid'];
		$rid = $params['rid'];
		$areaId = $params['betPos'];
		$money = $params['amount'];
		$fd = $params['fd'];
		
		$user = UserPool::getUser($mid);
		if ($user->mstatus == 1){
			UserMessager::sendEnterRoomResultv2($fd, GameConst::LOGIN_RET_ERR_ACCOUNT_DISABLE, $rid);
			ServerManager::getInstance()->transit->lc($user->sid, $mid, LCHelp::LC_PRIMARY_KEY, array(LCHelp::LC_KEY_BET_FAILED=>1));
			
			ServerManager::getInstance()->logs('error', "bet failed, user status, mid:{$mid}, rid:{$rid}");
			return ;
		}
		
		$table = TablePool::getUserTable($rid, $mid);
		if (is_null($table)){
			ServerManager::getInstance()->transit->lc($user->sid, $mid, LCHelp::LC_PRIMARY_KEY, array(LCHelp::LC_KEY_BET_FAILED=>1));
			ServerManager::getInstance()->logs('error', "bet failed, wrong table, mid:{$mid}, rid:{$rid}");
			return ;
		}
		
		$betRet = $table->bet($mid, $areaId, $money);
		UserMessager::sendUserBet($mid, $rid, $betRet, $areaId, $money);
		if ($betRet != GameConst::BET_RET_OK){
			ServerManager::getInstance()->transit->lc($user->sid, $mid, LCHelp::LC_PRIMARY_KEY, array(LCHelp::LC_KEY_BET_FAILED=>1));
			ServerManager::getInstance()->logs('error', "bet failed, logic error, mid:{$mid}, rid:{$rid}, ret:{$betRet}");
			return ;
		}
		
		if ($table->userList[$mid]['type'] == User::USER_TYPE_BET_SIT){
			TableMessager::sendSeatUserBet($table, $mid);
		}
	}
	
	
	/**
	 * 重复上一轮下注
	 */
	public function repeatBetAction($params){
		$mid = $params['mid'];
		$rid = $params['rid'];
		$fd = $params['fd'];
		
		$user = UserPool::getUser($mid);
		if ($user->mstatus == 1){
			UserMessager::sendEnterRoomResultv2($fd, GameConst::LOGIN_RET_ERR_ACCOUNT_DISABLE, $rid);
			ServerManager::getInstance()->transit->lc($user->sid, $mid, LCHelp::LC_PRIMARY_KEY, array(LCHelp::LC_KEY_BET_FAILED=>1));
			
			ServerManager::getInstance()->logs('error', "repeat bet failed, user status, mid:{$mid}, rid:{$rid}");
			return ;
		}
		
		$table = TablePool::getUserTable($rid, $mid);
		if (is_null($table)){
			ServerManager::getInstance()->transit->lc($user->sid, $mid, LCHelp::LC_PRIMARY_KEY, array(LCHelp::LC_KEY_BET_FAILED=>1));
			
			ServerManager::getInstance()->logs('error', "repeat bet failed, wrong table, mid:{$mid}, rid:{$rid}");
			return ;
		}
		
		$betRet = $table->repeatBet($mid);
		UserMessager::sendUserRepeatBet($mid, $rid, $betRet);
		if ($betRet != GameConst::BET_RET_OK){
			ServerManager::getInstance()->transit->lc($user->sid, $mid, LCHelp::LC_PRIMARY_KEY, array(LCHelp::LC_KEY_BET_FAILED=>1));
			
			ServerManager::getInstance()->logs('error', "repeat bet failed, logic error, mid:{$mid}, rid:{$rid}, ret:{$betRet}");
			return ;
		}
	
		if ($table->userList[$mid]['type'] == User::USER_TYPE_BET_SIT){
			TableMessager::sendSeatUserBet($table, $mid);
		}
	}
	
	public function cancelBetAction($params){
		$rid = $params['rid'];
		$mid = $params['mid'];
		
		$table = TablePool::getUserTable($rid, $mid);
		if (is_null($table)){
			ServerManager::getInstance()->logs('error', "cancel bet failed, wrong table, mid:{$mid}");
			return ;
		}
		
		$ret = $table->cancelBet($mid);
		if ($ret != GameConst::CANCEL_BET_OK){
			UserMessager::sendUserCancelBet($mid, $rid, $ret);
			ServerManager::getInstance()->logs('error', "cancel bet failed, ret:{$ret}, mid:{$mid}");
			return ;
		}
		
		UserMessager::sendUserCancelBet($mid, $rid, $ret);
		TableMessager::sendBroadcastCancelBet($table, $mid);
		$table->record->popUserCancelBetList($mid);
		
		ServerManager::getInstance()->logs('error', "cancel bet, ret:{$ret}, mid:{$mid}");
		return ;
	}
	
	
	public function showStandUserBetBufferAction($params){
		$rid = $params['rid'];
		
		$tableList = TablePool::getTableList($rid);
		foreach ($tableList as $table){
			TableMessager::sendStanUserBet($table);
		}
	}
	
	
	/**
	 * 用户触发开奖
	 */
	public function resultAction($params){
		$rid = $params['rid'];
		$mid = $params['mid'];
		
		$room = RoomPool::getRoom($rid);
		if (is_null($room)){
			ServerManager::getInstance()->logs('error', "result failed, wrong room, rid:{$rid}");
			return ;
		}
		
		if ($room->status != Room::ROOM_ST_ROLL_DICE){
			ServerManager::getInstance()->logs('error', "result failed, wrong room st, rid:{$rid}, st:{$room->status}");
			return ;
		}
		
		if (false == $room->checkRollDiceButtonShow()){
			ServerManager::getInstance()->logs('error', "result failed, wrong roll button, rid:{$rid}");
			return ;
		}
		
		if (is_null($room->resultTimerId)){
			ServerManager::getInstance()->logs('error', "result failed, wrong timer, rid:{$rid}");
			return ;
		}
		
		$maxBetUser = $room->getMaxBetMoneyUserInfo();
		if ($maxBetUser['mid'] != $mid){
			ServerManager::getInstance()->logs('error', "result failed, wrong user, rid:{$rid}");
			return;
		}
		
		ServerManager::getInstance()->delBusinessTask($room->resultTimerId);
		
		//取消定时任务， name: 广播开奖结果
		ServerManager::getInstance()->businessTask($rid, 'gameResult', array('rid'=>$rid));
	}
	
	/**
	 * 聊天广播
	 * @param unknown $params
	 */
	public function chatAction($params){
		$rid = $params['rid'];
		$mid = $params['mid'];
		$msg = $params['msg'];
		
		$table = TablePool::getUserTable($rid, $mid);
		if (is_null($table)){
			ServerManager::getInstance()->logs('error', "chat failed, wrong table, rid:{$rid}, mid:{$mid}");
			return ;
		}
		TableMessager::sendUserChat($table, $mid, $msg);
	}
	
	
	public function magicFaceAction($params){
		$rid = $params['rid'];
		$mid = $params['mid'];
		$gid = $params['gid'];
		
		$table = TablePool::getUserTable($rid, $mid);
		if (is_null($table)){
			ServerManager::getInstance()->logs('error', "magic face failed, wrong table, rid:{$rid}, mid:{$mid}");
			return ;
		}
		
		if ($table->userList[$mid]['type'] != User::USER_TYPE_BET_SIT){
			ServerManager::getInstance()->logs('error', "magic face failed, user seat, rid:{$rid}, mid:{$mid}");
			return ;
		}
		
		TableMessager::sendUserMagicFace($table, $mid, $gid);
	}
	
	
	public function friendRequestAction($params){
		$mid = $params['mid'];
		$rid = $params['rid'];
		$requestedMid = $params['requestedMid'];
		
		$table = TablePool::getUserTable($rid, $mid);
		if (is_null($table)){
			ServerManager::getInstance()->logs('error', "friend request failed, wrong table, rid:{$rid}, mid:{$mid}");
			return ;
		}
		
		if ($table->userList[$mid]['type'] != User::USER_TYPE_BET_SIT){
			ServerManager::getInstance()->logs('error', "friend request failed, wrong seat, rid:{$rid}, mid:{$mid}");
			return ;
		}
		
		if (!isset($table->userList[$requestedMid])){
			ServerManager::getInstance()->logs('error', "friend request failed, wrong friend, rid:{$rid}, mid:{$mid}, fmid:{$requestedMid}");
			return ;
		}
		
		if ($table->userList[$requestedMid]['type'] != User::USER_TYPE_BET_SIT){
			ServerManager::getInstance()->logs('error', "friend request failed, wrong friend seat, rid:{$rid}, mid:{$mid}, fmid:{$requestedMid}");
			return ;
		}
		
		$seatId = $table->userList[$mid]['pos'];
		$requestedSeatId = $table->userList[$requestedMid]['pos'];
		TableMessager::sendFreindRequest($table, $mid, $seatId, $requestedMid, $requestedSeatId);
	}
	
	public function acceptFriendRequestAction($params){
		$mid = $params['mid'];
		$rid = $params['rid'];
		$requestMid = $params['requestMid'];
		
		$table = TablePool::getUserTable($rid, $mid);
		if (is_null($table)){
			ServerManager::getInstance()->logs('error', "friend accept failed, wrong table, rid:{$rid}, mid:{$mid}");
			return ;
		}
		
		if ($table->userList[$mid]['type'] != User::USER_TYPE_BET_SIT){
			ServerManager::getInstance()->logs('error', "friend accept failed, wrong seat, rid:{$rid}, mid:{$mid}");
			return ;
		}
		
		if (!isset($table->userList[$requestMid])){
			ServerManager::getInstance()->logs('error', "friend accept failed, wrong friend, rid:{$rid}, mid:{$mid}, fmid:{$requestMid}");
			return ;
		}
		
		if ($table->userList[$requestMid]['type'] != User::USER_TYPE_BET_SIT){
			ServerManager::getInstance()->logs('error', "friend accept failed, wrong friend seat, rid:{$rid}, mid:{$mid}, fmid:{$requestMid}");
			return ;
		}
		
		$seatId = $table->userList[$mid]['pos'];
		$requestSeatId = $table->userList[$requestMid]['pos'];
		TableMessager::sendAcceptFreindRequest($table, $mid, $seatId, $requestMid, $requestSeatId);
	}
	
	
	public function delFriendAction($params){
		$mid = $params['mid'];
		$rid = $params['rid'];
		$fmid = $params['fmid'];
		
		$table = TablePool::getUserTable($rid, $mid);
		if (is_null($table)){
			ServerManager::getInstance()->logs('error', "friend delete failed, wrong table, rid:{$rid}, mid:{$mid}");
			return;
		}
		
		if ($table->userList[$mid]['type'] != User::USER_TYPE_BET_SIT){
			ServerManager::getInstance()->logs('error', "friend delete failed, wrong seat, rid:{$rid}, mid:{$mid}");
			return;
		}
		
		if (!isset($table->userList[$fmid])){
			ServerManager::getInstance()->logs('error', "friend delete failed, wrong friend, rid:{$rid}, mid:{$mid}, fmid:{$fmid}");
			return ;
		}
		
		if ($table->userList[$fmid]['type'] = User::USER_TYPE_BET_SIT){
			ServerManager::getInstance()->logs('error', "friend delete failed, wrong friend seat, rid:{$rid}, mid:{$mid}, fmid:{$fmid}");
			return ;
		}
		
		$seatId = $table->userList[$mid]['pos'];
		$fseateId = $table->userList[$fmid]['pos'];
		TableMessager::sendDelFriend($table, $mid, $seatId, $fmid, $fseateId);
	}
	
	public function getRoomStandUserListAction($params){
		$fd = $params['fd'];
		$rid = $params['rid'];
		$tid = $params['tid'];
		$page = $params['page'];
		$pageSize = $params['pageSize'];
		
		$table = TablePool::getTable($rid, $tid);
		if (is_null($table)){
			UserMessager::sendRoomStandListWithFd($fd, 0,  array());
			return ;
		}
		
		$count = $table->getStandBetUserCount();
		$list = $table->getStandBetUserList($page, $pageSize);
		
		UserMessager::sendRoomStandListWithFd($fd, $count,  $list);
	}
	
	public function fetchRoomDataAction($params){
		$rid = $params['rid'];
		$mid = $params['mid'];
		$fd = $params['fd'];
		UserMessager::sendFetchRoomData(GameConst::FETCH_ROOM_DATA_RET_OK, $fd);
		UserMessager::sendRoomData($mid, $rid);
		UserMessager::sendTableStandUsesr($mid, $rid);
		UserMessager::sendSeatListMessage($mid, $rid);
	}
	
	
	public function userConnectColseAction($params){
		$mid = $params['mid'];
		$rid = $params['rid'];
	
		$table = TablePool::getUserTable($rid, $mid);
		
		if (is_null($table)){
			ServerManager::getInstance()->logs('error', "close connect, can not find table, mid:{$mid}, rid:{$rid}");
			return ;
		}
		
		UserPool::userOffline($table, $mid);
	
		ServerManager::getInstance()->logs('error', "Connection Close, mid:{$mid}, rid:{$rid}");
	}
	
	
	public function sendActItemAction($params){
		$mid = $params['mid'];
		$rid = $params['rid'];
		$toSeatId = $params['toSeatId'];
		$itemId = $params['itemId'];
		$isCostMoney = $params['isCostMoney'];
		
		$user = UserPool::getUser($mid);
		$table = TablePool::getUserTable($rid, $mid);
		$room = RoomPool::getRoom($rid);
		if (is_null($table) || is_null($user)){
			UserMessager::sendActItemPack($mid, GameConst::ACT_ITEM_RET_ERR_SELF_SEAT, 0);
			return ;
		}
		
		$selfSeatId = $table->userList[$mid]['pos'];
		if ($toSeatId == $selfSeatId){
			UserMessager::sendActItemPack($mid, GameConst::ACT_ITEM_RET_ERR_TO_SEAT, 0);
			return ;
		}
		
		if ($table->userList[$mid]['type'] != User::USER_TYPE_BET_SIT){
			UserMessager::sendActItemPack($mid, GameConst::ACT_ITEM_RET_ERR_SELF_SEAT, 0);
			return ;
		}
		
		if (!isset($table->betSeatList[$toSeatId]) || $table->betSeatList[$toSeatId] == User::MID_NONE){
			UserMessager::sendActItemPack($mid, GameConst::ACT_ITEM_RET_ERR_TO_SEAT, 0);
			return ;
		}
		
		$neeMoney = 0;
		if ($isCostMoney){
			$factory = GameConst::ACT_ITEM_MONEY_FACTORY;
			if (isset(Config::$game['propMul']) && Config::$game['propMul'] != 0){
				$factory = Config::$game['propMul'];
			}
			$neeMoney = ceil(Config::$game['aRoomCfg'][$rid]['minBet'] * $factory);
			$ret = $user->decMoney($neeMoney, GameConst::MONEY_SOURCE_SEND_ACT_ITEM, $rid);
			if (false == $ret){
				UserMessager::sendActItemPack($mid, GameConst::ACT_ITEM_RET_ERR_DEC_MONEY, 0);
				return ;
			}
			ServerManager::getInstance()->transit->proc('AddPropsLog', array($mid, $table->betSeatList[$toSeatId], $itemId, Config::$game['tid'], $room->startTime, $neeMoney));
		}
		UserMessager::sendActItemPack($mid, GameConst::ACT_ITEM_RET_OK, $neeMoney);
		TableMessager::sendBroadcastActItem($table, $selfSeatId, $toSeatId, $itemId, $neeMoney);
	}
	
	
	public function sendMoneyAction($params){
		$mid = $params['mid'];
		$rid = $params['rid'];
		$toMid = $params['toMid'];
		$sendMoney = $params['sendMoney'];
		
		if ($sendMoney <= 0){
			ServerManager::getInstance()->logs('error', "send money failed, money error, mid:{$mid}, sendMoney:{$sendMoney}");
			UserMessager::sendMoneyPack($mid, GameConst::SEND_MONEY_RET_ERR_WRONG_MEONY_AMOUNT, 0);
			return ;
		}
		
		if (isset(Config::$game['maxSendMoney']) && Config::$game['maxSendMoney'] > 0 && $sendMoney > Config::$game['maxSendMoney']){
			ServerManager::getInstance()->logs('error', "send money failed, money error, mid:{$mid}, sendMoney:{$sendMoney}, config:".Config::$game['maxSendMoney']);
			UserMessager::sendMoneyPack($mid, GameConst::SEND_MONEY_RET_ERR_WRONG_MEONY_AMOUNT, 0);
			return ;
		}
		
		$user = UserPool::getUser($mid);
		$table = TablePool::getUserTable($rid, $mid);
		$room = RoomPool::getRoom($rid);
		if (is_null($table) || is_null($user)){
			ServerManager::getInstance()->logs('error', "send money failed, table error, mid:{$mid}");
			UserMessager::sendMoneyPack($mid, GameConst::SEND_MONEY_RET_ERR_SELF_SEAT, 0);
			return ;
		}
		
		if ($mid == $toMid){
			ServerManager::getInstance()->logs('error', "send money failed, accepte mid error, mid:{$mid}");
			UserMessager::sendMoneyPack($mid, GameConst::SEND_MONEY_RET_ERR_TO_SEAT, 0);
			return ;
		}
		
		if ($table->userList[$mid]['type'] != User::USER_TYPE_BET_SIT){
			ServerManager::getInstance()->logs('error', "send money failed, user seat error, mid:{$mid}");
			UserMessager::sendMoneyPack($mid, GameConst::SEND_MONEY_RET_ERR_SELF_SEAT, 0);
			return ;
		}
		
		$toUser = UserPool::getUser($toMid);
		if (!isset($table->userList[$toMid]['type']) || $table->userList[$toMid]['type'] != User::USER_TYPE_BET_SIT){
			ServerManager::getInstance()->logs('error', "send money failed, accept user seat error, mid:{$mid}");
			UserMessager::sendMoneyPack($mid, GameConst::SEND_MONEY_RET_ERR_TO_SEAT, 0);
			return ;
		}
		
		$ret = $user->decMoney($sendMoney, GameConst::MONEY_SOURCE_SEND_MONEY, $rid);
		if (false == $ret){
			ServerManager::getInstance()->logs('error', "send money failed, dec money error, mid:{$mid}, money:{$sendMoney}");
			UserMessager::sendMoneyPack($mid, GameConst::SEND_MONEY_RET_ERR_DEC_MONEY, 0);
			return ;
		}
		
		$toUser->addMoney($sendMoney, GameConst::MONEY_SOURCE_ACCEPT_SEND_MONEY, $rid);
		
		$fromSeatId = $table->userList[$mid]['pos'];
		$toSeatId = $table->userList[$toMid]['pos'];
		UserMessager::sendMoneyPack($mid, GameConst::SEND_MONEY_RET_OK, $sendMoney);
		TableMessager::sendBroadcastSendMoney($table, $fromSeatId, $toSeatId, $sendMoney);
		
		ServerManager::getInstance()->transit->proc('AddToChipsLog', array($mid, $toMid, Config::$game['tid'], $room->startTime, $sendMoney, $user->sid, $user->unid));
	}
	
	//-----------------------管理任务-------------------------
	
	public function adminGetRoomSummaryAction($params){
		$rid = $params['rid'];
		
		$roomInfo = array();
		$room = RoomPool::getRoom($rid);
		if (!is_null($room)){
			$roomInfo['tableCount'] = $room->getTableCount();
			$roomInfo['userCount'] = $room->getUserCount();
			$roomInfo['todyInMoney'] = $room->todayInMoney;
			$roomInfo['todaySendMoney'] = $room->todaySendMoney;
			$roomInfo['lotteryMoeny'] = $room->lottery->money;
		}
		ServerManager::getInstance()->swoole->finish($roomInfo);
	}
	
	public function adminStopGameAction($params){
		$rid = $params['rid'];
		$tableList = TablePool::getTableList($rid);
		foreach ($tableList as $table){
			foreach ($table->userList as $mid=>$minfo){
				$betList = $table->record->getUserBetList($mid);
				if (!empty($betList)){
					continue;
				}
				UserMessager::sendSystemMsg($mid, GameConst::SYSTEM_SIGN_SERVER_UPDATE);
			}
		}
	}
	
	public function adminGetProcessAction($params){
		$info = array();
		$info['userCount'] = UserPool::getUserCount();
		ServerManager::getInstance()->swoole->finish($info);
	}
	
	public function adminFixRoomMongoInfoAction($params){
		$rid = $params['rid'];
		
		ServerManager::getInstance()->mongo->delete(MongoTable::tableNameUser(), array('rid'=>$rid), 0);
		$tableList = TablePool::getTableList($rid);
		foreach ($tableList as $table){
			foreach ($table->userList as $mid=>$minfo){
				$table->updateMongoUserInfo($mid);
			}
		}
	}
	
	public function adminClearLeakUserDataAction($params){
		$userList = UserPool::getUserList();
		foreach ($userList as $mid=>$minfo){
			UserPool::tryDelLeakUser($mid);
		}
		ServerManager::getInstance()->swoole->finish($info);
		return;
	}
}


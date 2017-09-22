<?php
namespace Model;

class UserMessager{
	
	public static function sendEnterRoomResult($mid, $rid, $ret){
		
		$roomInfo = self::_getFormatRoomInfo($rid);
		$tableInfo = self::_getFormatTableInfo($rid, $mid);
		
		$pack = new \GSWritePackage();
		$pack->WriteBegin(GameConst::CMD_S_LOGIN);
		
		$pack->WriteByte($ret);
		$pack->WriteByte($roomInfo['status']);
		
		$pack->WriteByte($roomInfo['readyTime']);
		$pack->WriteByte($roomInfo['betTime']);
		
		$pack->WriteByte(count($tableInfo['betList']));
		foreach ($tableInfo['betList'] as $betInfo){
			$pack->WriteByte($betInfo['areaId']);
			$pack->WriteInt64($betInfo['betMoney']);
		}
		
		$pack->WriteByte(count($roomInfo['diceList']));
		foreach ($roomInfo['diceList'] as $dice){
			$pack->WriteByte($dice);
		}
		
		
		$pack->WriteByte(count($roomInfo['rewardAreaList']));
		foreach ($roomInfo['rewardAreaList'] as $areaId=>$areaInfo){
			$pack->WriteByte($areaId);
		}
		
		$pack->WriteInt64($roomInfo['lotteryMoney']);
		
		$pack->WriteEnd();
		self::_send($mid, $pack);
	}
	
	public static function sendEnterRoomResultv2($fd, $ret, $rid=0){
		$pack = new \GSWritePackage();
		$pack->WriteBegin(GameConst::CMD_S_LOGIN);
		$pack->WriteByte($ret);
		$pack->WriteInt($rid);
		$pack->WriteEnd();
		self::_sendFid($fd, $pack);
	}
	
	public static function sendRoomData($mid, $rid){
		$roomInfo = self::_getFormatRoomInfo($rid);
		$tableInfo = self::_getFormatTableInfo($rid, $mid);
		$pack = new \GSWritePackage();
		$pack->WriteBegin(GameConst::CMD_S_GAME_DATA);
		
		$user = UserPool::getUser($mid);
		$pack->WriteByte($roomInfo['status']);
		
		//准备状态
		$pack->WriteByte($roomInfo['readyTime']);
		
		//下注状态
		$pack->WriteByte($roomInfo['betTime']);
		$pack->WriteByte(count($tableInfo['betList']));
		foreach ($tableInfo['betList'] as $betInfo){
			$pack->WriteByte($betInfo['areaId']);
			$pack->WriteInt64($betInfo['betMoney']);
			$pack->WriteInt64($betInfo['selfBetMoney']);
		}
		
		//结算状态
		$pack->WriteByte(count($roomInfo['diceList']));
		foreach ($roomInfo['diceList'] as $dice){
			$pack->WriteByte($dice);
		}
		$pack->WriteByte(count($roomInfo['rewardAreaList']));
		foreach ($roomInfo['rewardAreaList'] as $areaId=>$areaInfo){
			$pack->WriteByte($areaId);
		}
		
		//彩池
		$pack->WriteInt64($roomInfo['lotteryMoney']);
		
		//上局骰子列表
		$isShowLastDice = GameConst::SHOW_LAST_DICE_FALSE;
		if (!empty($roomInfo['lastDiceList'])){
			$isShowLastDice = GameConst::SHOW_LAST_DICE_TRUE;
		}
		
		$pack->WriteByte($isShowLastDice);
		$pack->WriteByte(count($roomInfo['lastDiceList']));
		foreach ($roomInfo['lastDiceList'] as $dice){
			$pack->WriteByte($dice);
		}
		
		//功能展示类
		$showRepeatBetSt = empty($lastBetRecord) ? 0 : 1;
		$pack->WriteByte($showRepeatBetSt);
		
		//房间底注
		$pack->WriteInt($user->realRid);
		$pack->WriteShort($tableInfo['tid']);
		
		$pack->WriteInt64($user->money);
		$pack->WriteEnd();
		self::_send($mid, $pack);
	}
	
	public static function sendFetchRoomData($ret, $fd){
		$pack = new \GSWritePackage();
		$pack->WriteBegin(GameConst::CMD_S_FETCH_ROOM_DATA);
		$pack->WriteByte($ret);
		$pack->WriteEnd();
		self::_sendFid($fd, $pack);
	}
	
	public static function sendTableStandUsesr($mid, $rid){
		$table = TablePool::getUserTable($rid, $mid);
		$pack = new \GSWritePackage();
		$pack->WriteBegin(GameConst::CMD_S_USER_COUNT_UPDATE);
		$pack->WriteShort($table->getStandBetUserCount());
		$pack->WriteEnd();
		self::_send($mid, $pack);
	}
	
	private static function _getFormatRoomInfo($rid){
		$info = array(
				'status'=>0,
				'readyTime'=>0,								//准备倒计时
				'betTime'=>0, 								//下注倒计时
				'diceList'=>array(),						//当前骰子列表
				'rewardAreaList'=>array(),				 	//当前中奖区域列表
				'lotteryMoney'=>0,							//当前彩池余额
				'lastDiceList'=>array(),					//上局骰子列表
		);
		
		$room = RoomPool::getRoom($rid);
		if (is_null($room)){
			return $info;
		}
		
		$info['status'] = $room->status;
		$currentTime = time();
		if ($room->status == Room::ROOM_ST_READY){
			$info['readyTime'] = Config::$game['readyTime']- ($currentTime - $room->statusTime);
		}
		
		if ($room->status == Room::ROOM_ST_BET){
			$info['betTime'] = Config::$game['betTime'] - ($currentTime - $room->statusTime);
		}
		$info['diceList'] = $room->dice->diceList;
		$info['rewardAreaList'] = $room->dice->areaList;
		$info['lotteryMoney'] = $room->lottery->money;
		$info['lastDiceList'] = $room->dice->lastDiceList;
		return $info;
	}
	
	private static function _getFormatTableInfo($rid, $mid){
		$info = array(
				'tid'=>0,
				'betList'=>array(),
		);
		$table = TablePool::getUserTable($rid, $mid);
		if (is_null($table)){
			return $info;
		}
		
		$info['tid'] = $table->tid;
		
		$areaBetList = $table->record->getSummaryAreaBetList();
		foreach ($areaBetList as $areaId=>$money){
			$selfBetMoney = $table->record->getUserAreaBetMoney($mid, $areaId);
			$info['betList'][] = array('areaId'=>$areaId, 'betMoney'=>$money, 'selfBetMoney'=>$selfBetMoney);
		}
		
		return $info;
	}
	
	public static function sendExitTableResultWithFd($fd, $ret){
		$pack = new \GSWritePackage();
		$pack->WriteBegin(GameConst::CMD_S_LOGOUT);
		$pack->WriteByte($ret);
		$pack->WriteEnd();
		self::_sendFid($fd, $pack);
	}
	
	
	public static function sendSeatListMessage($mid, $rid){
		$table = TablePool::getUserTable($rid, $mid);
		foreach ($table->betSeatList as $seatId=>$smid){
			if ($smid == User::MID_NONE){
				continue;
			}
			$seateUser = UserPool::getUser($smid);
			$pack = new \GSWritePackage();
			$pack->WriteBegin(GameConst::CMD_S_USER_SIT);
			$pack->WriteInt($seateUser->mid);
			$pack->WriteByte($seatId);
			$pack->WriteString($seateUser->name);
			$pack->WriteString($seateUser->url);
			$pack->WriteInt64($seateUser->money);
			$pack->WriteByte($seateUser->showVip);
			$pack->WriteByte($seateUser->mvip);
			$pack->WriteByte($seateUser->vipLv);
			$pack->WriteEnd();
			self::_send($mid, $pack);
		}
	}
	
	public static function sendUserBet($mid, $rid, $ret, $betArea, $betMoney){
		$table = TablePool::getUserTable($rid, $mid);
		$user = UserPool::getUser($mid);
		
		$pack = new \GSWritePackage();
		$pack->WriteBegin(GameConst::CMD_S_BET);
		$pack->WriteByte($ret);
		$pack->WriteByte($betArea);
		$pack->WriteInt64($betMoney);
		$pack->WriteInt64($table->record->getSummaryAreaBetMoney($betArea));
		$pack->WriteInt64($table->record->getUserAreaBetMoney($mid, $betArea));
		$pack->WriteInt64($user->money);
		$pack->WriteEnd();
		
		self::_send($mid, $pack);
	}
	
	
	public static function sendUserRepeatBet($mid, $rid, $ret){
		$table = TablePool::getUserTable($rid, $mid);
		$user = UserPool::getUser($mid);
		
		$betList = $table->record->getUserHistoryBetList($mid);
		
		$pack = new \GSWritePackage();
		$pack->WriteBegin(GameConst::CMD_S_BET_REPEAT);
		$pack->WriteByte($ret);
		$pack->WriteByte(count($betList));
		foreach ($betList as $areaId=>$betMoney){
			$pack->WriteByte($areaId);
			$pack->WriteInt64($table->record->getSummaryAreaBetMoney($areaId));
			$pack->WriteInt64($table->record->getUserAreaBetMoney($mid, $areaId));
		}
		$pack->WriteInt64($user->money);
		$pack->WriteEnd();
		
		self::_send($mid, $pack);
	}
	
	public static function sendUserCancelBet($mid, $rid, $ret){
		$table = TablePool::getUserTable($rid, $mid);
		$user = UserPool::getUser($mid);
		
		$cancelList = $table->record->getUserCancelBetList($mid);
		
		$pack = new \GSWritePackage();
		$pack->WriteBegin(GameConst::CMD_S_CANCEL_BET);
		$pack->WriteByte($ret);
		$pack->WriteInt64($user->money);
		
		$cancelAreaCount = count($cancelList);
		$pack->WriteByte($cancelAreaCount);
		foreach ($cancelList as $areaId=>$betMoney){
			$areaSumMoney = $table->record->getSummaryAreaBetMoney($areaId);
			$pack->WriteByte($areaId);
			$pack->WriteInt64($areaSumMoney);
		}
		
		$pack->WriteEnd();
		self::_send($mid, $pack);
	}
	
	public static function sendActItemPack($mid, $ret, $costMoney){
		$pack = new \GSWritePackage();
		$pack->WriteBegin(GameConst::CMD_S_SEND_ACT_ITEM);
		$pack->WriteByte($ret);
		$pack->WriteEnd();
		
		self::_send($mid, $pack);
	}
	
	public static function sendMoneyPack($mid, $ret, $costMoney){
		$pack = new \GSWritePackage();
		$pack->WriteBegin(GameConst::CMD_S_SEND_MONEY);
		$pack->WriteByte($ret);
		$pack->WriteEnd();
		
		self::_send($mid, $pack);
	}
	
	
	public static function sendUserHeartBeat($fd){
		$pack = new \GSWritePackage();
		$pack->WriteBegin(GameConst::CMD_S_HEART_BEAT);
		$pack->WriteByte(0);//占位， 无含义
		$pack->WriteEnd();
		self::_sendFid($fd, $pack);
	}
	
	public static function sendSystemMsg($mid, $sign){
		$pack =  new \GSWritePackage();
		$pack->WriteBegin(GameConst::CMD_S_SYSTEM_SIGN);
		$pack->WriteByte($sign);
		$pack->WriteEnd();
		self::_send($mid, $pack);
	}
	
	public static function sendSystemMsgWithFd($fd, $sign){
		$pack =  new \GSWritePackage();
		$pack->WriteBegin(GameConst::CMD_S_SYSTEM_SIGN);
		$pack->WriteByte($sign);
		$pack->WriteEnd();
		self::_sendFid($fd, $pack);
	}
	
	public static function sendRoomStandListWithFd($fd, $count, $list){
		$listJson = json_encode($list);
		$pack = new \GSWritePackage();
		$pack->WriteBegin(GameConst::CMD_S_GET_ROOM_STAND_LIST);
		$pack->WriteShort($count);
		$pack->WriteString($listJson);
		$pack->WriteEnd();
		self::_sendFid($fd, $pack);
	}
	
	public static function sendAdmin($fd, $msg){
		$pack = new \GSWritePackage();
		$pack->WriteBegin(GameConst::CMD_S_ADMIN);
		$pack->WriteString($msg);
		$pack->WriteEnd();
		self::_sendFid($fd, $pack);
	}
	
	public static function sendTest($mid, Room $room){
		$mem = memory_get_usage();
		$pack = new \GSWritePackage();
		$pack->WriteBegin(GameConst::CMD_S_TEST);
		$pack->WriteInt($room->getTableCount());
		$pack->WriteInt($room->getUserCount());
		$pack->WriteInt64($mem);
		$pack->WriteEnd();
		self::_send($mid, $pack);
	}
	
	private static function _send($mid, \GSWritePackage $pack){
		$connInfo = GlobalData::getConnectInfoWithMid($mid);
		if (!is_array($connInfo)){
			return ;
		}
		if (!ServerManager::getInstance()->swoole->exist($connInfo['fd'])){
			return ;
		}
		ServerManager::getInstance()->swoole->send($connInfo['fd'], $pack->GetPacketBuffer());
	}
	
	private static function _sendFid($fd, \GSWritePackage $pack){
		if (!ServerManager::getInstance()->swoole->exist($fd)){
			return ;
		}
		ServerManager::getInstance()->swoole->send($fd, $pack->GetPacketBuffer());
	}
}
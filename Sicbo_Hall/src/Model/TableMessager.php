<?php
namespace Model;

class TableMessager{
	public static function sendStandUserMessage(Table $table) {
		$pack = new \GSWritePackage();
		$pack->WriteBegin(GameConst::CMD_S_USER_COUNT_UPDATE);
		$pack->WriteShort($table->getStandBetUserCount());
		$pack->WriteEnd();
		self::_broadcastTable($table, $pack);
	}
	
	
	public static function sendUserSitDown(Table $table, $mid, $seatId){
		$user = UserPool::getUser($mid);
		$pack = new \GSWritePackage();
		$pack->WriteBegin(GameConst::CMD_S_USER_SIT);
		$pack->WriteInt($mid);
		$pack->WriteByte($seatId);
		$pack->WriteString($user->name);
		$pack->WriteString($user->url);
		$pack->WriteInt64($user->money);
		$pack->WriteByte($user->showVip);
		$pack->WriteByte($user->mvip);
		$pack->WriteByte($user->vipLv);
		$pack->WriteEnd();
		
		self::_broadcastTable($table, $pack);
	}
	
	
	public static function sendUserStandUp(Table $table, $seatId){
		$pack = new \GSWritePackage();
		$pack->WriteBegin(GameConst::CMD_S_USER_STAND);
		$pack->WriteByte($seatId);
		$pack->WriteEnd();
		self::_broadcastTable($table, $pack);
	}
	
	
	public static function sendSeatUserBet(Table $table, $mid){
		$betList = $table->record->popUserBufferBetList($mid);
		if (empty($betList)){
			return ;
		}
		
		$pack = new \GSWritePackage();
		$pack->WriteBegin(GameConst::CMD_S_BROADCAST_BET_SIT);
		$pack->WriteByte($table->userList[$mid]['pos']);
		$pack->WriteByte(count($betList));
		foreach ($betList as $areaId=>$betMoney){
			$pack->WriteByte($areaId);
			$pack->WriteInt64($table->record->getSummaryAreaBetMoney($areaId));
		}
		$pack->WriteEnd();
		
		self::_broadcastTable($table, $pack, array($mid=>1));
	}
	
	
	public static function sendStanUserBet(Table $table){
		$userBetList = array();
		foreach ($table->userList as $mid=>$minfo){
			if ($minfo['type'] != User::USER_TYPE_BET_STAND){
				continue;
			}
			$tmpBetList = $table->record->popUserBufferBetList($mid);
			if (empty($tmpBetList)){
				continue;
			}
			$userBetList[$mid] = $tmpBetList;
		}
		
		if (empty($userBetList)){
			return ;
		}
		
		foreach ($table->userList as $mid=>$minfo){
			if (UserPool::checkUserOffline($table->rid, $mid)){
				continue;
			}
			
			$areaList = array();
			foreach ($userBetList as $betMid=>$betList){
				if ($mid == $betMid){
					continue;
				}
				foreach ($betList as $areaId=>$betMoney){
					if (!isset($areaList[$areaId])){
						$areaList[$areaId] = 0;
					}
					$areaList[$areaId] += $betMoney;
				}
			}
			
			$pack = new \GSWritePackage();
			$pack->WriteBegin(GameConst::CMD_S_BROADCAST_BET_STAND);
			$pack->WriteByte(count($areaList));
			foreach ($areaList as $areaId=>$betMoney){
				$pack->WriteByte($areaId);
				$pack->WriteInt64($table->record->getSummaryAreaBetMoney($areaId));
			}
			$pack->WriteEnd();
			self::_sendUser($mid, $pack);
		}
	}
	
	public static function sendBroadcastCancelBet(Table $table, $mid){
		$cancelList = $table->record->getUserCancelBetList($mid);
		
		$pack = new \GSWritePackage();
		$pack->WriteBegin(GameConst::CMD_S_BROADCAST_CANCEL_BET);
		
		$cancelAreaCount = count($cancelList);
		$pack->WriteByte($cancelAreaCount);
		foreach ($cancelList as $areaId=>$betMoney){
			$areaSumMoney = $table->record->getSummaryAreaBetMoney($areaId);
			$pack->WriteByte($areaId);
			$pack->WriteInt64($areaSumMoney);
		}
		
		$pack->WriteEnd();
		self::_broadcastTable($table, $pack, array($mid=>1));
	}
	
	
	public static function sendUserChat(Table $table, $mid, $msg){
		$user = UserPool::getUser($mid);
		
		$pack = new \GSWritePackage();
		$pack->WriteBegin(GameConst::CMD_S_CHAT);
		$pack->WriteInt($mid);
		$pack->WriteString($user->name);
		$pack->WriteString($msg);
		$pack->WriteByte($table->userList[$mid]['pos']);
		$pack->WriteEnd();
		
		self::_broadcastTable($table, $pack);
	}
	
	public static function sendUserMagicFace(Table $table, $mid, $gid){
		$seatId = $table->userList[$mid]['pos'];
		
		$pack = new \GSWritePackage();
		$pack->WriteBegin(GameConst::CMD_S_MAGIC_FACE);
		$pack->WriteByte($seatId);
		$pack->WriteInt($gid);
		$pack->WriteEnd();
		
		self::_broadcastTable($table, $pack);
	}
	
	public static function sendSystemMsg(Table $table, $sign){
		$pack =  new \GSWritePackage();
		$pack->WriteBegin(GameConst::CMD_S_SYSTEM_SIGN);
		$pack->WriteByte($sign);
		$pack->WriteEnd();
		self::_broadcastTable($table, $pack);
	}
	
	public static function sendFreindRequest(Table $table, $mid, $seatId, $requestedMid, $requestedSeatId){
		$pack = new \GSWritePackage();
		$pack->WriteBegin(GameConst::CMD_S_FREIND_REQUEST);
		$pack->WriteInt($mid);
		$pack->WriteByte($seatId);
		$pack->WriteInt($requestedMid);
		$pack->WriteByte($requestedSeatId);
		$pack->WriteEnd();
		self::_broadcastTable($table, $pack);
	}
	
	
	public static function sendAcceptFreindRequest(Table $table, $mid, $seatId, $requestMid, $requestSeatId){
		$pack = new \GSWritePackage();
		$pack->WriteBegin(GameConst::CMD_S_ACCEPT_FREIND_REQUEST);
		$pack->WriteInt($mid);
		$pack->WriteByte($seatId);
		$pack->WriteInt($requestMid);
		$pack->WriteByte($requestSeatId);
		$pack->WriteEnd();
		self::_broadcastTable($table, $pack);
	}
	
	public static function sendDelFriend(Table $table, $mid, $seatId, $fmid, $fseateId){
		$pack = new \GSWritePackage();
		$pack->WriteBegin(GameConst::CMD_S_DEL_FRIEND);
		$pack->WriteInt($mid);
		$pack->WriteByte($seatId);
		$pack->WriteInt($fmid);
		$pack->WriteByte($fseateId);
		$pack->WriteEnd();
		self::_broadcastTable($table, $pack);
	}
	
	public static function sendBroadcastActItem(Table $table, $fromSeatId, $toSeatId, $itemId, $costMoney){
		$pack = new \GSWritePackage();
		$pack->WriteBegin(GameConst::CMD_S_BROADCAST_SEND_ACT_ITEM);
		$pack->WriteByte($fromSeatId);
		$pack->WriteByte($toSeatId);
		$pack->WriteInt($itemId);
		$pack->WriteInt($costMoney);
		$pack->WriteEnd();
		self::_broadcastTable($table, $pack);
	}
	
	public static function sendBroadcastSendMoney(Table $table, $fromSeatId, $toSeatId, $money){
		$pack = new \GSWritePackage();
		$pack->WriteBegin(GameConst::CMD_S_BROADCAST_SEND_MONEY);
		$pack->WriteByte($fromSeatId);
		$pack->WriteByte($toSeatId);
		$pack->WriteInt($money);
		$pack->WriteEnd();
		self::_broadcastTable($table, $pack);
	}
	
	private static function _sendUser($mid, \GSWritePackage $pack){
		$connInfo = GlobalData::getConnectInfoWithMid($mid);
		if (!is_array($connInfo)){
			return ;
		}
		if (!ServerManager::getInstance()->swoole->exist($connInfo['fd'])){
			return ;
		}
		
		ServerManager::getInstance()->swoole->send($connInfo['fd'], $pack->GetPacketBuffer());
	}
	
	private static function _broadcastTable(Table $table, \GSWritePackage $pack, array $filterList=array()){
		foreach ($table->userList as $mid=>$minfo){
			if (isset($filterList[$mid])){
				continue;
			}
			if (UserPool::checkUserOffline($table->rid, $mid)){
				continue;
			}
			
			self::_sendUser($mid, $pack);
		}
	}
	
}


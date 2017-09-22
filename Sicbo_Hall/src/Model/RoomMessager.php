<?php
namespace Model;

class RoomMessager{
	public static function sendGameReady(Room $room){
		$pack = new \GSWritePackage();
		$pack->WriteBegin(GameConst::CMD_S_GAME_READY);
		$pack->WriteByte(0);
		$pack->WriteEnd();
		
		self::_broadcastRoom($room, $pack);
	}
	
	public static function sendGameBetStart(Room $room){
		$tableList = TablePool::getTableList($room->rid);
		
		foreach ($tableList as $table){
			foreach ($table->userList as $mid=>$minfo){
				if (UserPool::checkUserOffline($room->rid, $mid)){
					continue;
				}
				
				$lastBetRecord = $table->record->getUserHistoryBetList($mid);
				$showRepeatBetSt = empty($lastBetRecord) ? 0 : 1;
				
				$pack = new \GSWritePackage();
				$pack->WriteBegin(GameConst::CMD_S_GAME_BET_START);
				$pack->WriteByte($showRepeatBetSt);
				$pack->WriteEnd();
				
				self::_sendUser($mid, $pack);
			}
		}
	}
	
	public static function sendGameBetEnd(Room $room, $isShowRollDice){
		$pack = new \GSWritePackage();
		$pack->WriteBegin(GameConst::CMD_S_GAME_BET_END);
		
		if (true == $isShowRollDice){
			$maxBetUserInfo = $room->getMaxBetMoneyUserInfo();
			$user = UserPool::getUser($maxBetUserInfo['mid']);
			$pack->WriteByte(1);
			$pack->WriteInt($maxBetUserInfo['mid']);
			$pack->WriteString($user->name);
			$pack->WriteString($user->url);
			$pack->WriteInt64($maxBetUserInfo['money']);
		}else{
			$pack->WriteByte(0);
			$pack->WriteInt(0);
			$pack->WriteString('default');
			$pack->WriteString('default');
			$pack->WriteInt64(0);
		}

		$pack->WriteEnd();
		
		self::_broadcastRoom($room, $pack);
	}
	
	public static function sendGameResult(Room $room){
		$pack = new \GSWritePackage();
		$pack->WriteBegin(GameConst::CMD_S_GAME_RESULT);

		$pack->WriteByte(count($room->dice->diceList));
		foreach ($room->dice->diceList as $dice){
			$pack->WriteByte($dice);
		}

		$pack->WriteEnd();
		
		self::_broadcastRoom($room, $pack);
	}
	
	
	public static function sendGameReward(Room $room){
		$tableList = TablePool::getTableList($room->rid);
		
		foreach ($tableList as $table){
			foreach ($table->userList as $mid=>$minfo){
				if (UserPool::checkUserOffline($room->rid, $mid)){
					continue;
				}
				
				$user = UserPool::getUser($mid);
				$pack = new \GSWritePackage();
				$pack->WriteBegin(GameConst::CMD_S_GAME_REWARD);
				
				$pack->WriteByte(count($room->dice->areaList));
				foreach ($room->dice->areaList as $areaId=>$areaInfo){
					$pack->WriteByte($areaId);
				}
				
				$upsetList = $table->record->getUserUpsetList($mid);;
				$pack->WriteByte(count($upsetList));
				foreach ($upsetList as $areaId){
					$pack->WriteByte($areaId);
				}
				
				$winMoney = $table->record->getUserWinMoney($mid);
				$pack->WriteInt64($winMoney);
				$pack->WriteInt64($user->money);
				
				$rewardPositionList = array();
				$tableAreaRewardList = $table->record->getRewardList();
				foreach ($tableAreaRewardList as $tmpMid=>$tmpRrewardList){
					$position = $table->getRewardPosition($tmpMid, $mid);
					foreach ($tmpRrewardList as $areaId=>$tmpMoney){
						if (!isset($rewardPositionList[$areaId.'_'.$position])){
							$rewardPositionList[$areaId.'_'.$position] = array('areaId'=>$areaId, 'pos'=>$position, 'money'=>0);
						}
						$rewardPositionList[$areaId.'_'.$position]['money'] += $tmpMoney;
					}
				}
				$pack->WriteShort(count($rewardPositionList));
				foreach ($rewardPositionList as $reward){
					$pack->WriteByte($reward['areaId']);
					$pack->WriteByte($reward['pos']);
					$pack->WriteInt64($reward['money']);
				}
				
				
				$lotteryPostionRewardList = array();
				$lotteryRewardList = array();	//{mid:money, }
				foreach ($room->lottery->rewardUserList as $tmpMid=>$tmpMoney){
					$position = $table->getRewardPosition($tmpMid, $mid);
					if (!isset($lotteryPostionRewardList[$position])){
						$lotteryPostionRewardList[$position] = 0;
					}
					$lotteryPostionRewardList[$position] += $tmpMoney;
				}
				$pack->WriteInt64($room->lottery->moneyAddtion);
				$pack->WriteByte(count($lotteryPostionRewardList));
				foreach ($lotteryPostionRewardList as $position=>$money){
					$pack->WriteByte($position);
					$pack->WriteInt64($money);
				}
				$pack->WriteInt64($room->lottery->money);
				
				$winTopList = $table->record->getWinUserTopList(5);
				
				$pack->WriteByte(count($winTopList));
				foreach ($winTopList as $tmpMid=>$tmpWinMoney){
					$user = UserPool::getUser($tmpMid);
					$pack->WriteInt($user->mid);
					$pack->WriteString($user->name);
					$pack->WriteString($user->url);
					$pack->WriteInt64($tmpWinMoney);
				}
				
				$seatUserList = array();
				foreach ($table->betSeatList as $tmpSeatId=>$tmpMid){
					if ($tmpMid == User::MID_NONE){
						continue;
					}
					$tmpUser = UserPool::getUser($tmpMid);
					$seatUserList[$tmpMid] = array(
							'seatId'=>$tmpSeatId,
							'money'=>$tmpUser->money,
							'winMoney'=>$table->record->getUserWinMoney($tmpMid),
					);
				}
				$pack->WriteByte(count($seatUserList));
				foreach ($seatUserList as $tmpMid=>$tmpMinfo){
					$pack->WriteByte($tmpMinfo['seatId']);
					$pack->WriteInt64($tmpMinfo['money']);
					$pack->WriteInt64($tmpMinfo['winMoney']);
				}
				
				$betList = $table->record->getUserBetList($mid);
				$betAreaCount = count($betList);
				$pack->WriteByte($betAreaCount);
				foreach ($betList as $areaId=>$areaInfo){
					$pack->WriteByte($areaId);
				}
				
				$pack->WriteEnd();
				
				self::_sendUser($mid, $pack);
			}
		}
		
	}
	
	public static function sendSystemMsg(Room $room, $sign){
		$pack =  new \GSWritePackage();
		$pack->WriteBegin(GameConst::CMD_S_SYSTEM_SIGN);
		$pack->WriteByte($sign);
		$pack->WriteEnd();
		self::_broadcastRoom($room, $pack);
	}
	
	private static function _sendUser($mid, \GSWritePackage $pack){
		$conInfo = GlobalData::getConnectInfoWithMid($mid);
		if (!is_array($conInfo)){
			return ;
		}
		if (!ServerManager::getInstance()->swoole->exist($conInfo['fd'])){
			return ;
		}
		
		ServerManager::getInstance()->swoole->send($conInfo['fd'], $pack->GetPacketBuffer());
	}
	
	
	private static function _broadcastRoom(Room $room, \GSWritePackage $pack){
		$tableList = TablePool::getTableList($room->rid);
		foreach ($tableList as $table){
			foreach ($table->userList as $mid=>$minfo){
				if (UserPool::checkUserOffline($room->rid, $mid)){
					continue;
				}
				
				self::_sendUser($mid, $pack);
			}
		}
	}
	
	
}

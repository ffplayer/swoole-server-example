<?php
namespace Model;

class Table{
	
	const TABLE_POSION_STAND = 51;
	const TABLE_POSION_SELF_ASSET = 52;
	
	const NET_WORK_OFFLINE = 0;
	const NET_WORK_ONLINE = 1;
	
	public $rid;
	
	public $tid;
	
	//下注座位列表, {seatId: mid, ...}
	public $betSeatList;
	
	
	//玩家列表, {mid:{'type': type, 'pos'=>pos}, ...}
	public $userList = array();

	
	public $record;
	
	
	public function __construct($rid, $tid){
		$this->rid = $rid;
		$this->tid = $tid;
		$this->betSeatList = array(
				1=>User::MID_NONE,
				2=>User::MID_NONE,
				3=>User::MID_NONE,
				4=>User::MID_NONE,
				5=>User::MID_NONE,
				6=>User::MID_NONE,
				7=>User::MID_NONE,
				8=>User::MID_NONE,
		);
		$this->record = new TableRecord();
	}
	
	
	
	//获取桌子内总人数
	public function getUserCount(){
		return count($this->userList);
	}
	
	
	//获取桌子内站立下注人数
	public function getStandBetUserCount(){
		$count = 0;
		foreach ($this->userList as $mid=>$minfo){
			if ($minfo['type'] != User::USER_TYPE_BET_STAND){
				continue;
			}
			$count++;
		}
		return $count;
	}
	
	//获取旁观玩家列表
	public function getStandBetUserList($page, $pageSize){
		$minIndex = ($page -1) * $pageSize;
		$maxIndex = $page * $pageSize - 1;
		
		$index = 0;
		$list = array();
		foreach ($this->userList as $mid=>$minfo){
			if ($minfo['type'] != User::USER_TYPE_BET_STAND){
				continue;
			}
			if ($index < $minIndex || $index > $maxIndex){
				$index++;
				continue;
			}
			$index++;
			$list[] = $mid;
		}
		return $list;
	}
	
	/**
	 * 获取用户奖励发放位置
	 * @param unknown $mid
	 * @param unknown $assetMid
	 */
	public function getRewardPosition($mid, $assetMid=null){
		if (!isset($this->userList[$mid])){
			return self::TABLE_POSION_STAND;
		}
		
		if ($this->userList[$mid]['type'] == User::USER_TYPE_BET_SIT){
			return $this->userList[$mid]['pos'];
		}
		
		if (!is_null($assetMid) && $mid == $assetMid){
			return self::TABLE_POSION_SELF_ASSET;
		}
		return self::TABLE_POSION_STAND;
	}
	
	
	//添加用户
	public function addUser($mid){
		if (isset($this->userList[$mid])){
			return ;
		}
		
		$this->userList[$mid] = array('type'=>User::USER_TYPE_BET_STAND, 'pos'=>self::TABLE_POSION_STAND);
		
		$this->updateMongoRoomInfo();
		$this->updateMongoUserInfo($mid);
		
		TablePool::updateCombineTable($this->rid);
		
		TableMessager::sendStandUserMessage($this);
		return ;
	}
	
	
	//删除用户
	public function delUser($mid){
		if (!isset($this->userList[$mid])){
			return ;
		}
		if ($this->userList[$mid]['type'] == User::USER_TYPE_BET_SIT){
			$this->userStandUp($mid);
		}
		unset($this->userList[$mid]);
		$this->record->delUserHistoryBetInfo($mid);
		$this->record->clearNoBetUser($mid);
		
		TablePool::updateCombineTable($this->rid);
		
		$this->updateMongoRoomInfo();
		$this->delMongoUserInfo($mid);
		
		TableMessager::sendStandUserMessage($this);
	}
	
	//用户坐下
	public function userSitDown($mid, $seatId){
		if (!isset($this->userList[$mid])){
			return false;
		}
		
		if ($this->userList[$mid]['type'] == User::USER_TYPE_BET_SIT){
			return false;
		}
		
		if (!isset($this->betSeatList[$seatId])){
			return false;
		}
		
		if ($this->betSeatList[$seatId] != User::MID_NONE){
			return false;
		}
		
		$user = UserPool::getUser($mid);
		$sitMoney = Config::$game['aRoomCfg'][$this->rid]['sitMoney'];
		if ($sitMoney != -1){
			if ($user->money < $sitMoney){
				$user->loadMServer();//从mserver更新
			}
			if ($user->money < $sitMoney){
				return false;
			}
		}
		
		$this->userList[$mid]['type'] = User::USER_TYPE_BET_SIT;
		$this->userList[$mid]['pos'] = $seatId;
		$this->betSeatList[$seatId] = $mid;
		
		//更新mongo数据
		$this->updateMongoUserInfo($mid);
		
		TableMessager::sendStandUserMessage($this);
		TableMessager::sendUserSitDown($this, $mid, $seatId);
		return true;
	}
	
	
	//用户站起
	public function userStandUp($mid){
		if (!isset($this->userList[$mid])){
			return false;
		}
		
		if ($this->userList[$mid]['type'] != User::USER_TYPE_BET_SIT){
			return false;
		}
		
		$seatId = $this->userList[$mid]['pos'];
		$this->userList[$mid]['type'] = User::USER_TYPE_BET_STAND;
		$this->userList[$mid]['pos'] = self::TABLE_POSION_STAND;
		$this->betSeatList[$seatId] = User::MID_NONE;
		
		//更新mongo数据
		$this->updateMongoUserInfo($mid);
		
		
		TableMessager::sendStandUserMessage($this);
		TableMessager::sendUserStandUp($this, $seatId);
		return $seatId;
	}
	
	
	public function bet($mid, $areaId, $money){
		$room = RoomPool::getRoom($this->rid);
		if ($room->status != Room::ROOM_ST_BET){
			return GameConst::BET_RET_TIME_FAIL;
		}
		
		if (!isset($this->userList[$mid])){
			return GameConst::BET_RET_ERROR_OTHER;
		}
		
		if ($money <= 0){
			return GameConst::BET_RET_ERROR_OTHER;
		}
		
		$userBetMoney = $this->record->getUserBetMoney($mid);
		if ($userBetMoney + $money > Config::$game['aRoomCfg'][$this->rid]['maxBet']){
			return GameConst::BET_RET_MAX_MONEY;
		}
		
		$user = UserPool::getUser($mid);
		if (false == $user->decMoney($money, GameConst::MONEY_SOURCE_BET, $this->rid)){
			return GameConst::BET_RET_MONEY_NOT_ENOUGH;
		}
		
		$this->record->addBetRecord($mid, $areaId, $money);
		
		$termNo = $room->getTermNo();
		$mfDetailArr = array('rid'=>$this->rid, 'area'=>$areaId);
		ServerManager::getInstance()->transit->mf(TSWOOLE_SID, $mid, 'sicbo_money', array('type'=>GameConst::MF_MONEY_TYPE_BET, 'term'=>$termNo, 'money'=>-$money, 'desc'=>json_encode($mfDetailArr)));
		return GameConst::BET_RET_OK;
	}
	
	
	public function repeatBet($mid){
		$room = RoomPool::getRoom($this->rid);
		if ($room->status != Room::ROOM_ST_BET){
			return GameConst::BET_RET_TIME_FAIL;
		}
		
		if (!isset($this->userList[$mid])){
			return GameConst::BET_RET_ERROR_OTHER;
		}
		
		$lastBetList = $this->record->getUserHistoryBetList($mid);
		if (empty($lastBetList)){
			return GameConst::BET_RET_ERROR_OTHER;
		}
		
		$lastBetMoney = $this->record->getUserHistoryBetMoney($mid);
		$userBetMoney = $this->record->getUserBetMoney($mid);
		if ($userBetMoney + $lastBetMoney > Config::$game['aRoomCfg'][$this->rid]['maxBet']){
			return GameConst::BET_RET_MAX_MONEY;
		}
		
		$user = UserPool::getUser($mid);
		if (false == $user->decMoney($lastBetMoney, GameConst::MONEY_SOURCE_BET, $this->rid)){
			return GameConst::BET_RET_MONEY_NOT_ENOUGH;
		}
		
		foreach ($lastBetList as $areaId=>$betMoney){
			$this->record->addBetRecord($mid, $areaId, $betMoney);
		}
		
		$termNo = $room->getTermNo();
		$mfDetailArr = array('rid'=>$this->rid, 'areaList'=>$lastBetList);
		ServerManager::getInstance()->transit->mf(TSWOOLE_SID, $mid, 'sicbo_money', array('type'=>GameConst::MF_MONEY_TYPE_REPEAT_BET, 'term'=>$termNo, 'money'=>-$lastBetMoney, 'desc'=>json_encode($mfDetailArr)));
		
		return GameConst::BET_RET_OK;
	}
	
	public function cancelBet($mid){
		$room = RoomPool::getRoom($this->rid);
		if ($room->status != Room::ROOM_ST_BET){
			return GameConst::CANCEL_BET_ERR_ROOM_ST;
		}
		
		$betList = $this->record->getUserBetList($mid);
		$betMoney = $this->record->getUserBetMoney($mid);
		if (empty($betList) || $betMoney == 0){
			return GameConst::CANCEL_BET_ERR_NO_BET;
		}
		
		$user = UserPool::getUser($mid);
		if (false == $user->addMoney($betMoney, GameConst::MONEY_SOURCE_CANCEL_BET, $this->rid)){
			return GameConst::CANCEL_BET_ERR_MONEY;
		}
		
		$this->record->cancelBetRecord($mid);
		
		$termNo = $room->getTermNo();
		$mfDetailArr = array('rid'=>$this->rid, 'cancelList'=>$betList);
		ServerManager::getInstance()->transit->mf(TSWOOLE_SID, $mid, 'sicbo_money', array('type'=>GameConst::MF_MONEY_CANCEL_BET, 'term'=>$termNo, 'money'=>$betMoney, 'desc'=>json_encode($mfDetailArr)));
		
		return GameConst::CANCEL_BET_OK;
	}
	
	
	public function gameReady(){
		$this->record->refresh();
	}
	
	
	public function gameStartBet(){
	}
	
	
	public function gameEndBet(){
	}
	
	public function gameReset(){
		$nobetList = $this->record->getNoBetList();
		foreach ($nobetList as $mid=>$minfo){
			if ($minfo['count'] < Config::$game['handsFree']){
				continue;
			}
			UserMessager::sendSystemMsg($mid, GameConst::SYSTEM_SIGN_USER_EXIT_NO_BET);
			if ($minfo['count'] - Config::$game['handsFree'] > 10){
				UserPool::userExit($this, $mid);
			}
		}
	}
	
	public function gameReward(){
		$room = RoomPool::getRoom($this->rid);
		
		foreach ($this->userList as $mid=>$minfo){
			$betList = $this->record->getUserBetList($mid);
			if (empty($betList)){
				$this->record->addNoBetUser($mid);
				continue;
			}
			
			$aLCInfo = array();
			
			$sumBet = 0;
			$sumRewardMoney = 0;
			foreach ($betList as $areaId=>$betMoney){
				$areaRewardMoney = 0;
				$isUpset = false;
				$sumBet += $betMoney;
				
				$lcBetKey = LCHelp::getKeyBetArea($areaId);
				$aLCInfo[$lcBetKey] = $betMoney;
				
				if (!isset($room->dice->areaList[$areaId])){
					continue;
				}
			
				$areaRewardMoney = $betMoney * $room->dice->areaList[$areaId]['odd'];
				if (isset($room->dice->upsetList[$areaId])){
					$isUpset = true;
					$areaRewardMoney *= 2;
				}
			
				$areaRewardMoney += $betMoney;
			
				$lotteryCMoney = 0;
				$sysCMoney = 0;
				$betWinMoney = $areaRewardMoney - $betMoney;
				if ($betWinMoney > 0){
					$lotteryCMoney = ceil($betWinMoney * Config::$game['recover']['tolottery'] / 100);
					$sysCMoney = ceil($betWinMoney * Config::$game['recover']['tosystem'] / 100);
				}
			
				$areaRewardMoney = $areaRewardMoney - $lotteryCMoney - $sysCMoney;
			
				$sumRewardMoney += $areaRewardMoney;
				$room->lottery->addLotteryMoney($lotteryCMoney);
				$this->record->addRewardRecord($mid, $areaId, $areaRewardMoney, $isUpset, $sysCMoney, $lotteryCMoney);
				
				$lcRewardKey = LCHelp::getKeyBetRewardArea($areaId);
				$aLCInfo[$lcRewardKey] = $areaRewardMoney;
				if ($isUpset){
					$aLCInfo[LCHelp::LC_KEY_BET_REWARD_UPSET_SUM] = $areaRewardMoney;
				}
			}
			$user = UserPool::getUser($mid);
			$user->addMoney($sumRewardMoney, GameConst::MONEY_SOURCE_BET_REWARD, $this->rid);
			$this->record->clearNoBetUser($mid);
			$aLCInfo[LCHelp::LC_KEY_BET_SUM] = $sumBet;
			$aLCInfo[LCHelp::LC_KEY_BET_REWARD_SUM] = $sumRewardMoney;
			
			$termNo = $room->getTermNo();
			$mfDetailArr = array('rid'=>$this->rid, 'diceList'=>$room->dice->diceList);
			ServerManager::getInstance()->transit->mf(TSWOOLE_SID, $mid, 'sicbo_money', array('type'=>GameConst::MF_MONEY_TYPE_BET_REWARD, 'term'=>$termNo, 'money'=>$sumRewardMoney, 'desc'=>json_encode($mfDetailArr)));
			
			ServerManager::getInstance()->transit->lc($user->sid, $mid, LCHelp::LC_PRIMARY_KEY, $aLCInfo);
			
			if (false == UserPool::checkUserOffline($this->rid, $mid)){
				$user->updateMtkey();
			}
		}
		
		$this->record->setRoomRewardAreaInfo($room->dice->areaList);
		
		$betUserList = $this->record->getBetList();
		foreach ($betUserList as $mid=>$betInfo){
			$user = UserPool::getUser($mid);
			
			$userAreaList = $this->record->getUserAreaList($mid);
			$userAreaJson = json_encode($userAreaList);
			$userWinList = $this->record->getUserWinList($mid);
			$userWinJson = json_encode($userWinList);
			$userBetJson = json_encode($betInfo);
			
			$isBankrupt = ($user->money > Config::$game['aRoomCfg'][$room->rid]['minBet']) ? 0 : 1;
			$isWinAreaTriple = $this->record->checkUserWinAreaType($mid, Dices::DICE_TYPE_TRIPLE);
			
			$info = array(
					'unid'=>$user->unid,
					'mid'=>$mid,
					'startTime'=>$room->startTime,
					'endTime'=>$room->startTime + 40,
					'userRewardInfo'=>$userAreaJson,
					'userWinInfo'=>$userWinJson,
					'ante'=>$room->rid,
					'tid'=>$this->tid,
					'roundId'=>$room->startTime,
					'playerCount'=>count($betUserList),
					'winCoins'=>$this->record->getUserWinMoney($mid),
					'userCoins'=>$user->money,
					'charge'=>$this->record->getUserRecoverMoneyOfSys($mid),
					'isBankrupt'=>$isBankrupt,
					'isTriple'=>$isWinAreaTriple,
					'userBetInfo'=>$userBetJson,
			);
			ServerManager::getInstance()->transit->proc('AddSicboMidPlayLog', array_values($info));
		}
	}
	
	
	public function merget(Table $otherTable){
		foreach ($otherTable->userList as $mid=>$minfo){
			$this->addUser($mid);
		}
	}
	
	public function updateMongoRoomInfo(){
		RoomPool::getRoom($this->rid)->updateMongoRoomInfo();
	}
	
	public function updateMongoUserInfo($mid){
		$userMongoInfo = array(
				'rid'=>$this->rid,
				'sicbotid'=>$this->tid,
				'mid'=>$mid,
				'st'=>$this->userList[$mid]['type'],
				'pos'=>$this->userList[$mid]['pos']
		);
		ServerManager::getInstance()->mongo->update(MongoTable::tableNameUser(), array('mid'=>$mid, 'rid'=>$this->rid), $userMongoInfo);
	}
	
	public function delMongoUserInfo($mid){
		ServerManager::getInstance()->mongo->delete(MongoTable::tableNameUser(), array('mid'=>$mid, 'rid'=>$this->rid), 1);
	}
}
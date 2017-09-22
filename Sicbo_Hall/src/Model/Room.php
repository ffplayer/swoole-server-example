<?php
namespace Model;

class Room{
	
	const ROOM_ST_READY = 0;
	const ROOM_ST_BET = 1;
	const ROOM_ST_RESULT = 2;
	const ROOM_ST_ROLL_DICE = 3;
	
	//房间id
	public $rid;
	
	//本轮游戏开始时间
	public $startTime = 0;
	
	//当前游戏状态
	public $status = self::ROOM_ST_READY;
	
	//当前状态开始时间
	public $statusTime = 0;
	
	//骰子
	public $dice;
	
	//彩池
	public $lottery;
	
	//结果展示定时器id
	public $resultTimerId = null;
	
	//
	public $todayTime = 0;
	public $todayInMoney = 0;
	public $todaySendMoney = 0;
	
	public function __construct($rid){
		$this->rid = $rid;
		$this->dice = new Dices();
		$this->lottery = new Lottery($this->rid);
	}
	
	public function getUserCount(){
		$count = 0;
		foreach (TablePool::getTableList($this->rid) as $table){
			$count += $table->getUserCount();
		}
		return $count;
	}
	
	public function getTableCount(){
		$tableList = TablePool::getTableList($this->rid);
		return count($tableList);
	}
	
	public function updateResultTimer($timerId){
		$this->resultTimerId = $timerId;
	}
	
	//重复押注记录
	public function gameReady(){
		$currentTime = time();
		$this->startTime = $currentTime;
		$this->status = self::ROOM_ST_READY;
		$this->statusTime = $currentTime;
		
		$this->dice->reset();
		$this->lottery->refresh();
		
		if (date('Ymd', $currentTime) !== date('Ymd', $this->todayTime)){
			$this->todayTime = $currentTime;
			$this->todayInMoney = 0;
			$this->todaySendMoney = 0;
		}
		
		//通知桌子游戏准备
		$tableList = TablePool::getTableList($this->rid);
		foreach (TablePool::getTableList($this->rid) as $table){
			$table->gameReady();
		}
	}
	
	public function gameBetStart(){
		$this->status = self::ROOM_ST_BET;
		$this->statusTime = time();
		
		//通知桌子游戏开始下注
		foreach (TablePool::getTableList($this->rid) as $table){
			$table->gameStartBet();
		}
	}
	
	
	public function gameBetEnd(){
		$this->status = self::ROOM_ST_ROLL_DICE;
		$this->statusTime = time();
		
		$areaBetList = $this->getAreaBetList();
		$this->dice->run($areaBetList, $this->rid);
		
		//通知桌子游戏开始下注
		foreach (TablePool::getTableList($this->rid) as $table){
			$table->gameEndBet();
		}
		
		$diceStr = implode(',', $this->dice->diceList);
		$diceType = $this->dice->getResultType();
		ServerManager::getInstance()->transit->proc('AddSicboPlayLog', array(TSWOOLE_SID, $this->startTime, $this->rid, $diceStr, $diceType));
	}
	
	public function gameRollDice(){
		$this->status = self::ROOM_ST_RESULT;
	}
	
	public function gameReward(){
		//通知桌子房间游戏开始下注
		foreach (TablePool::getTableList($this->rid) as $table){
			$table->gameReward();
		}
		
		$betList = $this->getUserBetList();
		$this->lottery->run($this->dice, $betList);
		
		foreach ($this->lottery->rewardUserList as $mid=>$money){
			$user = UserPool::getUser($mid);
			$this->lottery->decLotteryMoney($money);
			$user->addMoney($money, GameConst::MONEY_SOURCE_LOTTERY_REWARD, $this->rid);
			
			$termNo = $this->getTermNo();
			$mfDetailArr = array('rid'=>$this->rid,'diceList'=>$this->dice->diceList);
			ServerManager::getInstance()->transit->mf(TSWOOLE_SID, $mid, 'sicbo_money', array('type'=>GameConst::MF_MONEY_LOTTERY_REWARD, 'term'=>$termNo, 'money'=>$money, 'desc'=>json_encode($mfDetailArr)));
			ServerManager::getInstance()->transit->lc($user->sid, $mid, LCHelp::LC_PRIMARY_KEY, array(LCHelp::LC_KEY_LOTTERY_REWARD=>$money));
		}
		
		if (!empty($this->lottery->rewardUserList)){
			$diceStr = implode(',', $this->dice->diceList);
			$lotteryUserStr = implode(',', array_keys($this->lottery->rewardUserList));
			$lotteryRewardStr = implode(',', array_values($this->lottery->rewardUserList));
			ServerManager::getInstance()->transit->proc('AddSicboLotteryPoolLog', array(TSWOOLE_SID, $this->startTime, $this->rid, $diceStr, $lotteryUserStr, $lotteryRewardStr));
		}
		
		$this->updateMongoRoomInfo();
	}
	
	public function gameReset(){
		$termNo = $this->getTermNo();
		
		//上报本局结果
		$inMoney = 0;
		$sendMoney = 0;
		$tableList = TablePool::getTableList($this->rid);
		foreach ($tableList as $table){
			$inMoney += $table->record->getSummaryBetMoney();
			$sendMoney += $table->record->getRewardMoney();
			$sendMoney += $this->lottery->getLotteryRewardMoney();
		}
		
		$this->todayInMoney += $inMoney;
		$this->todaySendMoney += $sendMoney;
		
		$detailData = array();
		$detailData['rid'] = $this->rid;
		$detailData['diceList'] = $this->dice->diceList;
		ServerManager::getInstance()->transit->mf(TSWOOLE_SID, 0, 'sicbo_dice', array('term'=>$termNo, 'inmoney'=>$inMoney,'sendMoney'=>$sendMoney, 'desc'=>json_encode($detailData)));
		
		
		UserPool::clearOfflineUser($this->rid);
		$combineTableRet = TablePool::combineTable($this->rid);
		//通知桌子房间游戏开始下注
		foreach (TablePool::getTableList($this->rid) as $table){
			$table->gameReset();
		}
		return array('combineTable'=>$combineTableRet);
	}
	
	public function getTermNo(){
		return $this->startTime;
	}
	
	public function getAreaBetList(){
		$list = array();
		foreach (TablePool::getTableList($this->rid) as $table){
			$tAreaBetList = $table->record->getSummaryAreaBetList();
			foreach ($tAreaBetList as $areaId=>$money){
				if (!isset($list[$areaId])){
					$list[$areaId] = 0;
				}
				$list[$areaId] += $money;
			}
		}
		return $list;
	}
	
	
	public function getUserBetList(){
		$list = array();
		foreach (TablePool::getTableList($this->rid) as $table){
			$tableBetList = $table->record->getBetList();
			foreach ($tableBetList as $mid=>$betList){
				$list[$mid] = $betList;
			}
		}
		return $list;
	}
	
	public function checkRollDiceButtonShow(){
		if (Config::$game['aRoomCfg'][$this->rid]['betShowBtn'] == -1){
			return false;
		}
		
		$maxBetUserInfo = $this->getMaxBetMoneyUserInfo();
		if ($maxBetUserInfo['mid'] == User::MID_NONE){
			return false;
		}
		
		
		if ($maxBetUserInfo['money'] < Config::$game['aRoomCfg'][$this->rid]['betShowBtn']) {
			return false;
		}
		
		return true;
	}
	
	public function getMaxBetMoneyUserInfo(){
		$info = array('mid'=>User::MID_NONE, 'money'=>0);
		
		foreach (TablePool::getTableList($this->rid) as $table){
			$tMaxInfo = $table->record->getMaxBetUserInfo();
			if ($tMaxInfo['mid'] == User::MID_NONE){
				continue;
			}
			if ($tMaxInfo['money'] > $info['money']){
				$info['mid'] = $tMaxInfo['mid'];
				$info['money'] = $tMaxInfo['money'];
				continue;
			}
			continue;
		}
		return $info;
	}
	
	public function updateMongoRoomInfo(){
		$rooomInfo = array(
				'rid'=>$this->rid,
				'userCount'=>$this->getUserCount(),
				'lotteryMoney'=>$this->lottery->money
		);
		ServerManager::getInstance()->mongo->update(MongoTable::tableNameRoom(), array('rid'=>$this->rid), $rooomInfo);
	}
	
	public function getMongoRoomInfo(){
		$ret = ServerManager::getInstance()->mongo->findOne(MongoTable::tableNameRoom(), array('rid'=>$this->rid));
		if (!is_array($ret)){
			return false;
		}
		if (!isset($ret['lotteryMoney'])){
			$ret['lotteryMoney'] = 0;
		}
		return $ret;
	}
}
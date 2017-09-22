<?php
namespace Model;

class Lottery{
	
	const MIN_BET = 10000;
	
	public $roomId;
	
	public $money = 0;
	
	public $moneyAddtion = 0;
	
	public $moneyDec = 0;
	
	public $rewardAreaId = -1;
	
	//{mid:money, ...}
	public $rewardUserList = array();
	
	public function __construct($roomId){
		$this->roomId = $roomId;
	}
	
	public function refresh(){
		$this->moneyAddtion = 0;
		$this->moneyDec = 0;
		$this->rewardAreaId = -1;
		$this->rewardUserList = array();
	}
	
	public function addLotteryMoney($money){
		$this->money += $money;
		$this->moneyAddtion += $money;
	}
	
	public function decLotteryMoney($money){
		if ($this->money < $money){
			return false;
		}
		$this->money -= $money;
		$this->moneyDec += $money;
		return true;
	}
	
	public function getLotteryRewardMoney(){
		return array_sum($this->rewardUserList);
	}
	
	public function updateLotteryMoneyFromMongo(){
		$room = RoomPool::getRoom($this->roomId);
		$ret = $room->getMongoRoomInfo();
		if (!is_array($ret) || !isset($ret['lotteryMoney'])){
			return ;
		}
		$this->money = $ret['lotteryMoney'];
	}
	
	public function run(Dices $dice, array $userBetList){
		foreach (Config::$game['aLottery'] as $areaId=>$info){
			if (isset($dice->areaList[$areaId])){
				$this->rewardAreaId = $areaId;
			}
		}
		
		$rewardBetList = array();
		foreach ($userBetList as $mid=>$betList){
			if (!isset($betList[$this->rewardAreaId])){
				continue;
			}
			if ($betList[$this->rewardAreaId] < Config::$game['aRoomCfg'][$this->roomId]['minBetOfWin']){
				continue;
			}
			$rewardBetList[$mid] = $betList[$this->rewardAreaId];
		}
		
		$rewardAreaBetSum = array_sum($rewardBetList);
		if ($rewardAreaBetSum == 0){
			return ;
		}
		
		$rewardSumMoney = floor($this->money * Config::$game['aLottery'][$this->rewardAreaId]['percent'] / 100);
		if ($rewardSumMoney == 0){
			return ;
		}
		
		
		foreach ($rewardBetList as $mid=>$betMoney){
			$this->rewardUserList[$mid] = floor($rewardSumMoney * ($betMoney/$rewardAreaBetSum));
		}
		
		return ;
	}
	
}
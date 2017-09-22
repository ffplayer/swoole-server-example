<?php
namespace Model;

class Dices{
	const RESULT_TYPE_SMALL = 1;
	const RESULT_TYPE_BIG = 2;
	const RESULT_TYPE_TRIPLE = 3;
	
	const DICE_TYPE_SIZE = 1;			//大小
	const DICE_TYPE_POINT_SUM = 2;		//点数
	const DICE_TYPE_TRIPLE = 3;			//豹子
	const DICE_TYPE_POINT = 4;			//指定骰子
	
	public $diceList = array();
	
	public $areaList = array();
	
	public $upsetList = array();
	
	public $lastDiceList = array();
	
	public function __construct() {}
	
	public function reset(){
		$this->lastDiceList = $this->diceList;
		$this->diceList = array();
		$this->areaList = array();
		$this->upsetList = array();
	}
	
	public function run(array $betList, $rid){
		$diceInfo = $this->_generateDiceInfo($rid);
		$this->diceList = $diceInfo['diceList'];
		$this->areaList = $diceInfo['rewardList'];
		$this->upsetList = self::_calUpset($this->areaList, $betList);
	}
	
	
	public function getResultType(){
		$typeList = array();
		
		foreach ($this->areaList as $areaId=>$areaInfo){
			if (Config::$game['aBet'][$areaId]['type'] == self::DICE_TYPE_TRIPLE){
				$typeList[self::RESULT_TYPE_TRIPLE] = 1;
			}
			if (Config::$game['aBet'][$areaId]['type'] == self::DICE_TYPE_SIZE && Config::$game['aBet'][$areaId]['val'] == 1){
				$typeList[self::RESULT_TYPE_SMALL] = 1;
			}
			if (Config::$game['aBet'][$areaId]['type'] == self::DICE_TYPE_SIZE && Config::$game['aBet'][$areaId]['val'] == 2){
				$typeList[self::RESULT_TYPE_BIG] = 1;
			}
		}
		
		$type = self::RESULT_TYPE_SMALL;
		if (isset($typeList[self::RESULT_TYPE_TRIPLE])){
			$type = self::RESULT_TYPE_TRIPLE;
		}elseif (isset($typeList[self::RESULT_TYPE_BIG])){
			$type = self::RESULT_TYPE_BIG;
		}
		
		return $type;
	}
	
	public static function getBetAreaNameZh($areaId){
		$name = null;
		if (!isset(Config::$game['aBet'][$areaId])){
			return $name;
		}
	
		$type = Config::$game['aBet'][$areaId]['type'];
		$val = Config::$game['aBet'][$areaId]['val'];
		if ($type == Dices::DICE_TYPE_SIZE){
			$name = ($val == 1) ? '小' : '大';
		}if ($type == Dices::DICE_TYPE_POINT_SUM){
			$name = "点数" . $val;
		}if ($type == Dices::DICE_TYPE_TRIPLE){
			if ($val === ''){
				$name = '任意豹子';
			}else{
				$name = '豹子' . $val;
			}
		}elseif ($type == Dices::DICE_TYPE_POINT){
			$name = '骰子' . $val;
		}
		return $name;
	}
	
	//--------------------生产骰子列表  begin-----------------
	private function _generateDiceInfo($rid){
		$diceInfo = null;
		if (TSWOOLE_ENV == 0){
			$diceInfo = $this->_getTestDiceInfo($rid);
		}
		if (is_null($diceInfo)){
			$diceInfo = $this->_getRandomDiceInfo($rid);
		}
		
		sort($diceInfo['diceList'], SORT_NUMERIC);
		return $diceInfo;
	}
	
	private function _getTestDiceInfo($rid){
		$rd = new \Muredis(array('192.168.202.200', 19011));
		$rd->seria = true;
		$str = $rd->hGet('sicboHallDiceTest', $rid);
	
		$diceList = @json_decode($str);
	
		if (null == $diceList){
			return null;
		}
	
		foreach ($diceList as &$dice){
			$dice += 0;
		}
		
		$rewardList = $this->_calRewardAreas($diceList);
		
		return array('diceList'=>$diceList, 'rewardList'=>$rewardList);
	}
	
	private function _getRandomDiceInfo($rid){
		$diceList = array(mt_rand(1,6), mt_rand(1,6), mt_rand(1,6));
		$rewardList = $this->_calRewardAreas($diceList);
		for ($retry=1; $retry<100; $retry++){
			if (false == $this->_checkShield($rid, $rewardList)){
				break;
			}
			$diceList = array(mt_rand(1,6), mt_rand(1,6), mt_rand(1,6));
			$rewardList = $this->_calRewardAreas($diceList);
		}
		return array('diceList'=>$diceList, 'rewardList'=>$rewardList);
	}
	
	private function _checkShield($rid, $rewardList){
		$roomWinMoeny = 0;
		$room = RoomPool::getRoom($rid);
		if (!is_null($room)){
			$roomWinMoeny = ($room->todayInMoney - $room->todaySendMoney);
		}
		
		if (!isset(Config::$game['aRoomCfg'][$rid])){
			return false;
		}
		
		$protectMoney = Config::$game['aRoomCfg'][$rid]['protect_min'];
		$protectArea = explode(',', Config::$game['aRoomCfg'][$rid]['protect_not']);
		
		if ($roomWinMoeny >= $protectMoney){
			return false;
		}
		
		foreach ($protectArea as $areaId){
			if (isset($rewardList[$areaId])){
				return true;
			}
		}
		return false;
	}
	
	//--------------------生产骰子列表  end-----------------
	
	
	//------------------计算开奖结果 begin-----------------
	private static function _calRewardAreas($diceList){
		$areaList = array();
		
		foreach ($diceList as $point){
			if ($point < 1 || $point > 6){
				return $areaList;
			}
		}
		
		$diceTypeInfo = self::_typeAnalyse($diceList);
		
		foreach (Config::$game['aBet'] as $betInfo){
			$tmpId = $betInfo['id'];
			$tmpType = $betInfo['type'];
			$tmpSubType = $betInfo['val'];
			$tmpOdd = $betInfo['odd'];
			if (!isset($diceTypeInfo[$tmpType][$tmpSubType])){
				continue;
			}
			
			$subVale = $tmpId;
			if ($tmpType == self::DICE_TYPE_POINT){
				$subVale = $diceTypeInfo[$tmpType][$tmpSubType];
				$tmpOdd = Config::$game['aFixedDice'][$subVale]['odd'];
			}
			
			$areaList[$tmpId] = array('odd'=>$tmpOdd, 'subVal'=>$subVale);
		}
		return $areaList;
	}
	
	private static function _typeAnalyse(array $diceList){
		$reuslt = array();
	
		$sumPoint = array_sum($diceList);
		$countList = self::_getDiceArrCountList($diceList);
	
		//大小类型
		if (!isset($countList[3])){
			if ($sumPoint >= 4 && $sumPoint <= 10){
				$reuslt[self::DICE_TYPE_SIZE][1] = 1;
			}elseif ($sumPoint >= 11 && $sumPoint <= 17){
				$reuslt[self::DICE_TYPE_SIZE][2] = 2;
			}
		}
	
		//点数类型
		if ($sumPoint >= 4 && $sumPoint <= 17){
			$reuslt[self::DICE_TYPE_POINT_SUM][$sumPoint] = $sumPoint;
		}
	
		//豹子
		if (isset($countList[3])){
			$reuslt[self::DICE_TYPE_TRIPLE][''] = 0;		//任意豹子
			foreach ($countList[3] as $val){
				$reuslt[self::DICE_TYPE_TRIPLE][$val] = $val;
			}
		}
	
		//指定筛子
		foreach ($countList as $count=>$countInfo){
			foreach ($countInfo as $val){
				$reuslt[self::DICE_TYPE_POINT][$val] = $count;
			}
		}
	
		return $reuslt;
	}
	
	private static function _getDiceArrCountList($arr){
		$result = array();
		$numberCountList = array_count_values($arr);
		foreach ($numberCountList as $k=>$num){
			$result[$num][] = $k;
		}
		return $result;
	}
	
	//------------------计算开奖结果 end-----------------
	
	
	//------------------计算暴击 begin-----------------
	private static function _calUpset(array $areaList, array $betList){
		$result = array();
		$areaTypeList = array();
		foreach ($betList as $areaId=>$betMoney){
			$type = Config::$game['aBet'][$areaId]['type'];
			if (false === in_array($type, Config::$game['aUpset'])){
				continue;
			}
				
			if ($betMoney <= 0){
				continue;
			}
				
			if (!isset($areaTypeList[$type])){
				$areaTypeList[$type] = 0;
			}
			$areaTypeList[$type] += $betMoney;
		}
	
		foreach ($betList as $areaId=>$betMoney){
			$type = Config::$game['aBet'][$areaId]['type'];
				
			if (!isset($areaTypeList[$type])){
				continue;
			}
			
			if (!isset($areaList[$areaId])){
				continue;
			}
			
			if (round($betMoney/$areaTypeList[$type], 4) <= round(Config::$game['upVal']/100, 4)){
				$result[$areaId] = 1;
			}
		}
		return $result;
	}
	
	//------------------计算暴击 end-----------------
	
	
}
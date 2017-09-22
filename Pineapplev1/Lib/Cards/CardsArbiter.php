<?php
include_once __DIR__ . '/Card.php';
include_once __DIR__ . '/WayCard.php';
include_once __DIR__ . '/GameCard.php';
include_once __DIR__ . '/CardsBuilder.php';
class CardsArbiter{
	/**
	 * 特殊牌型对应分值配置
	 * @var unknown
	 */
	public static $_specailTypeScore = array(
			WayCard::WAY_ID_HEAD=>array(
					WayCard::TYPE_PAIR=>array(0x6=>1, 0x7=>2, 0x8=>3, 0x9=>4, 0xA=>5, 0xB=>6, 0xC=>7, 0xD=>8, 0xE=>9),
					WayCard::TYPE_THREE_CARDS=>array(0x2=>10, 0x3=>11, 0x4=>12, 0x5=>13, 0x6=>14, 0x7=>15, 0x8=>16, 0x9=>17, 0xA=>18, 0xB=>19, 0xC=>20, 0xD=>21, 0xE=>22),
			),
			WayCard::WAY_ID_MEDIUM=>array(
					WayCard::TYPE_THREE_CARDS=>array('any'=>2),
					WayCard::TYPE_STRAIGHT=>array('any'=>4),
					WayCard::TYPE_FLUSH=>array('any'=>8),
					WayCard::TYPE_FULL_HOUSE=>array('any'=>12),
					WayCard::TYPE_FOUR_CARDS=>array('any'=>20),
					WayCard::TYPE_STRAIGHT_FLUSH=>array('any'=>30),
					WayCard::TYPE_ROYAL_FLUSH=>array('any'=>50),
			),
			WayCard::WAY_ID_TAIL=>array(
					WayCard::TYPE_STRAIGHT=>array('any'=>2),
					WayCard::TYPE_FLUSH=>array('any'=>4),
					WayCard::TYPE_FULL_HOUSE=>array('any'=>6),
					WayCard::TYPE_FOUR_CARDS=>array('any'=>10),
					WayCard::TYPE_STRAIGHT_FLUSH=>array('any'=>15),
					WayCard::TYPE_ROYAL_FLUSH=>array('any'=>25),
			),
	);


	/**
	 * 获取比牌结果
	 * @param array $playerCardInfo, 玩家牌列表， 格式:
	 * 		{
	 * 			id:{
	 * 				'fanRound':round,		//范特西回合数
	 * 				'cardList':{
	 * 					wayId:[card, card, ...],	//牌列表
	 * 					...
	 * 				}, 
	 * 			...
	 * 			}
	 * 		}
	 *
	 * @return array/false,  如果成功返回数组， 失败返回false
	 * 成功时数组格式:
	 * 		{
	 * 		id:{					//对应id
	 * 			isBust:bool,		//是否爆牌
	 * 			isFantasy:bool,		//是否范特西
	 * 			allWinScore:score,	//全胜分数
	 * 			winScoreList:{wayId:score, ...},		//每道的胜负分数列表
	 * 			typeScoreList:{wayId:score, ...}		//每道特殊牌型分数
	 * 			},
	 * 		...
	 * 		}
	 */
	public static function calResult(array $playerCardInfo){
		$playerCounts = count($playerCardInfo);
		if ($playerCounts < 2){
			return false;
		}
		$gameCardObjList = array();
		foreach ($playerCardInfo as $id=>$cardInfo){
			$gameCardObjList[] = CardsBuilder::buildGameCard($id, $cardInfo['fanRound'], $cardInfo['cardList']);
		}

		for ($i=0; $i<$playerCounts; $i++){
			for ($j=$i+1; $j<$playerCounts; $j++){
				self::_calScore($gameCardObjList[$i], $gameCardObjList[$j]);
			}
		}

		foreach ($gameCardObjList as $obj){
			$obj->isCardsFantasy = self::_checkFantasy($obj);
		}
		$result = array();
		foreach ($gameCardObjList as $obj){
			$result[$obj->id]['isBust'] = $obj->isCardsBust;
			$result[$obj->id]['isFantasy'] = $obj->isCardsFantasy;
			$result[$obj->id]['allWinScore'] = $obj->allWinScore;
			$result[$obj->id]['winScoreList'] = $obj->winScoreList;
			$result[$obj->id]['typeScoreList'] = $obj->typeScoreList;
			$result[$obj->id]['sumScore'] = $obj->getSumScore();
				
		}
		return $result;
	}
	
	/**
	 * 获取玩家牌信息
	 * @param array $cardList, [cardValue, cardValue, ....]
	 * 
	 * @return array
	 * 		{
	 * 			'isBust'=>bool,		//是否爆牌
	 * 			'wayInfoList':{
	 * 					wayId:{
	 * 						'type':type,							//类型
	 * 						'typeExtScore':score,					//类型附加分
	 * 						'list':[cardValue, cardValue, ....]		//牌值列表， 已排序
	 * 					}
	 * 				}
	 * 		}
	 */
	public static function getGameCardInfo(array $cardList){
		$result = array();
		$result['isBust'] = false;
		
		$defalutWayInfo = array('type'=>WayCard::TYPE_HIGH_CARD, 'typeExtScore'=>0, 'list'=>array());
		$result['wayInfoList'] = array(WayCard::WAY_ID_HEAD=>$defalutWayInfo, WayCard::WAY_ID_MEDIUM=>$defalutWayInfo, WayCard::WAY_ID_TAIL=>$defalutWayInfo);
		
		$gameCardObj = CardsBuilder::buildGameCard(0, 0, $cardList);
		
		$result['isBust'] = $gameCardObj->isCardsBust;
		foreach ($result['wayInfoList'] as $wayId=>&$info){
			$info['type'] = $gameCardObj->wayCardList[$wayId]->getType();
			$info['typeExtScore'] = self::_getWaySpecailScore($gameCardObj, $wayId);
			$info['list'] = $gameCardObj->wayCardList[$wayId]->getCardsList();
		}
		
		return $result;
	}
	
	
	/**
	 * 根据牌型进行牌的排序
	 * @param array $cardList, [cardValue, cardValue, ...]
	 * 
	 * @return array [cardValue, cardValue, ...]
	 */
	public static function sortCardList(array $cardList){
		return  CardsBuilder::cardListSort($cardList);
	}

	//------------------------------分数计算	begin------------------------------------

	private static function _calScore(GameCard $obj1, GameCard $obj2){
		$wayIdList = array(WayCard::WAY_ID_HEAD, WayCard::WAY_ID_MEDIUM, WayCard::WAY_ID_TAIL);
		$allWayCount = count($wayIdList);

		$winCount1 = 0;
		$winCount2 = 0;
		foreach ($wayIdList as $id){
			$compRet = GameCard::compare($id, $obj1, $obj2);
			//胜负分数
			if ($compRet>0){
				$winCount1++;//胜利次数
				$obj1->winScoreList[$id] += 1;
				$obj2->winScoreList[$id] -= 1;
			}elseif ($compRet < 0){
				$winCount2++;
				$obj1->winScoreList[$id] -= 1;
				$obj2->winScoreList[$id] += 1;
			}

			//特殊牌型分数
			$typeScore1 = self::_getWaySpecailScore($obj1, $id);
			$typeScore2 = self::_getWaySpecailScore($obj2, $id);
			$obj1->typeScoreList[$id] += $typeScore1-$typeScore2;
			$obj2->typeScoreList[$id] += -($typeScore1-$typeScore2);
		}

		//全胜分数
		if ($winCount1 == $allWayCount){
			$obj1->allWinScore += 3;
			$obj2->allWinScore -= 3;
		}
		if ($winCount2 == $allWayCount){
			$obj1->allWinScore -= 3;
			$obj2->allWinScore += 3;
		}
	}

	private static function _getWaySpecailScore(GameCard $obj, $wayId){
		if (!isset(self::$_specailTypeScore[$wayId]) || !isset($obj->wayCardList[$wayId])){
			return 0;
		}
		if (true == $obj->isCardsBust){
			return 0;
		}

		$wayType = $obj->wayCardList[$wayId]->getType();
		if (!isset(self::$_specailTypeScore[$wayId][$wayType])){
			return 0;
		}

		$headFCNumber = $obj->wayCardList[$wayId]->getCardNumber(0);
		if (isset(self::$_specailTypeScore[$wayId][$wayType]['any'])){
			return self::$_specailTypeScore[$wayId][$wayType]['any'];
		}
		if (isset(self::$_specailTypeScore[$wayId][$wayType][$headFCNumber])){
			return self::$_specailTypeScore[$wayId][$wayType][$headFCNumber];
		}
		return 0;
	}

	//------------------------------分数计算	end------------------------------------



	//------------------------------范特西模式计算	begin----------------------------------
	private static function _checkFantasy(GameCard $obj){
		$sumScore = $obj->getSumScore();
		if ($sumScore <= 0){
			return false;
		}

		$headType = $obj->wayCardList[WayCard::WAY_ID_HEAD]->getType();
		$tailType = $obj->wayCardList[WayCard::WAY_ID_TAIL]->getType();
		$headFCNumber = $obj->wayCardList[WayCard::WAY_ID_HEAD]->getCardNumber(0);
		if ($obj->fanRound == 0){
			if ($headType > WayCard::TYPE_PAIR){
				return true;
			}

			if ($headType == WayCard::TYPE_PAIR && $headFCNumber >= Card::FIRST_FANTASY_MIN_PAIRE_NUMBER){
				return true;
			}
		}

		if ($obj->fanRound > 0){
			if ($headType == WayCard::TYPE_THREE_CARDS || $tailType >= WayCard::TYPE_FOUR_CARDS){
				return true;
			}
		}
		return false;
	}

	//------------------------------范特西模式计算	end----------------------------------

}
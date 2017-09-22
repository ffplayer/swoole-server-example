<?php
include_once __DIR__ . '/Card.php';
include_once __DIR__ . '/WayCard.php';
include_once __DIR__ . '/GameCard.php';
include_once __DIR__ . '/Help.php';
class CardsBuilder{
	/**
	 * 建造WayCard对象
	 * @param array $WayCardList  [cardsValue, ...]
	 *
	 * @return WayCard/NULL
	 */
	public static function buildWayCard($id, array $wayCardList){

		$sortCardList = self::cardListSort($wayCardList);

		$type = self::_parseWayType($sortCardList);
		

		$wayObj = new WayCard($id, $type, $sortCardList);

		return $wayObj;
	}


	/**
	 * 建造GameCard对象
	 * @param $id, 所属者id
	 * @param $fanRound, 范特西轮数
	 * @param array $cardInfo  {way:[cardsValue, ...], ...}
	 */
	public static function buildGameCard($id, $fanRound, array $wayCardList){
		$filterList = array();
		//3道是否齐全
		if (!isset($wayCardList[WayCard::WAY_ID_HEAD])){
			return null;
		}
		if (!isset($wayCardList[WayCard::WAY_ID_MEDIUM])){
			return null;
		}
		if (!isset($wayCardList[WayCard::WAY_ID_TAIL])){
			return null;
		}

		$filterList[WayCard::WAY_ID_HEAD] = $wayCardList[WayCard::WAY_ID_HEAD];
		$filterList[WayCard::WAY_ID_MEDIUM] = $wayCardList[WayCard::WAY_ID_MEDIUM];
		$filterList[WayCard::WAY_ID_TAIL] = $wayCardList[WayCard::WAY_ID_TAIL];

		//检测牌值是否合法
		$allcardsList = array_merge($filterList[WayCard::WAY_ID_HEAD], $filterList[WayCard::WAY_ID_MEDIUM], $filterList[WayCard::WAY_ID_TAIL]);
		foreach ($allcardsList as $cardValue){
			if (false == Card::checkCardValueAvail($cardValue)){
				return null;
			}
		}

		//检测牌值唯一
		if (true == Help::checkArrRepeat($allcardsList)){
			return null;
		}

		//生成way对象
		$headWay = self::buildWayCard(WayCard::WAY_ID_HEAD, $filterList[WayCard::WAY_ID_HEAD]);
		$mediumWay = self::buildWayCard(WayCard::WAY_ID_MEDIUM, $filterList[WayCard::WAY_ID_MEDIUM]);
		$tailWay = self::buildWayCard(WayCard::WAY_ID_TAIL, $filterList[WayCard::WAY_ID_TAIL]);

		$obj = new GameCard($id, $fanRound, $headWay, $mediumWay, $tailWay);

		return $obj;
	}

	/**
	 * 牌列表排序
	 * @param unknown $cardList
	 */
	public static function cardListSort($cardList){
		$countList = array();
		foreach ($cardList as $cardValue){
			$number = Card::getCardNumber($cardValue);
			if (!isset($countList[$number])){
				$countList[$number] = 0;
			}
			$countList[$number]++;
		}
	
		usort($cardList, function ($a, $b) use($countList){
			$numberA = Card::getCardNumber($a);
			$numberB = Card::getCardNumber($b);
			$colorA = Card::getCardColor($a);
			$colorB = Card::getCardColor($b);
			$countsA = isset($countList[$numberA]) ? $countList[$numberA] : 0;
			$countsB = isset($countList[$numberB]) ? $countList[$numberB] : 0;
	
			if ($countsA > $countsB){
				return -1;
			}
			if ($countsA < $countsB){
				return 1;
			}
			if ($numberA > $numberB){
				return -1;
			}
			if ($numberA < $numberB){
				return 1;
			}
			if ($colorA > $colorB){
				return -1;
			}
			if ($colorA < $colorB){
				return 1;
			}
			return 0;
		});
			return $cardList;
	}

	//-----------------------解析way牌型 code begin---------------------------
	private static function _parseWayType(&$cardSortList){

		$numberList = array();
		$colorList = array();
		foreach ($cardSortList as $cardValue){
			$numberList[] = Card::getCardNumber($cardValue);
			$colorList[] = Card::getCardColor($cardValue);
		}

		$numberCountList = Help::getArrRepeatCountList($numberList);
		$colorCount = count(array_unique($colorList));
		$isContinuous = Help::checkArrContinue($numberList);
		
		$count1 = isset($numberCountList[1]) ? $numberCountList[1] : 0;
		$count2 = isset($numberCountList[2]) ? $numberCountList[2] : 0;
		$count3 = isset($numberCountList[3]) ? $numberCountList[3] : 0;
		$count4 = isset($numberCountList[4]) ? $numberCountList[4] : 0;

		$type = WayCard::TYPE_HIGH_CARD;
		if (true == self::_isRoyaFlush($colorCount, $isContinuous, $cardSortList)){
			$type = WayCard::TYPE_ROYAL_FLUSH;
		}elseif (true == self::_isStraightFlush($colorCount, $isContinuous, $cardSortList, $cardSortList)){
			$type = WayCard::TYPE_STRAIGHT_FLUSH;
		}elseif (true == self::_isFourCard($count4)){
			$type = WayCard::TYPE_FOUR_CARDS;
		}elseif (true == self::_isFullHouse($count2, $count3, $count4)){
			$type = WayCard::TYPE_FULL_HOUSE;
		}elseif (true == self::_isFlush($colorCount, $isContinuous, $cardSortList)){
			$type = WayCard::TYPE_FLUSH;
		}elseif (true == self::_isStraight($colorCount, $isContinuous, $cardSortList)){
			$type = WayCard::TYPE_STRAIGHT;
		}elseif (true == self::_isThreeCard($count2, $count3, $count4)){
			$type = WayCard::TYPE_THREE_CARDS;
		}elseif (true == self::_isTwoPair($count2, $count3, $count4)){
			$type = WayCard::TYPE_TWO_PAIR;
		}elseif (true == self::_isPair($count2, $count3, $count4)){
			$type = WayCard::TYPE_PAIR;
		}
		if((2 == $isContinuous) && in_array($type, array(WayCard::TYPE_STRAIGHT, WayCard::TYPE_STRAIGHT_FLUSH))){//如果是A5432 换成 5432A
			array_push($cardSortList, $cardSortList[0]);
			array_shift($cardSortList);
		}
		return $type;
	}

	private static function _isPair($count2, $count3, $count4){
		if ($count2 == 1 && $count3 == 0 && $count4 == 0){
			return true;
		}
		return false;
	}

	private static function _isTwoPair($count2, $count3, $count4){
		if ($count2 >= 2 && $count3 == 0 && $count4 == 0){
			return true;
		}
		return false;
	}

	private static function _isThreeCard($count2, $count3, $count4){
		if ($count2 == 0 && $count3 >= 1 && $count4 == 0){
			return true;
		}
		return false;
	}

	private static function _isFullHouse($count2, $count3, $count4){
		if ($count2 >= 1 && $count3 >= 1 && $count4 == 0){
			return true;
		}
		return false;
	}

	private static function _isFourCard($count4){
		if ($count4 >= 1){
			return true;
		}
		return false;
	}

	private static function _isStraight($colorCount, $isContinuous, $cardSortList){
		$cardCount = count($cardSortList);
		if ($colorCount > 1 && true == $isContinuous && $cardCount >= 5){
			return true;
		}
		return false;
	}

	private static function _isFlush($colorCount, $isContinuous, $cardSortList){
		$cardCount = count($cardSortList);
		if ($colorCount == 1 && false == $isContinuous && $cardCount >= 5){
			return true;
		}
		return false;
	}


	private static function _isStraightFlush($colorCount, $isContinuous, $cardSortList){
		$maxValue = isset($cardSortList[0]) ? $cardSortList[0] : 0x0;
		$maxNumber = Card::getCardNumber($maxValue);
		$cardCount = count($cardSortList);
		if (true == $isContinuous && $colorCount == 1 && ($maxNumber != Card::NUMBER_MAX || $isContinuous == 2) && $cardCount >= 5){
			return true;
		}
		return false;
	}

	private static function _isRoyaFlush($colorCount, $isContinuous, $cardSortList){
		$maxValue = isset($cardSortList[0]) ? $cardSortList[0] : 0x0;
		$maxNumber = Card::getCardNumber($maxValue);
		$cardCount = count($cardSortList);
		if (1 == $isContinuous && $colorCount == 1 && $maxNumber == Card::NUMBER_MAX && $cardCount >= 5){
			return true;
		}
		return false;
	}


	//-----------------------解析way牌型 code end-----------------------------

}
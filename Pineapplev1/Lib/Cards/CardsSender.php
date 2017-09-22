<?php
include_once __DIR__ . '/Card.php';

class CardsSender{
	public static $_cardList = array();
	
	public static $_cardtempList = array();

	/**
	 * 牌局开始， 会重置底牌
	 * @param $tid		//所在桌子id
	 *
	 * @return null
	 */
	public static function start($tid){
		if(self::$_cardtempList[$tid]){
			return self::$_cardList[$tid] = self::$_cardtempList[$tid];
		}
		self::$_cardList[$tid] = self::_getCardsInitData();
	}

	/**
	 * 发牌，同一桌第一次发牌之前，必须调用start进行底盘重置
	 * @param $tid		//桌子id
	 * @param $count	//所要发牌的数量
	 */
	public static function send($tid, $count) {
		$result = array();
		for ($i=0; $i<$count; $i++){
			$result[] = array_shift(self::$_cardList[$tid]);
		}
		return $result;
	}

	/**
	 * 所有发牌结束， 销毁底牌数据以释放内存
	 * @param unknown $tid
	 */
	public static function end($tid){
		unset(self::$_cardList[$tid]);
	}


	private static function _getCardsInitData (){
		$result = array();
		for ($number=Card::NUMBER_MIN; $number<=Card::NUMBER_MAX; $number++){
			$result[] = Card::getCardValue(Card::COLOR_SPADE, $number);
			$result[] = Card::getCardValue(Card::COLOR_HEART, $number);
			$result[] = Card::getCardValue(Card::COLOR_DIAMOND, $number);
			$result[] = Card::getCardValue(Card::COLOR_CLUB, $number);
		}
		shuffle($result);
		return $result;
	}
}
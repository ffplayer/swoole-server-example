<?php
class Card{
	const COLOR_CLUB = 0X1;			//方块
	const COLOR_DIAMOND = 0X2;		//梅花
	const COLOR_HEART = 0X3;		//红桃
	const COLOR_SPADE = 0X4;		//黑桃

	const NUMBER_MAX = 0XE;			//牌面数字最大的为A
	const NUMBER_MIN = 0X2;			//牌面数字最小的为2

	const DEFAULT_ERROR_CARD_VALUE = 0X00;
	const DEFAULT_ERROR_CARD_NUMBER = 0X0;

	const FIRST_FANTASY_MIN_PAIRE_NUMBER = 0xC;	//首次范特西最小对子数字（对Q）

	/**
	 * 通过牌值获取牌面数字
	 * @param $value
	 *
	 * @return $nubmer
	 */
	public static function getCardNumber($value){
		return ($value & 0x0F);
	}


	/**
	 * 检查牌面数字是否在合法范围
	 * @param $number
	 *
	 * @return bool true/false
	 */
	public static function checkCardNumberAvail($number){
		if ($number >= self::NUMBER_MIN && $number <= self::NUMBER_MAX){
			return true;
		}
		return false;
	}


	/**
	 * 通过牌值获取牌面花型
	 * @param $value
	 *
	 * @return $color
	 */
	public static function getCardColor($value){
		return ($value & 0xF0) >> 4;
	}


	/**
	 * 检查牌面花型是否在合法范围
	 * @param $color
	 *
	 * @return bool true/false
	 */
	public static function checkCardColorAvail($color){
		if ($color == self::COLOR_SPADE){
			return true;
		}
		if ($color == self::COLOR_HEART){
			return true;
		}
		if ($color == self::COLOR_DIAMOND){
			return true;
		}
		if ($color == self::COLOR_CLUB){
			return true;
		}
		return false;
	}

	/**
	 * 通过花型和数字获取牌值
	 * @param $color
	 * @param $number
	 *
	 * @return $value
	 */
	public static function getCardValue($color, $number){
		return ($color << 4) | $number;
	}

	/**
	 * 检测牌值是否合法
	 * @param $value
	 * @param $number
	 *
	 * @return bool true/false
	 */
	public static function checkCardValueAvail($value){
		$number = self::getCardNumber($value);
		$color = self::getCardColor($value);
		if (true == self::checkCardNumberAvail($number) && true == self::checkCardColorAvail($color)){
			return true;
		}
		return false;
	}
}
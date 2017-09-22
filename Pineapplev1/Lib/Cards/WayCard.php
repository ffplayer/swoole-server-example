<?php
class WayCard{
	const WAY_ID_HEAD = 1;			//头道
	const WAY_ID_MEDIUM = 2;		//中道
	const WAY_ID_TAIL = 3;			//尾道
	
	const WAY_MAX_CARDS_COUNTS_HEAD = 3;
	const WAY_MAX_CARDS_COUNTS_MEDIUM = 5;
	const WAY_MAX_CARDS_COUNTS_TAIL = 5;
	
	const STATUS_UNFINISH = 0X0;			//牌未完成
	const STATUS_FINISH = 0X1;				//牌未已完成
	
	const TYPE_WIPE_CARD = 0X1;				//杂牌（暂时未启用）
	const TYPE_HIGH_CARD = 0x2;				//高牌
	const TYPE_PAIR = 0x3;					//对子
	const TYPE_TWO_PAIR = 0x4;				//两对
	const TYPE_THREE_CARDS = 0x5;			//三条
	const TYPE_STRAIGHT = 0x6;				//顺子
	const TYPE_FLUSH = 0x7;					//同花
	const TYPE_FULL_HOUSE = 0x8;			//葫芦
	const TYPE_FOUR_CARDS = 0x9;			//金刚
	const TYPE_STRAIGHT_FLUSH = 0xA;		//同花顺
	const TYPE_ROYAL_FLUSH = 0xB;			//皇家同花顺
	
	
	/**
	 * id, 对应头、中、尾
	 * @var $_id
	 */
	private $_id;
	
	/**
	 * 本道牌类型
	 * @var $_type
	 */
	private $_type;
	
	
	/**
	 * 本道牌状态
	 * @var unknown
	 */
	private $_status;
	
	/**
	 * 已排序的牌值列表，
	 * 排序规则：
	 * 1. 按照同牌面数字的数量进行排序， 数量多的排前边；相同数量的按照牌面顺进行排序，数字大的排前边；
	 * 2. 同数字的牌，按照花色进行排序， 花色越大的排前边
	 * @var $_cardsList
	 */
	private $_cardsList;
	
	
	
	public function __construct($id, $type, $sortCardsList){
		$this->_id = $id;
		$this->_type = $type;
		$this->_cardsList = $sortCardsList;
		$this->_status = $this->_calStatus();
	}
	
	/**
	 * 获取道id
	 * @param null
	 * 
	 * @return $id
	 */
	public function getId(){
		return $this->_id;
	}
	
	/**
	 * 获取牌型
	 * @param null
	 * 
	 * @return $type
	 */
	public function getType(){
		if ($this->_status == self::STATUS_UNFINISH){
			return self::TYPE_HIGH_CARD;
		}
		return $this->_type;
	}
	
	/**
	 * 获取状态
	 * @return unknown
	 */
	public function getStatus(){
		return $this->_status;
	}
	
	/**
	 * 获取状态
	 */
	private function _calStatus(){
		$status = self::STATUS_UNFINISH;
		$cardCounts = $this->getCardsCount();
		if ($this->_id == self::WAY_ID_HEAD && $cardCounts == self::WAY_MAX_CARDS_COUNTS_HEAD){
			$status = self::STATUS_FINISH;
		}elseif ($this->_id == self::WAY_ID_MEDIUM && $cardCounts == self::WAY_MAX_CARDS_COUNTS_MEDIUM){
			$status = self::STATUS_FINISH;
		}elseif ($this->_id == self::WAY_ID_TAIL && $cardCounts == self::WAY_MAX_CARDS_COUNTS_TAIL){
			$status = self::STATUS_FINISH;
		}
		return $status;
	}
	
	/**
	 * 获取牌面列表
	 * @param null
	 * 
	 * @return array, format:[cardValue1, cardValue2, ...]
	 */
	public function getCardsList(){
		return $this->_cardsList;
	}
	
	/**
	 * 获取牌的数量
	 */
	public function getCardsCount(){
		return count($this->_cardsList);
	}
	
	/**
	 * 获取指定索引处的牌的综合数值(花色和数字混合形成的)
	 * @param $index
	 * 
	 * @return $value
	 */
	public function getCardValue($index){
		if (!isset($this->_cardsList[$index])){
			return Card::DEFAULT_ERROR_CARD_VALUE;
		}
		return $this->_cardsList[$index];
	}
	
	
	/**
	 * 获取指定索引处的牌面数字
	 * @param $index
	 *
	 * @return $number
	 */
	public function getCardNumber($index){
		$value = $this->getCardValue($index);
		if ($value == Card::DEFAULT_ERROR_CARD_NUMBER){
			return Card::DEFAULT_ERROR_CARD_NUMBER;
		}
		return Card::getCardNumber($value);
	}
	
	
	/**
	 * 对比两个WayCard大小(只比较牌型大小， 其他类如爆牌等条件不考虑)
	 * @param WayCard $obj1
	 * @param WayCard $obj2
	 *
	 * @return 1/0/-1	 如果如果obj1>obj2， 返回1; obj1<obj2, 返回-1; obj1==obj2,返回0;
	 */
	public static function compare(WayCard $obj1, WayCard $obj2){
		$status1 = $obj1->getStatus();
		$status2 = $obj2->getStatus();
		if ($status1 == self::STATUS_UNFINISH || $status2 == self::STATUS_UNFINISH){
			return 0;
		}
		
		$type1 = $obj1->getType();
		$type2 = $obj2->getType();
		if ($type1 > $type2){
			return 1;
		}
		if ($type1 < $type2){
			return -1;
		}
		
		$count1 = $obj1->getCardsCount();
		$count2 = $obj2->getCardsCount();
		$minCount = min($count1, $count2);
		for ($i=0; $i< $minCount; $i++){
			$value1 = $obj1->getCardValue($i);
			$value2 = $obj2->getCardValue($i);
			$number1 = Card::getCardNumber($value1);
			$number2 = Card::getCardNumber($value2);
			if ($number1 > $number2){
				return 1;
			}
			if ($number1 < $number2){
				return -1;
			}
		}
		return 0;
	}
	
	/**
	 * 获取牌型的中文名
	 * @param $type
	 * 
	 * @return $name
	 */
	public static function getTypeName($type){
		$name = '未知';
		switch ($type){
			case self::TYPE_HIGH_CARD:
				$name = '高牌';
				break;
			case self::TYPE_PAIR:
				$name = '对子';
				break;
			case self::TYPE_TWO_PAIR:
				$name = '双对';
				break;
			case self::TYPE_THREE_CARDS:
				$name = '三条';
				break;
			case self::TYPE_STRAIGHT:
				$name = '顺子';
				break;
			case self::TYPE_FLUSH:
				$name = '同花';
				break;
			case self::TYPE_FULL_HOUSE:
				$name = '葫芦';
				break;
			case self::TYPE_FOUR_CARDS:
				$name = '四条';
				break;
			case self::TYPE_STRAIGHT_FLUSH:
				$name ='同花顺';
				break;
			case self::TYPE_ROYAL_FLUSH:
				$name = '皇家同花顺';
				break;
		}
		return $name;
	}
}

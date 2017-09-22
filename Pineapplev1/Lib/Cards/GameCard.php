<?php
include_once __DIR__.'/WayCard.php';

class GameCard{

	public $id;							//关联id
	
	public $fanRound;					//范特西回合数
	
	public $isCardsBust;				//是否爆牌
	
	//WayCard列表
	public $wayCardList = array(
			WayCard::WAY_ID_HEAD=>null,
			WayCard::WAY_ID_MEDIUM=>null,
			WayCard::WAY_ID_TAIL=>null,
	);
	
	
	//胜负分数列表，格式:array(wayId=>score, ....)
	public $winScoreList = array(
			WayCard::WAY_ID_HEAD=>0,
			WayCard::WAY_ID_MEDIUM=>0,
			WayCard::WAY_ID_TAIL=>0
	);
	
	
	//牌型分数列表， 格式:array(wayId=>score, ....)
	public $typeScoreList = array(
			WayCard::WAY_ID_HEAD=>0,
			WayCard::WAY_ID_MEDIUM=>0,
			WayCard::WAY_ID_TAIL=>0
	);
	
	
	//全胜分数
	public $allWinScore = 0;
	
	
	//是否范特西牌型
	public $isCardsFantasy = false;
	
	
	public function __construct($id, $fanRound, WayCard $head, WayCard $medium, WayCard $tail){
		$this->id = $id;
		$this->fanRound = $fanRound;
		$this->wayCardList[WayCard::WAY_ID_HEAD] = $head;
		$this->wayCardList[WayCard::WAY_ID_MEDIUM] = $medium;
		$this->wayCardList[WayCard::WAY_ID_TAIL] = $tail;
		$this->isCardsBust = $this->_checkBust();
	}
	
	/**
	 * 获取总得分
	 */
	public function getSumScore(){
		return array_sum($this->winScoreList) + $this->allWinScore + array_sum($this->typeScoreList);
	}
	
	
	//检查是否爆牌
	private function _checkBust(){
		if (1 == WayCard::compare($this->wayCardList[WayCard::WAY_ID_HEAD], $this->wayCardList[WayCard::WAY_ID_MEDIUM])){
			return true; 
		}
		if ( 1 == WayCard::compare($this->wayCardList[WayCard::WAY_ID_MEDIUM], $this->wayCardList[WayCard::WAY_ID_TAIL])){
			return true;
		}
		return false;
	}

	
	/**
	 * 对比两个GameCard中指定id的WayCard大小(包含爆牌等条件)
	 * @param WayCard $obj1
	 * @param WayCard $obj2
	 *
	 * @return 1/0/-1	 如果如果obj1>obj2， 返回1; obj1<obj2, 返回-1; obj1==obj2,返回0;
	 */
	public static function compare($wayId, GameCard $obj1, GameCard $obj2){
		if ($obj1->isCardsBust && $obj2->isCardsBust){
			return 0;
		}
		if (!$obj1->isCardsBust && $obj2->isCardsBust){
			return 1;
		}
		if ($obj1->isCardsBust && !$obj2->isCardsBust){
			return -1;
		}
		return WayCard::compare($obj1->wayCardList[$wayId], $obj2->wayCardList[$wayId]);
	}
}
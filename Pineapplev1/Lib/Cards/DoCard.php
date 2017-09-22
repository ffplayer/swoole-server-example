<?php
class DOCard{

	//Card::getCardColor() 获取牌的颜色
	//Card::getCardValue() 获取牌的值
	/**
	 * 传入14张牌  得到最优的结果
	 */
	public static function getFtxCard($aCard){
		foreach ($aCard as $cardValue){
			if(false == Card::checkCardValueAvail($cardValue)){
				return null;
			}
		}
		
	}
}
<?php
namespace Model;

class LCHelp{
	
	const LC_PRIMARY_KEY = 20035;
	
	const LC_KEY_BET_SUM = '2_当日投注总额';
	const LC_KEY_BET_REWARD_SUM = '2_当日中奖总额';
	const LC_KEY_BET_FAILED = '2_当日投注失败';
	
	const LC_KEY_BET_REWARD_UPSET_SUM = '3_爆冷中奖';
	const LC_KEY_LOTTERY_REWARD = '3_彩池中奖';
	
	public static function getKeyRoomUser($ante){
		return $ante . '房间人数';
	}
	
	public static function getKeyBetArea($areaId){
		$areaName = Dices::getBetAreaNameZh($areaId);
		return '3_投注' . $areaName;
	}
	
	public static function getKeyBetRewardArea($areaId){
		$areaName = Dices::getBetAreaNameZh($areaId);
		return '3_中奖' . $areaName;
	}
	
}
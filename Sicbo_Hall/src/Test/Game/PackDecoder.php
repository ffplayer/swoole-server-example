<?php
namespace Test\Game;

use Model\GameConst;
class PackDecoder{
	public static function decode($buffer){
		$packList = self::_splitPack($buffer);
		
		foreach ($packList as $data){
			$str = self::_decode($data);
			echo $str . "\n";
		}
	}
	
	
	private static function _splitPack($buffer){
		$list = array();
		
		$swooleBuffer = new \swoole_buffer(1024);
		$len = $swooleBuffer->append($buffer);
		$offset = 0;
		
		while (true){
// 			$headerInfo = unpack("c2Iden/sCmdType/cVer/sLen", $swooleBuffer->read($offset, \GSReadPackage::PACKET_HEADER_SIZE));

			$headerInfo = unpack("c2Iden/sCmdType/cVer/cSubVer/sLen/cCode/IInc", $swooleBuffer->read($offset, \GSReadPackage::PACKET_HEADER_SIZE));
			
			$subLen = $headerInfo['Len'] + \GSReadPackage::PACKET_HEADER_SIZE;
			$list[] = $swooleBuffer->read($offset, $subLen);
			$offset += $subLen;
			if ($offset >= $len){
				break;
			}
		}
		return $list;
	}
	
	
	private static function _decode($data){
		$pack = new \GSReadPackage();
		$pack->ReadPackageBuffer($data);
		$str = "";
		switch ($pack->CmdType){
			case GameConst::CMD_S_LOGIN:
				$str = self::_decode_login($pack);
				break;
			case GameConst::CMD_S_LOGOUT:
				$str = self::_decode_logout($pack);
				break;
			case GameConst::CMD_S_USER_SIT:
				$str = self::_decode_userSit($pack);
				break;
			case GameConst::CMD_S_USER_STAND:
				$str = self::_decode_userStand($pack);
				break;
			case GameConst::CMD_S_USER_COUNT_UPDATE:
				$str = self::_decode_userCountUpdate($pack);
				break;
			case GameConst::CMD_S_GAME_DATA:
				$str = self::_decode_gameData($pack);
				break;
			case GameConst::CMD_S_GAME_READY:
				$str = self::_decode_gameReady($pack);
				break;
			case GameConst::CMD_S_GAME_BET_START:
				$str = self::_decode_gameBetStart($pack);
				break;
			case GameConst::CMD_S_GAME_BET_END:
				$str = self::_decode_gameBetEnd($pack);
				break;
			case GameConst::CMD_S_GAME_RESULT:
				$str = self::_decode_gameResult($pack);
				break;
			case GameConst::CMD_S_GAME_REWARD:
				$str = self::_decode_gameReward_v2($pack);
				break;
			case GameConst::CMD_S_BET:
				$str = self::_decode_bet($pack);
				break;
			case GameConst::CMD_S_BET_REPEAT:
				$str = self::_decode_betRepeat($pack);
				break;
			case GameConst::CMD_S_BROADCAST_BET_SIT:
				$str = self::_decode_broadcastBetSit($pack);
				break;
			case GameConst::CMD_S_BROADCAST_BET_STAND:
				$str = self::_decode_broadcastBetStand($pack);
				break;
			case GameConst::CMD_S_TEST:
				$str = self::_decode_test($pack);
				break;
			default:
				$str = "[error] 未知回包~~~";
		}
		
		return $str;
	}
	
	private static function _decode_test(\GSReadPackage $pack){
		$tableCount = $pack->ReadInt();
		$userCount = $pack->ReadInt();
		$memB = $pack->ReadInt64();
		$memM = round(($memB / 1024 / 1024), 4);
		return "[压力测试]， z桌子数量:{$tableCount}, 用户总数量:{$userCount}, 内存:{$memM}M";
	}
	
	private static function _decode_login(\GSReadPackage $pack){
// 		$ret = $pack->ReadByte();
// 		$status = $pack->ReadByte();
		
// 		$readyTime = $pack->ReadByte();
// 		$betTime = $pack->ReadByte();
		
// 		$betList = array();
// 		$betLen = $pack->ReadByte();
// 		for ($i=0; $i<$betLen; $i++){
// 			$tmpAreaId = $pack->ReadByte();
// 			$tmpMoney = $pack->ReadInt64();
// 			$betList[] = array('areaId'=>$tmpAreaId, 'betMoney'=>$tmpMoney);
// 		}
		
// 		$diceList = array();
// 		$len = $pack->ReadByte();
// 		for ($i=0; $i<$len; $i++){
// 			$diceList[] = $pack->ReadByte();
// 		}
		
// 		$rewardAreaList = array();
// 		$len = $pack->ReadByte();
// 		for ($i=0; $i<$len; $i++){
// 			$rewardAreaList[] = $pack->ReadByte();
// 		}
		
// 		$lotteryMoney = $pack->ReadInt64();
		
// 		$result = array(
// 				'ret'=>$ret,
// 				'status'=>$status,
// 				'readyTime'=>$readyTime,
// 				'betTime'=>$betTime,
// 				'betList'=>$betList,
// 				'diceList'=>$diceList,
// 				'rewardList'=>$rewardAreaList,
// 				'lotteryMoney'=>$lotteryMoney,
// 		);
		$ret = $pack->ReadByte();
		
		return "[登录] ret:{$ret}";
	}
	
	private static function _decode_logout(\GSReadPackage $pack){
		$ret = $pack->ReadByte();
		return "[退出] ret:{$ret}";
	}
	
	
	private static function _decode_userSit(\GSReadPackage $pack){
		$mid = $pack->ReadInt();
		$seatId = $pack->ReadByte();
		$name = $pack->ReadString();
		$url = $pack->ReadString();
		$money = $pack->ReadInt64();
		
		return "[座位] 用户{$mid}在座位{$seatId}坐下了";
	}

	
	private static function _decode_userStand(\GSReadPackage $pack){
		$seatId = $pack->ReadByte();
		return "[座位] 座位{$seatId}上的用户站起了";
	}
	
	private static function _decode_userCountUpdate(\GSReadPackage $pack){
		$standUserCount = $pack->ReadShort();
		return "[桌子信息跟新] 站立玩家数量变为{$standUserCount}人";
	}
	
	private static function _decode_gameData(\GSReadPackage $pack){
		$status = $pack->ReadByte();
		
		$readyTime = $pack->ReadByte();
		$betTime = $pack->ReadByte();
		
		$betList = array();
		$betLen = $pack->ReadByte();
		for ($i=0; $i<$betLen; $i++){
			$tmpAreaId = $pack->ReadByte();
			$tmpMoney = $pack->ReadInt64();
			$tmpSelfMoney = $pack->ReadInt64();
			$betList[] = array('areaId'=>$tmpAreaId, 'betMoney'=>$tmpMoney, 'selfBetMoney'=>$tmpSelfMoney);
		}
		
		$diceList = array();
		$len = $pack->ReadByte();
		for ($i=0; $i<$len; $i++){
			$diceList[] = $pack->ReadByte();
		}
		
		$rewardAreaList = array();
		$len = $pack->ReadByte();
		for ($i=0; $i<$len; $i++){
			$rewardAreaList[] = $pack->ReadByte();
		}
		
		$lotteryMoney = $pack->ReadInt64();
		
		$isShowLastDice = $pack->ReadByte();
		$lastDice = array();
		$len = $pack->ReadByte();
		for ($i=0; $i<$len; $i++){
			$lastDice[] = $pack->ReadByte();
		}
		
		$result = array(
				'status'=>$status,
				'readyTime'=>$readyTime,
				'betTime'=>$betTime,
				'betList'=>$betList,
				'diceList'=>$diceList,
				'rewardList'=>$rewardAreaList,
				'lotteryMoney'=>$lotteryMoney,
				'showLastDice'=>$isShowLastDice,
				'lastDice'=>$lastDice
		);
		return "[房间数据] data:" . json_encode($result);
	}
	
	private static function _decode_gameReady(\GSReadPackage $pack){
		//null
		return "\n\n[流程] 准备倒计时开始";
	}
	
	private static function _decode_gameBetStart(\GSReadPackage $pack){
		$isShowRB = $pack->ReadByte();
		return "[流程] 开始下注, 重复购买按钮状态:{$isShowRB}";
	}
	
	private static function _decode_gameBetEnd(\GSReadPackage $pack){
		$rollButton = $pack->ReadByte();
		$mid = $pack->ReadInt();
		$name = $pack->ReadString();
		$url = $pack->ReadString();
		$money = $pack->ReadInt64();
		return "[流程] 下注结束, 摇奖按钮状态:{$rollButton}";
	}
	
	private static function _decode_gameResult(\GSReadPackage $pack){
		$diceList = array();
		$len = $pack->ReadByte();
		for ($i=0; $i<$len; $i++){
			$diceList[] = $pack->ReadByte();
		}
		return "[流程] 展示开奖结果， 骰子列表".json_encode($diceList);
	}
	
	private static function _decode_gameReward(\GSReadPackage $pack){
		$areaList = array();
		$len = $pack->ReadByte();
		for ($i=0; $i<$len; $i++){
			$areaList[] = $pack->ReadByte();
		}
		
		$upsetList = array();
		$len = $pack->ReadByte();
		for ($i=0; $i<$len; $i++){
			$upsetList[] = $pack->ReadByte();
		}
		
		$winMoney = $pack->ReadInt64();
		$userMoney = $pack->ReadInt64();
		
		$rewardList = array();
		$len = $pack->ReadByte();
		for ($i=0; $i<$len; $i++){
			$tmpPosition = $pack->ReadByte();
			$tmpMoney = $pack->ReadInt64();
			$rewardList[] = array('pos'=>$tmpPosition, 'money'=>$tmpMoney);
		}
		
		$lotteryAddMoney = $pack->ReadInt64();
		$lotteryList = array();
		$len = $pack->ReadByte();
		for ($i=0; $i<$len; $i++){
			$tmpPos = $pack->ReadByte();
			$tmpMoney = $pack->ReadInt64();
			$lotteryList[] = array('pos'=>$tmpPos, 'money'=>$tmpMoney);
		}
		$lotteryMoney = $pack->ReadInt64();
		
		$topList = array();
		$len = $pack->ReadByte();
		for ($i=0; $i<$len; $i++){
			$tmpMid = $pack->ReadInt();
			$tmpName = $pack->ReadString();
			$tmpUrl = $pack->ReadString();
			$tmpMoney = $pack->ReadInt64();
			$topList[] = array('mid'=>$tmpMid, 'name'=>$tmpName, 'url'=>$tmpUrl, 'money'=>$tmpMoney);
		}
		
		$result = array(
				'money'=>$userMoney,
				'winMoney'=>$winMoney,
				'area'=>$areaList,
				'upset'=>$upsetList,
				'reward'=>$rewardList,
				'lottery'=>$lotteryList,
				'lotteryAddMonety'=>$lotteryAddMoney,
				'lotteryMoney'=>$lotteryMoney,
				'topList'=>$topList
		);
		echo "[流程] 展示奖励, data:".json_encode($result);
	}
	
	private static function _decode_gameReward_v2(\GSReadPackage $pack){
		$areaList = array();
		$len = $pack->ReadByte();
		for ($i=0; $i<$len; $i++){
			$areaList[] = $pack->ReadByte();
		}
	
		$upsetList = array();
		$len = $pack->ReadByte();
		for ($i=0; $i<$len; $i++){
			$upsetList[] = $pack->ReadByte();
		}
	
		$winMoney = $pack->ReadInt64();
		$userMoney = $pack->ReadInt64();
	
		$rewardList = array();
		$len = $pack->ReadShort();
		for ($i=0; $i<$len; $i++){
			$tmpAreaId = $pack->ReadByte();
			$tmpPosition = $pack->ReadByte();
			$tmpMoney = $pack->ReadInt64();
			$rewardList[] = array('areaId'=>$tmpAreaId, 'pos'=>$tmpPosition, 'money'=>$tmpMoney);
		}
	
		$lotteryAddMoney = $pack->ReadInt64();
		$lotteryList = array();
		$len = $pack->ReadByte();
		for ($i=0; $i<$len; $i++){
			$tmpPos = $pack->ReadByte();
			$tmpMoney = $pack->ReadInt64();
			$lotteryList[] = array('pos'=>$tmpPos, 'money'=>$tmpMoney);
		}
		$lotteryMoney = $pack->ReadInt64();
	
		$topList = array();
		$len = $pack->ReadByte();
		for ($i=0; $i<$len; $i++){
			$tmpMid = $pack->ReadInt();
			$tmpName = $pack->ReadString();
			$tmpUrl = $pack->ReadString();
			$tmpMoney = $pack->ReadInt64();
			$topList[] = array('mid'=>$tmpMid, 'name'=>$tmpName, 'url'=>$tmpUrl, 'money'=>$tmpMoney);
		}
	
		$result = array(
				'money'=>$userMoney,
				'winMoney'=>$winMoney,
				'area'=>$areaList,
				'upset'=>$upsetList,
				'reward'=>$rewardList,
				'lottery'=>$lotteryList,
				'lotteryAddMonety'=>$lotteryAddMoney,
				'lotteryMoney'=>$lotteryMoney,
				'topList'=>$topList
		);
		echo "[流程] 展示奖励, data:".json_encode($result);
	}
	
	private static function _decode_bet(\GSReadPackage $pack){
		$ret = $pack->ReadByte();
		$areaId = $pack->ReadByte();
		$betMoney = $pack->ReadInt64();
		$allBetMoney = $pack->ReadInt64();
		$selfBetMoney = $pack->ReadInt64();
		$money = $pack->ReadInt64();
		
		$result = array(
				'ret'=>$ret,
				'areaId'=>$areaId,
				'betMoney'=>$betMoney,
				'allBetMoney'=>$allBetMoney,
				'selfBetMoney'=>$selfBetMoney,
				'userMoney'=>$money
		);
		return "[下注] 自己下注" . json_encode($result);
	}
	
	private static function _decode_betRepeat(\GSReadPackage $pack){
		$ret = $pack->ReadByte();
		
		$areaList = array();
		$len = $pack->ReadByte();
		for ($i=0; $i<$len; $i++){
			$tmpAreaId = $pack->ReadByte();
			$tmpAllBetMoney = $pack->ReadInt64();
			$tmpSelfBetMoney = $pack->ReadInt64();
			$areaList[] = array('areaId'=>$tmpAreaId, 'allBMoney'=>$tmpAllBetMoney, 'selfBMoney'=>$tmpSelfBetMoney);
		}
		
		$money = $pack->ReadInt64();
		
		$result = array(
				'ret'=>$ret,
				'betList'=>$areaList
		);
		
		return "[下注] 自己下注" . json_encode($result);
	}
	
	private static function _decode_broadcastBetSit(\GSReadPackage $pack){
		$seatID = $pack->ReadByte();
		
		$betList = array();
		$len = $pack->ReadByte();
		for ($i=0; $i<$len; $i++){
			$tmpAreaId = $pack->ReadByte();
			$tmpMoney = $pack->ReadInt64();
			$betList[] = array('areaId'=>$tmpAreaId, 'betMoney'=>$tmpMoney);
		}
		
		$result = array(
				'seatId'=>$seatID,
				'betList'=>$betList
		);
		
		return "[下注] 其他座位下注" . json_encode($result);
	}
	
	private static function _decode_broadcastBetStand(\GSReadPackage $pack){
		$betList = array();
		$len = $pack->ReadByte();
		for ($i=0; $i<$len; $i++){
			$tmpAreaId = $pack->ReadByte();
			$tmpMoney = $pack->ReadInt64();
			$betList[] = array('areaId'=>$tmpAreaId, 'money'=>$tmpMoney);
		}
		return "[下注] 其他站立下注" . json_encode($betList);
	}
}

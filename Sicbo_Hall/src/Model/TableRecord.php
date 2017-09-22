<?php
namespace Model;

class TableRecord{
	
	//下注区域概况, {areaId:money, ...}
	private $_betSummaryList = array();
	
	//下注列表, {mid:{areaId:money, ...}, ...}
	private $_betList = array();
	
	//待广播用户下注记录, {mid:{areaId:money, ...}, ...}
	private $_betBufferList = array();
	
	//历史下注记录（保留最后一局）, {mid:{areaId:money, ...}, ...}
	private $_betHistoryList = array();
	
	//奖金列表, {mid:{areaId:money, ...}, ...}
	private $_rewardList = array();
	
	//暴击区域列表, {mid:[areaId, ...],  ...}
	private $_upsetList = array();
	
	//未下注用户信息列表{mid:{'count':roundCount}, ...}
	private $_noBetList = array();
	
	//用户取消下注列表{mid:{areaId:money, ...}, ...}
	private $_cancelBetList = array();
	
	//中奖区域信息{areaId:{'odd':odd, 'subVal': subVal}, ...}
	private $_rooomRewardAreaInfo = array();
	
	//奖励抽成记录, {mid:{'sys':money, 'lottery':money}, ...}
	private $_rewardRecoverList = array();
	
	public function __construct(){}
	
	//新一轮游戏刷新
	public function refresh(){
		foreach ($this->_betList as $mid=>$betList){
			$this->_betHistoryList[$mid] = $betList;
		}
		$this->_betSummaryList = array();
		$this->_betList = array();
		$this->_betBufferList = array();
		$this->_rewardList = array();
		$this->_upsetList = array();
		$this->_rooomRewardAreaInfo = array();
		$this->_rewardRecoverList = array();
		$this->_cancelBetList = array();
	}
	
	
	//添加下注记录
	public function addBetRecord($mid, $areaId, $betMoney){
		if (!isset($this->_betSummaryList[$areaId])){
			$this->_betSummaryList[$areaId] = 0;
		}
		if (!isset($this->_betList[$mid][$areaId])){
			$this->_betList[$mid][$areaId] = 0;
		}
		if (!isset($this->_betBufferList[$mid][$areaId])){
			$this->_betBufferList[$mid][$areaId] = 0;
		}
		$this->_betSummaryList[$areaId] += $betMoney;
		$this->_betList[$mid][$areaId] += $betMoney;
		$this->_betBufferList[$mid][$areaId] += $betMoney;
	}
	
	public function cancelBetRecord($mid){
		$betMoney = $this->getUserBetMoney($mid);
		$userBetList = $this->getUserBetList($mid);
		foreach ($userBetList as $areaId=>$money){
			if (!isset($this->_betSummaryList[$areaId])){
				continue;
			}
			$this->_betSummaryList[$areaId] -= $money;
			if ($this->_betSummaryList[$areaId] <= 0){
				unset($this->_betSummaryList[$areaId]);
			}
		}
		unset($this->_betList[$mid]);
		unset($this->_betBufferList[$mid]);
		
		$this->_cancelBetList[$mid] = $userBetList;
		
		return ;
	}
	
	//添加奖励记录
	public function addRewardRecord($mid, $areaId, $rewardMoney, $isUpset, $sysRecover, $lotteryRecover){
		$this->_rewardList[$mid][$areaId] = $rewardMoney;
		if (true == $isUpset){
			$this->_upsetList[$mid][] = $areaId;
		}
		if (!isset($this->_rewardRecoverList[$mid])){
			$this->_rewardRecoverList[$mid] = array('sys'=>0, 'lottery'=>0);
		}
		$this->_rewardRecoverList[$mid]['sys'] += $sysRecover;
		$this->_rewardRecoverList[$mid]['lottery'] += $lotteryRecover;
	}
	
	public function setRoomRewardAreaInfo($areaInfo){
		$this->_rooomRewardAreaInfo = $areaInfo;
	}
	
	
	/*************************下注相关信息  begin*************************/
	public function getSummaryBetMoney(){
		$money = 0;
		foreach ($this->_betSummaryList as $tmpMoney){
			$money += $tmpMoney;
		}
		return $money;
	}
	
	//获取区域下注金额列表
	public function getSummaryAreaBetList(){
		return $this->_betSummaryList;
	}
	
	//获取指定区域下注金额
	public function getSummaryAreaBetMoney($areaId){
		if (!isset($this->_betSummaryList[$areaId])){
			return 0;
		}
		return $this->_betSummaryList[$areaId];
	}
	
	//获取下注用户列表
	public function getBetList(){
		return $this->_betList;
	}
	
	//获取指定用户下注列表
	public function getUserBetList($mid){
		if (!isset($this->_betList[$mid])){
			return array();
		}
		return $this->_betList[$mid];
	}
	
	//获取用户下注金额
	public function getUserBetMoney($mid){
		if (!isset($this->_betList[$mid])){
			return 0;
		}
		return array_sum($this->_betList[$mid]);
	}
	
	//获取指定用户区域的下注金额
	public function getUserAreaBetMoney($mid, $areaId){
		if (!isset($this->_betList[$mid][$areaId])){
			return 0;
		}
		return $this->_betList[$mid][$areaId];
	}
	/*************************下注相关信息  end***************************/
	
	
	
	/***********************历史下注记录相关 begin**************************/
	//获取用户历史下注列表
	public function getUserHistoryBetList($mid){
		if (!isset($this->_betHistoryList[$mid])){
			return array();
		}
		return $this->_betHistoryList[$mid];
	}
	
	//获取用户历史下注总金额
	public function getUserHistoryBetMoney($mid){
		if (!isset($this->_betHistoryList[$mid])){
			return 0;
		}
		return array_sum($this->_betHistoryList[$mid]);
	}
	
	//清空用户历史下注记录
	public function delUserHistoryBetInfo($mid){
		unset($this->_betHistoryList[$mid]);
	}
	/***********************历史下注记录相关 end****************************/
	
	
	/************************下注广播列表 begin***************************/
	//获取并删除指定用户下注buff列表
	public function popUserBufferBetList($mid){
		if (!isset($this->_betBufferList[$mid])){
			return array();
		}
		$list = $this->_betBufferList[$mid];
		unset($this->_betBufferList[$mid]);
		return $list;
	}
	
	//获取下注最多的用户信息
	public function getMaxBetUserInfo(){
		$maxMid = User::MID_NONE;
		$maxMoney = 0;
		foreach ($this->_betList as $mid=>$betList){
			$betMoney = $this->getUserBetMoney($mid);
			if ($betMoney > $maxMoney){
				$maxMid = $mid;
				$maxMoney = $betMoney;
			}
			continue;
		}
		return array('mid'=>$maxMid, 'money'=>$maxMoney);
	}
	/************************下注广播列表 end***************************/
	
	
	
	/************************取消下注列表 begin***************************/
	public function getUserCancelBetList($mid){
		$list = array();
		if (isset($this->_cancelBetList[$mid])){
			$list = $this->_cancelBetList[$mid];
		}
		return $list;
	}
	
	public function popUserCancelBetList($mid){
		$list = array();
		if (isset($this->_cancelBetList[$mid])){
			$list = $this->_cancelBetList[$mid];
		}
		
		unset($this->_cancelBetList[$mid]);
		return $list;
	}
	
	/************************取消下注列表 end*****************************/
	
	
	
	/*************************结算奖励相关 begin**************************/
	//获取奖金列表
	public function getRewardList(){
		return $this->_rewardList;
	}
	
	public function getRewardMoney(){
		$money = 0;
		foreach ($this->_rewardList as $mid=>$rewardInfo){
			$userMoney = $this->getUserRewardMoney($mid);
			$money += $userMoney;
		}
		return $money;
	}
	
	public function getUserRewardList($mid){
		if (!isset($this->_rewardList[$mid])){
			return 0;
		}
		return $this->_rewardList[$mid];
	}
	
	public function getUserRewardMoney($mid){
		if (!isset($this->_rewardList[$mid])){
			return 0;
		}
		return array_sum($this->_rewardList[$mid]);
	}
	
	public function getUserWinMoney($mid){
		$betMoney = $this->getUserBetMoney($mid);
		$rewardMoney = $this->getUserRewardMoney($mid);
		return $rewardMoney - $betMoney;
	}
	
	//获取用户中奖区域详情列表
	public function getUserAreaList($mid){
		$list = array();
		if (!isset($this->_rewardList[$mid])){
			return array();
		}
		foreach ($this->_rewardList[$mid] as $areaId=>$areaInfo){
			$subVal = $areaId;
			if (isset($this->_rooomRewardAreaInfo[$areaId]['subVal'])){
				$subVal = $this->_rooomRewardAreaInfo[$areaId]['subVal'];
			}
			$list[$areaId] = $subVal;
		}
		return $list;
	}
	
	//获取用户赢钱列表
	public function getUserWinList($mid){
		if (!isset($this->_betList[$mid])){
			return array();
		}
		
		$list = array();
		foreach ($this->_betList[$mid] as $areaId=>$betMoney){
			$rewardMoney = isset($this->_rewardList[$mid][$areaId]) ? $this->_rewardList[$mid][$areaId] : 0;
			$winMoney = $rewardMoney - $betMoney;
			if ($winMoney <= 0){
				continue;
			}
			$list[$areaId] = $winMoney;
		}
		
		return $list;
	}
	
	//检测用户赢钱区域是否有指定类型
	public function checkUserWinAreaType($mid, $type){
		$ret = 0;
		$winList = $this->getUserWinList($mid);
		foreach ($winList as $areaId=>$winMoney){
			if (isset(Config::$game['aBet'][$areaId]['type']) && $type == Config::$game['aBet'][$areaId]['type']){
				$ret = 1;
				break;
			}
		}
		return $ret;
	}
	
	//获取指定用户盈利排名列表
	public function getWinUserTopList($count){
		$topList = array();
		foreach ($this->_betList as $mid=>$betList){
			$winMoney = $this->getUserWinMoney($mid);
			if ($winMoney <= 0){
				continue;
			}
			$topList[$mid] = $winMoney;
		}
		arsort($topList);
		
		return array_slice($topList, 0, $count, true);
	}
	
	//获取指定用户暴击区域列表
	public function getUserUpsetList($mid){
		if (!isset($this->_upsetList[$mid])){
			return array();
		}
		return $this->_upsetList[$mid];
	}
	/*************************结算奖励相关  end***************************/
	
	/*************************奖励提成相关  begin***************************/
	public function getUserRecoverMoneyOfSys($mid){
		if (!isset($this->_rewardRecoverList[$mid])){
			return 0;
		}
		return $this->_rewardRecoverList[$mid]['sys'];
	}
	
	public function getUserRecoverMoneyOfLottery($mid){
		if (!isset($this->_rewardRecoverList[$mid])){
			return 0;
		}
		return $this->_rewardRecoverList[$mid]['lottery'];
	}
	/*************************奖励提成相关  end***************************/
	
	
	/*************************未下注用户相关  begin************************/
	//获取未下注列表
	public function getNoBetList(){
		return $this->_noBetList;
	}
	
	//获取用户为下注轮数
	public function getUserNobetCount($mid){
		if (!isset($this->_noBetList[$mid]['count'])){
			return 0;
		}
		return $this->_noBetList[$mid]['count'];
	}
	
	//用户未下注轮数+1
	public function addNoBetUser($mid){
		if (!isset($this->_noBetList[$mid])){
			$this->_noBetList[$mid] = array('count'=>0);
		}
		$this->_noBetList[$mid]['count']++;
	}
	
	//清除用户未下注信息
	public function clearNoBetUser($mid){
		unset($this->_noBetList[$mid]);
	}
	/*************************未下注用户相关  end**************************/
}
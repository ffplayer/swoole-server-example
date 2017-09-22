<?php
/**
* 查询一个房间
*/
class ModelFindTable{
	/**
	 * 找一个桌子
	 * $shieldTid 屏蔽的桌子ＩＤ
	 */
	public function find($ante, $shieldTid=0){
		global $find_ante;
		$aAnteInfo = $find_ante->get($ante);
		if(!$aAnteInfo){//没有找到桌子
			return 0;
		}
		$aAnteTable = Fun::unserialize($aAnteInfo['cfg']);
		if($aAnteTable[1]){//如果存在一个人的房间 直接返回
			shuffle($aAnteTable[1]);
			foreach($aAnteTable[1] as $tid){
				if($shieldTid == $tid){
					continue;
				}
				if($this->tidIsOk($tid)){
					return $tid;
				}
			}
		}
		if($aAnteTable[2]){
			foreach($aAnteTable[2] as $tid=>$s){
				if($shieldTid == $tid){
					continue;
				}
				if($this->tidIsOk($tid)){
					return $tid;
				}
			}
		}
		if($aAnteTable[0]){
			foreach($aAnteTable[0] as $tid){
				if($shieldTid == $tid){
					continue;
				}
				if($this->tidIsOk($tid)){
					return $tid;
				}
			}
		}
		return 0;
	}
	
	public function doRun(){
		global $game_table,$game_ante,$find_ante;
		$aSum = $aTables = array();
		foreach($game_ante as $ante=>$val){
			$aTables[$ante] = array(0=> array(), 1=>array(), 2=>array());
			$aSum[$ante] = array(0=> 0, 1=>0, 2=>0);
		}
		foreach($game_table as $tid=>$info){
			$play = $info['play'];
			if($info['play'] >= 3){
				continue;
			}
			$ante = $info['ante'];
			if(!isset($aTables[$ante])){
				continue;
			}
			if($aSum[$ante][$play] < 10){
				if($play == 2){
					$aTables[$ante][$play][$tid] = $info['status'] ? $info['status'] : 10;
				}else{
					$aTables[$ante][$play][] = $tid;
				}
				$aSum[$ante][$play]++;
			}
		}
		foreach($aTables as $ante=>$aInfo){
			if($aSum[$ante][0] < 10){
				$aInfo[0] = array_merge($aInfo[0], $this->addTable($ante, 10-$aSum[$ante][0]));
			}
			arsort($aInfo[2]);
			$find_ante->set($ante, array('cfg'=>Fun::serialize($aInfo)));
		}
	}
	
	private function tidIsOk($tid){
		global $game_table;
		$aGameTable = $game_table->get($tid);
		if($aGameTable['play'] < 3){
			return true;
		}
		return false;
	}
	
	/**
	 * 新增桌子 一次添加10个
	 */
	private function addTable($ante, $num=10){
		global $id_atomic,$game_table;
		$aTids = array();
		for($i=0;$i<$num;$i++){
			$aTids[] = $tid = $id_atomic->add(1);
			$game_table->set($tid, array('ante'=>$ante, 'play'=>0, 'status'=> 0));
		}
		return $aTids;
	}
}
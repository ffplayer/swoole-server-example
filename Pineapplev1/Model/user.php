<?php
/**
* 用户信息
*/
class ModelUser{
	public $mid;//用户ID
	public $aPosNeedNum = array(1 => 3,2 => 5,3 => 5);//位置需要牌的张数
	
	public function get($field){
		return oo::data()->getUserData($this->mid, $field);
	}
	
	public function set($field, $val){
		return oo::data()->setUserData($this->mid, $field, $val);
	}
	
	public function setArr($aData){
		return oo::data()->setUserDatas($this->mid, $aData);
	}
	
	public function del(){
		oo::data()->delUserData($this->mid);
	}
	/**
	 * 用户进入桌子时
	 */
	public function userLogin($tid, $mInfoStr=''){
		$aInfo = array_merge((array)json_decode($mInfoStr, true) , (array)$this->get('userInfo'));
		$this->setArr(array('tid'=> $tid, 'disconnect'=>0, 'userInfo'=> $aInfo, 'aGiInfo'=> array()));
	}
	
	public function getGi($field=''){
		$aGi = $this->get('aGiInfo');
		if(!$aGi){
			$aGi = oo::member()->onlineinfo($this->mid);
			$aGiInfo = array('sid'=>$aGi['sid'], 'mstatus'=>$aGi['mstatus']);
			$this->set('aGiInfo', $aGiInfo);
		}
		return $field ? $aGi[$field] : $aGi;
	}
	/**
	 * 用户入座时调用 默认准备
	 */
	public function userSit($seatid){
		$aData = array(
			'seatId'=>$seatid, 
			'isReady'=> 1,
			'inQueue'=> 0,
		);
		$aData['userInfo'] = array_merge((array)$this->get('userInfo'), oo::member()->getMinfo($this->mid));
		$this->setArr($aData);
		$this->updateGi(array('mtstatus'=>2));
		oo::main()->adminLog("userSit,mid:".$this->mid." seatid:".$seatid);
	}
	
	public function standUp(){
		$this->set('seatId', 0);
		$this->updateGi(array('mtstatus'=>1));
		if($this->get('disconnect') > 1){//站起之后重新计算次数
			$this->set('disconnect', 1);
		}
	}
	
	/**
	 * 开始发牌时调用
	 */
	public function startPlay(){
		$aData['isPlay'] = 1;
		$aData['isReady'] = 0;
		$this->setArr($aData);
	}
	
	/**
	 * 游戏结束时调用
	 */
	public function playOver($isPlay=0){
		$aData = array(
			'foldPoker' => array(),
			'myCards' => array(
					1 => array(),//头道的牌
					2 => array(),//中道的牌
					3 => array()//尾道的牌
				),
			'sendPoker' => array(),
			'isPlay' => $isPlay,
			'isReady' => 0
		);
		$this->setArr($aData);
		$this->updateGi();
	}
	
	private function updateGi($aInfo = array()){
		if($aGi = oo::member()->updateOnlineInfo($this->mid, $aInfo)){
			$aGiInfo = array('sid'=>$aGi['sid'], 'mstatus'=>$aGi['mstatus']);
			$this->set('aGiInfo', $aGiInfo);
		}
	}
	
	/**
	 * 获取用户手中已经成型的牌
	 */
	public function getAllCards(){
		return $this->get('myCards');
	}
	
	public function getMidTableInfo(){
		global $mid_table;
		return $mid_table->get($this->mid);
	}
	
	/**
	 * 用户退出桌子
	 */
	public function quitTable(){
		$this->set('score', 0);
		global $mid_table,$fd_table;
		$fdInfo = $mid_table->get($this->mid);
		$tid = $this->get('tid');
		if($tid && ($tid!= $fdInfo['tid'])){
			oo::main()->adminLog($this->mid." 清理失败  ".$tid.' '.$fdInfo['tid']);
			return;
		}
		$mid_table->del($this->mid);
		$fd_table->del($fdInfo['fd']);
		$this->updateGi(array('mtstatus'=>0,'svid'=>0, 'tid'=>0));
	}
	
	/**
	 *  给用户随机出牌
	 * @return array 出的牌
	 */
	public function randSubmit(){
		$sendPoker = $this->get('sendPoker');
		$oneCard = $this->get('oneCard');
		$aMyCards = $this->get('myCards');
		$sendNum = count($sendPoker);
		$useCard = $aCard = array();
		foreach($oneCard as $card=>$pos){
			$aCard[] = array($pos, $card);
			$useCard[] = $card;
			$aMyCards[$pos][] = $card;
		}
		$hasCard = count($aCard);
		$asendToNeed = array(
			5 => 5,
			14 =>13,
			3 => 2
		);
		if($hasCard >= $asendToNeed[$sendNum]){
			return $aCard;
		}
		$needNum = $asendToNeed[$sendNum] - $hasCard;
		$leftCard = array_diff($sendPoker, $useCard);
		$aPos = array();
		foreach($aMyCards as $pos => $aC){
			$leftNum = $this->aPosNeedNum[$pos] - count($aC);
			for($i=0;$i<$leftNum;$i++){
				$aPos[] = $pos;
			}
		}
		shuffle($leftCard);
		shuffle($aPos);
		$sendPokerKey = array_rand($leftCard, $needNum);
		$aPosKey = array_rand($aPos, $needNum);
		if($needNum == 1){
			$aCard[] = array($aPos[$aPosKey], $leftCard[$sendPokerKey]);
		}else{
			for($i=0;$i<$needNum;$i++){
				$aCard[] = array($aPos[$aPosKey[$i]], $leftCard[$sendPokerKey[$i]]);
			}
		}
		return $aCard;
	}
	
	/**
	 * 检测用户提交的牌
	 */
	public function checkCard($aCard){
		$myCards = $this->get('myCards');
		$sendPoker = $this->get('sendPoker');
		$aSCard = array();
		$num = count($aCard);
		$sendNum = count($sendPoker);
		if(!in_array($num, array(2, 5, 13))){
			oo::main()->adminLog($this->mid.' 张数错误:'.$num);
			return false;
		}
		if(($num + 1) < $sendNum ){
			oo::main()->adminLog($this->mid.' 张数少了:'.$num);
			return false;
		}
		
		foreach($aCard as $aC){
			if(!in_array($aC[0], array(1,2,3))){
				oo::main()->adminLog($this->mid.' '.$aC[0] . ' 位置错误');
				return false;
			}
			if(!in_array($aC[1], $sendPoker)){//如果不在发牌的里面
				oo::main()->adminLog($this->mid.' '.$aC[1] . ' 不在发的牌中');
				return false;
			}
			if(isset($myCards[$aC[0]]) && !in_array($aC[1], $myCards[$aC[0]])){
				array_push($myCards[$aC[0]], $aC[1]);
				$aSCard[] = $aC[1];
			}
		}
		foreach($myCards as $k=> &$aV){
			$num = count($aV);
			if(($k == 1) && ($num > 3)){
				oo::main()->adminLog($this->mid.' 牌的张数ERR,'.$num.' '. json_encode($aV));
				return false;
			}
			if(in_array($k , array(2, 3)) && ($num > 5)){
				oo::main()->adminLog($this->mid.' 牌的张数ERR,'.$num.' '. json_encode($aV));
				return false;
			}
			$aV = CardsArbiter::sortCardList($aV);//排序下
		}
		
		$aFold = array_diff($sendPoker, $aSCard);
		oo::data()->setUserAddArray($this->mid, 'foldPoker', $aFold);
		$aData['sendPoker'] = array();
		$aData['myCards'] = $myCards;
		$aData['isPut'] = 1;
		$aData['putCard'] = $aCard;
		$aData['oneCard'] = array();
		$this->setArr($aData);
		return true;
	}
	
	/**
	 * 提交一张牌型
	 */
	public function checkOneCard($aCard){
		$myCards = $this->get('myCards');
		$sendPoker = $this->get('sendPoker');
		$oneCard = $this->get('oneCard');
		if(!in_array($aCard[1], $sendPoker)){//如果不在发牌的里面
			oo::main()->adminLog($this->mid.' '.$aCard[1] . ' 不在发的牌中checkOneCard');
			return false;
		}
		$pos = $aCard[0];
		if(!$pos){
			unset($oneCard[$aCard[1]]);
			$this->set('oneCard', $oneCard);
			return true;
		}
		if($aCard[2]){
			unset($oneCard[$aCard[2]]);
		}
		if(!in_array($pos, array(1,2,3))){
			oo::main()->adminLog($this->mid.' '.$pos . ' 位置错误checkOneCard');
			return false;
		}
		$hasNum = count($myCards[$pos]);
		foreach($oneCard as $card=>$p){
			if($pos == $p){
				$hasNum++;
			}
		}
		if($hasNum >= $this->aPosNeedNum[$pos]){
			oo::main()->adminLog($this->mid.' '.$pos . '  张数错误checkOneCard');
			return false;
		}
		$asendToNeed = array(
			5 => 5,
			14 =>13,
			3 => 2
		);
		$oneCard[$aCard[1]] = $pos;
		if(count($oneCard) > $asendToNeed[count($sendPoker)]){
			oo::main()->adminLog($this->mid.' '.count($oneCard) . '  提交张数错误checkOneCard');
			return false;
		}
		$this->set('oneCard', $oneCard);
	}
	
	/**
	 * 给用户发包
	 */
	public function sendPack($wrpack, $packData=false){
		if($this->get('disconnect')){
			oo::main()->adminLog($this->mid." sendPack err cmd:".$wrpack->GetCmdType());
			return;
		}
		
		$aInfo = $this->getMidTableInfo();
		oo::main()->adminLog($this->mid." sendPack cmd:".$wrpack->GetCmdType());
		if($aInfo['fd'] && oo::main()->swoole->exist($aInfo['fd'])){
			if(!$packData){
				$packData =  $wrpack->GetPacketBuffer();
			}
			oo::main()->swoole->send($aInfo['fd'], $packData);
			//oo::main()->adminLog($this->mid.' '.bin2hex($packData));
			$this->set('mtime', time());
		}
	}
	
	/**
	 * 获取用户信息str格式
	 */
	public function getUserInfoStr(){
		$userInfoStr = oo::data()->getUserData($this->mid, 'userInfoStr');
		if(!$userInfoStr){
			$userInfoStr = json_encode((array)$this->get('userInfo'));
			$this->set('userInfoStr', $userInfoStr);
		}
		return $userInfoStr;
	}
	
	public function getUserInfo(){
		$userInfo = $this->get('userInfo');
		if(!$userInfo['mnick']){
			$userInfo = array_merge($userInfo, oo::member()->getMinfo($this->mid));
			$this->set('userInfo', $userInfo);
		}
		return $userInfo;
	}
	
	/**
	 * 游戏币不够坐下提示
	 */
	private $sMoneyLimit = array();
	public function moneyLimit($num=0){
		if(!$this->sMoneyLimit[$num]){
			$writePackage = new GSWritePackage();
			$writePackage->WriteBegin(gameConst::S_MONEY_LIMIT);
			$writePackage->WriteByte($num);
			$writePackage->WriteEnd();
			$this->sMoneyLimit[$num] = $writePackage;
		}
		$this->sendPack($this->sMoneyLimit[$num]);
	}
}
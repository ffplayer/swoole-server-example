<?php
/**
* 桌子相关操作
*/
class ModelTables{
	public $tid;//桌子ID
	
	public function get($field, $key = ''){
		return oo::data()->getTableData($this->tid, $field, $key);
	}
	
	public function set($field, $val){
		oo::data()->setTableData($this->tid, $field, $val);
	}
	
	public function setArr($aData){
		oo::data()->setTableDatas($this->tid, $aData);
	}
	
	public function upArr($field, $k , $val){
		oo::data()->upArrTable($this->tid, $field, $k , $val);
	}
	public function delArrKey($field, $k){
		oo::data()->delArrTable($this->tid, $field, $k);
	}
	public function addStr($field, $msg){
		oo::data()->addTableStr($this->tid, $field, $msg);
	}
	
	public function setCfg(){
		global $game_ante,$game_table;
		if(!($ante = $this->get('ante'))){
			$tableInfo = $game_table->get($this->tid);
			$ante = $tableInfo['ante'];
		}
		$tableInfo = $game_ante->get($ante);
		if(!$tableInfo){//不存在了
			$game_table->del($this->tid);
			oo::data()->delTableData($this->tid);
			return false;
		}
		oo::main()->adminLog($this->tid. ' 桌子配置:'. $tableInfo['cfg']);
		$this->setArr(array('ante'=> $ante,'mtime'=>time(), 'reload'=>0, 'cfg'=>Fun::unserialize($tableInfo['cfg'])));
		oo::main()->logs(array('reload',$this->tid, $ante, $tableInfo['cfg']), 'table_cfg');
		//$this->upArr('cfg', 'times', 5);
		return true;
	}
	
	/**
	 * 用户登录
	 */
	public function userLogin($mid, $mInfoStr){
		$mid = fun::uint($mid);
		//oo::main()->adminLog($mid.' '.$this->tid.' info:'.$mInfoStr);
		$aAllMid = $this->get('aAllMid'); 
		oo::user($mid)->userLogin($this->tid, $mInfoStr);
		$this->userLoginSuccess($mid);
		if(!$aAllMid[$mid]){
			$this->upArr('aAllMid' , $mid , 1);
			$this->updateNum(2, 1);
			$this->userSit($mid);
		}
	}
	/**
	 * 用户坐下
	 */
	public function userSit($mid){
		$mid = fun::uint($mid);
		if(oo::user($mid)->get('seatId')){//用户已经坐下了
			return true;
		}
		if(!$this->get('aAllMid', $mid)){
			oo::main()->logs('no In aAllMid, mid:'.$mid.'|'.$this->tid, 'userSitErr');
			return false;
		}
		global $game_table;
		$findSeat = 0;
		$aSeat = $this->get('aSeat');
		foreach($aSeat as $seatId=>$v){//给用户找个位置
			if(!$v){
				$findSeat = $seatId;
				break;
			}
		}
		if(!$findSeat){
			return false;
		}
		if(!$this->canSit($mid)){//判断用户是否够资格坐下
			return false;
		}
		$this->upArr('aSeat', $findSeat, $mid);
		oo::user($mid)->userSit($findSeat);
		$this->upArr('aAllMid' , $mid , 2);
		$playNow = $game_table->incr($this->tid, 'play', 1);
		$this->userSitSuccess($mid);//广播用户坐下成功
		$this->updateNum(1, 1);
		if(($playNow >= 2) && !$this->get('status')){//如果已经是2个人 直接开始
			$this->startGame();
		}
		return true;
	}
	
	/**
	 * 用户站起
	 */
	public function userStandUp($mid){
		$mid = fun::uint($mid);
		if(!$seatId = oo::user($mid)->get('seatId')){//用户不在座位上
			return true;
		}
		if(!oo::user($mid)->get('isPlay')){//可以站起
			global $game_table;
			oo::user($mid)->standUp();
			$playNow = $game_table->decr($this->tid, 'play', 1);
			$this->upArr('aAllMid' , $mid , 1);
			$this->upArr('aSeat' , $seatId , 0);
			
			$writePackage = new GSWritePackage();
			$writePackage->WriteBegin(gameConst::S_BROADCAST_STANDUP);
			$writePackage->WriteByte($seatId);
			$this->broadTablePackage($writePackage);
			
			if(oo::money()->addWin($mid, 1152, 0, $this->get('cfg', 'mincarry')) === false){//还最小携带
				oo::main()->logs(array('最小携带', $mid, $this->get('ante'), $this->get('cfg', 'mincarry')), 'moneyErr' , 1);
			}
			oo::main()->logs(array('up', $mid, $this->tid), 'sit_'.$this->get('ante'));
			$this->updateNum(1, 0);
			//处理排队的人自动入座
			//$this->queueSit();
			return true;
		}else{
			return false;
		}
	}
	
	/**
	 * 用户退出房间
	 */
	public function quitTable($mid){
		$mid = fun::uint($mid);
		if(!$this->get('aAllMid', $mid)){
			oo::main()->logs('quitErr no in aAllMid, mid:'.$mid.'|'.$this->tid, 'quitTable');
			return;
		}
		if($this->userStandUp($mid)){//站起成功
			$this->delArrKey('aAllMid' , $mid);
			//如果用户在排队
			//$this->cancelQueue($mid);

			$writePackage = new GSWritePackage();
			$writePackage->WriteBegin(gameConst::S_LOGOUT);
			$writePackage->WriteByte(1);
			$writePackage->WriteEnd();
			oo::user($mid)->sendPack($writePackage);
			$this->updateNum(2, 0);
			oo::user($mid)->quitTable();
			
			oo::user($mid)->del();
			return true;
		}
		return false;
	}
	
	/**
	 * 换个房间
	 */
	public function otherTable($mid){
		$mid = fun::uint($mid);
		if(!$this->get('aAllMid', $mid)){
			return;
		}
		$findTid = oo::findTable()->find($this->get('ante'), $this->tid);
		if($findTid && $this->userStandUp($mid)){
			$this->delArrKey('aAllMid' , $mid);
			$this->updateNum(2, 0);
			$mInfoStr = oo::user($mid)->getUserInfoStr();
			oo::user($mid)->del();
			global $mid_table;
			$mid_table->set($mid, array('tid'=>$findTid));
			oo::task()->sendMessage(array('game', 'enterRoom', array($mid, $findTid, $mInfoStr)) , $findTid);
		}
	}
	
	/**
	 * 用户点击排队按钮了
	 */
	public function userQueue($mid){
		$mid = fun::uint($mid);
//		if(!$this->canSit($mid)){//先判断用户能不能入座
//			return;
//		}
//		if(($this->aAllMid[$mid] == 1) && !in_array($mid, $this->aQueue)){//旁观用户才可以点击排队
//			array_push($this->aQueue, $mid);
//			oo::user($mid)->set('inQueue' , 1);
//			$this->broadcastQueue();
//		}
	}
	
	/**
	 * 取消排队
	 */
	public function cancelQueue($mid){
		$mid = fun::uint($mid);
		if(oo::user($mid)->get('inQueue')){
			$offset = array_search($mid, $this->get('aQueue'));
			if($offset !== false){
				//array_splice($this->aQueue, $offset, 1);
				$this->broadcastQueue();
			}
			oo::user($mid)->set('inQueue' , 0);
		}
	}
	
	/**
	 * 排队的人自动坐下
	 */
	protected function queueSit(){
		//if($this->aQueue && ($mid = array_pop($this->aQueue))){
		//	$this->userSit($mid);
		//	$this->broadcastQueue();
		//}
	}
	
	/**
	 * 判断用户是否可以入座
	 */
	protected function canSit($mid){
		if(oo::game()->stopGame){//停服了 不让坐下
			$this->quitTable($mid);
			return false;
		}
		if(oo::user($mid)->getGi('mstatus') == 1){
			oo::user($mid)->moneyLimit(3);
			return false;
		}
		//判断用户够钱坐下不
		$money = oo::money()->getAvailableMoney($mid);
		$mincarry = $this->get('cfg', 'mincarry');
		if($money < $mincarry){//游戏币小于最小携带 禁止坐下
			oo::user($mid)->moneyLimit(1);
			return false;
		}
		if(oo::money()->addWin($mid, 1151, 1, $mincarry) === false){//先扣除一个最小携带
			oo::user($mid)->moneyLimit(2);
			oo::main()->logs(array($mid, $this->get('ante')), 'sitErr');
			return false;
		}
		oo::user($mid)->set('money', $money);
		oo::main()->logs(array('sit', $mid,$this->tid), 'sit_'.$this->get('ante'));
		return true;
	}
	
	/**
	 * 提交牌型
	 */
	public function submitCard($mid, $aCard, $isSys=0){
		if(oo::user($mid)->checkCard($aCard)){
			$this->addStr('plog', '4|'.time().'|'.oo::user($mid)->get('seatId').'|'.$isSys);
			foreach($aCard as $card){
				$this->addStr('plog', '|'.$card[0].'_'.$card[1]);
			}
			$this->addStr('plog','#');
			//广播用户的牌
			if(oo::user($mid)->get('isFtx')){
				TimerHandler::del($this->tid.'|'.$mid, TimerEvent::EVENT_TYPE_FTX);
				if($this->get('status') == 5){//有可能结束了
					$this->broadcastMycard($mid, $aCard);
					$isOver = true;
					foreach($this->get('aSeat') as $seatId=>$mid){
						if($mid && oo::user($mid)->get('isPlay') && !oo::user($mid)->get('isPut')){
							$isOver = false;
						}
					}
					if($isOver){
						$this->playOver();
					}
				}else{
					$this->putOk($mid, 1);
				}
			}elseif($this->get('operating') == oo::user($mid)->get('seatId')){
				TimerHandler::del($this->tid, TimerEvent::EVENT_TYPE_POKER_DO);
				$this->broadcastMycard($mid, $aCard);
				$this->findNextPutPoker();//寻找下一个
			}else{
				$this->putOk($mid, 1);
			}
		}else{
			$this->putOk($mid, 2);
			oo::main()->logs(array($mid, $isSys, $this->get('ante'), $aCard, oo::user($mid)->get('sendPoker')), 'submitErr');
		}
	}
	/**
	 *  提交一张牌
	 */
	public function submitOneCard($mid, $aCard){
		oo::user($mid)->checkOneCard($aCard);
	}
	/**
	 *  提交多张牌
	 */
	public function submiMoreCard($mid, $aD){
		foreach((array)$aD['aCard'] as $card){
			$aCard = array($aD['pos'],  $card, 0);
			oo::user($mid)->checkOneCard($aCard);
		}
	}
	
	/**
	 * 提交牌是否成功
	 */
	private function putOk($mid, $num){
		$writePackage = new GSWritePackage();
		$writePackage->WriteBegin(gameConst::S_BROADCAST_PUTOK);
		$writePackage->WriteByte($num);
		$writePackage->WriteEnd();
		oo::user($mid)->sendPack($writePackage);
	}
	
	/**
	 * 告诉用户登录成功
	 */
	public function userLoginSuccess($mid){
		$writePackage = new GSWritePackage();
		$writePackage->WriteBegin(gameConst::S_LOGIN);
		$writePackage->WriteInt($this->get('ante'));//底注
		$writePackage->WriteInt(oo::user($mid)->get('score'));//得分
		$writePackage->WriteByte($this->get('operating'));//当前操作者
		$writePackage->WriteByte($this->get('makers'));//庄家位置
		$aUser = array();
		foreach($this->get('aSeat') as $seatId=>$v){
			if($v){
				$aUser[$v] = array(
					$seatId,
					oo::user($v)->get('money'),
					oo::user($v)->get('isReady'),//是否准备
					oo::user($v)->getUserInfoStr(),//用户额外信息
					oo::user($v)->getAllCards(),
					oo::user($v)->get('isFtx'),
				);
			}
		}
		$writePackage->WriteByte(count($aUser));
		foreach($aUser as $imid=>$aInfo){
			$writePackage->WriteInt($imid);
			$writePackage->WriteByte($aInfo[0]);
			$writePackage->WriteInt64($aInfo[1]);
			$writePackage->WriteByte($aInfo[2]);
			$writePackage->WriteString($aInfo[3]);
			
			$myCard = $aInfo[4];
			$ct1 = count($myCard[1]);
			$ct2 = count($myCard[2]);
			$ct3 = count($myCard[3]);
			$aCard = array();
			if(($imid != $mid) && ($aPut = oo::user($imid)->get('putCard'))){//不显示没有展示的牌
				foreach($aPut as $put){
					$aCard[] = $put[1];
				}
			}
			if($ct1 + $ct2 + $ct3 > count($aCard)){
				$writePackage->WriteByte(1);
				foreach($myCard as $cards){
					$aNeed = array();
					foreach($cards as $card){
						if(!in_array($card, $aCard)){
							$aNeed[] = $card;
						}
					}
					$writePackage->WriteByte(count($aNeed));
					foreach($aNeed as $card){
						$writePackage->WriteByte($card);
					}
				}
			}else{
				$writePackage->WriteByte(0);
			}
			$writePackage->WriteByte($aInfo[5]?1:0);
		}
		//以下是告诉我的操作牌
		$sendPoker = (array)oo::user($mid)->get('sendPoker');
		$writePackage->WriteByte(count($sendPoker));
		foreach($sendPoker as $card){
			$writePackage->WriteByte($card);
		}
		$writePackage->WriteEnd();
		oo::user($mid)->sendPack($writePackage);
	}
	
	/**
	 * 广播用户坐下成功
	 */
	protected function userSitSuccess($mid){
		$writePackage = new GSWritePackage();
		$writePackage->WriteBegin(gameConst::S_BROADCAST_SIT);
		$writePackage->WriteInt($mid);
		$writePackage->WriteByte(oo::user($mid)->get('seatId'));
		$writePackage->WriteInt64(oo::user($mid)->get('money'));
		$writePackage->WriteByte(1);
		$writePackage->WriteString(oo::user($mid)->getUserInfoStr());
		$this->broadTablePackage($writePackage);
	}
	
	
	/**
	 * 开始游戏
	 */
	public function startGame(){
		if(!in_array($this->get('status'), array(0, 6, 7))){
			return;
		}
		global $game_table;
		if(oo::game()->stopGame && (!$this->get('isFtx'))){//停服了
			$this->broadStopGame();
			return;
		}
		if($this->get('reload') && !$this->setCfg()){
			return;
		}
		$playNum = 0;
		foreach($this->get('aSeat') as $seatId=>$mid){
			if($mid){
				$playNum++;
			}
		}
		if($playNum < 2){
			$playNow = $game_table->decr($this->tid, 'play', 1);
			$this->updateNum(1, 0);
			//oo::main()->adminLog('游戏开始了,人数不够，num:'.$playNum);
			oo::main()->logs('game Num err', 'table_'.$this->get('ante').'/'.$this->tid);
			return;
		}
		$aSet = array();
		$aSet['mtime'] = time();
		$aSet['plog']  = '1|'.$aSet['mtime'].'|'.$this->get('isFtx');
		$aSet['status'] = 1;
		$bid = $this->get('bid');
		if($bid > 9999999){
			$aSet['bid'] = 1;
		}else{
			$aSet['bid'] = $bid + 1;//局ID自增
		}
		$this->updateGameTable(array('status'=>1));
		
		CardsSender::start($this->tid);
		
		$this->setArr($aSet);
		
		foreach($this->get('aSeat') as $seatId=>$mid){
			if(!$mid){
				continue;
			}
			if($this->get('isFtx') && !oo::user($mid)->get('isPlay')){
				continue;
			}
			oo::user($mid)->startPlay();
			$this->addStr('plog', '|'.$seatId.'_'.$mid.'_'. oo::user($mid)->getGi('sid') .'_'.oo::user($mid)->get('money'));
		}
		
		if(!$this->get('isFtx')){//如果不是范特西 庄家下一个
			$this->set('makers', $this->findNextOperating($this->get('makers')));//确定庄家位置
		}
		
		$this->doAllFee();//处理台费
		
		$this->addStr('plog', '#');
		oo::main()->logs(array('startGame', $aSet['bid'], $this->get('aSeat')) , 'table_'.$this->get('ante').'/'.$this->tid);
		$this->sendFiveCard();
	}
	
	public function stopGame(){
		if($this->get('status') == 0){
			$this->broadStopGame();
		}else{
			$writePackage = new GSWritePackage();
			$writePackage->WriteBegin(gameConst::S_BROADCAST_STOP);
			$writePackage->WriteByte(1);
			$writePackage->WriteEnd();
			$packData = $writePackage->GetPacketBuffer();
			foreach($this->get('aAllMid') as $mid=>$status){//没有玩的玩家发停服广播
				if($mid && !oo::user($mid)->get('isPlay')){
					oo::user($mid)->sendPack($writePackage, $packData);
					$this->quitTable($mid);
				}
			}
		}
	}
	
	/**
	 * 停服了
	 */
	public function broadStopGame(){
		$writePackage = new GSWritePackage();
		$writePackage->WriteBegin(gameConst::S_BROADCAST_STOP);
		$writePackage->WriteByte(1);
		$this->broadTablePackage($writePackage);
		foreach($this->get('aAllMid') as $mid=>$status){
			if($mid){
				$this->quitTable($mid);
			}
		}
	}

	/**
	 * 开始发5张牌
	 */
	public function sendFiveCard(){
		$aSet = $aPlay = $aWp = array();
		$aSet['first_operating'] = $aSet['operating'] = $this->findNextOperating($this->get('makers'));
		oo::main()->adminLog($this->tid." 开始发5张牌, 庄家:". $this->get('makers').",操作者：".$aSet['first_operating']);
		$aSet['mtime'] = time();
		$this->setArr($aSet);
		$this->addStr('plog', '2|'.$aSet['mtime']);
		foreach($this->get('aAllMid') as $mid=>$status){
			$writePackage = new GSWritePackage();
			$writePackage->WriteBegin(gameConst::S_FIVECARD);
			$writePackage->WriteByte($this->get('makers'));//庄家位置
			$writePackage->WriteByte($aSet['operating']);//操作者位置
			if(oo::user($mid)->get('isPlay')){//在玩的用户发牌
				oo::user($mid)->set('isPut' , 0);
				$aPlay[oo::user($mid)->get('seatId')] = 14;
				if(oo::user($mid)->get('isFtx')){//用户进入范特西了
					$writePackage->WriteByte(14);
					$aCard = CardsSender::send($this->tid , 14);
					oo::user($mid)->set('sendPoker', $aCard);
					
					//添加范特西倒计时
					$event = new TimerEvent($this->tid.'|'.$mid, TimerEvent::EVENT_TYPE_FTX, array('tid'=>$this->tid, 'mid'=> $mid));
					TimerHandler::after($event, oo::$cfg['ftxOpTime']);
				}else{
					$aPlay[oo::user($mid)->get('seatId')] = 5;
					$writePackage->WriteByte(5);
					$aCard = CardsSender::send($this->tid , 5);
					oo::user($mid)->set('sendPoker', $aCard);
					
					//oo::main()->adminLog($this->tid." " .$mid ." 开始发5张牌".json_encode($aCard));
				}
				$this->addStr('plog', '|'.oo::user($mid)->get('seatId'));
				foreach($aCard as $card){
					$writePackage->WriteByte($card);
					$this->addStr('plog', '_'.$card);
				}
			}else{
				$writePackage->WriteByte(0);
			}
			$aWp[$mid] =  $writePackage;
		}
		$playNum = count($aPlay);
		foreach($aWp as $mid=>$wp){
			$wp->WriteByte($playNum);
			foreach($aPlay as $seatId=>$num){
				$wp->WriteByte($seatId);
				$wp->WriteByte($num);
			}
			$wp->WriteEnd();
			oo::user($mid)->sendPack($wp);
		}
		$this->addStr('plog', '#');
		
		$event = new TimerEvent($this->tid, TimerEvent::EVENT_TYPE_POKER_DO, array('tid'=>$this->tid));
		TimerHandler::after($event, $this->get('cfg', 'times'));
		
	}
	/**
	 * 开始发3张牌
	 * 需要发4轮
	 */
	public function sendThreeCard(){
		$status = $this->get('status');
		$status++;
		$aSet = $aPlay = $aWp = array();
		$this->updateGameTable(array('status'=>$status));
		$aSet['status'] = $status;
		$aSet['first_operating'] = $aSet['operating'] = $this->findNextOperating($this->get('makers'));
		oo::main()->adminLog($this->tid." 状态:".$status ." 开始发3张牌, 庄家:". $this->get('makers').",操作者：".$aSet['first_operating']);
		
		$aSet['mtime'] = time();
		$this->setArr($aSet);
		$this->addStr('plog', '3|'.$aSet['mtime']);
		foreach($this->get('aAllMid') as $mid=>$status){
			$writePackage = new GSWritePackage();
			$writePackage->WriteBegin(gameConst::S_THREECARD);
			$writePackage->WriteByte($aSet['operating']);//操作者位置
			if(oo::user($mid)->get('isPlay') && !oo::user($mid)->get('isFtx')){//在玩的用户 并且没有进入范特西 发牌
				$aPlay[oo::user($mid)->get('seatId')] = 3;
				$writePackage->WriteByte(3);
				$aCard = CardsSender::send($this->tid , 3);
				oo::user($mid)->set('sendPoker', $aCard);
				oo::user($mid)->set('isPut', 0);
				//oo::main()->adminLog($this->tid." " .$mid ." 开始发3张牌".json_encode($aCard));
				$this->addStr('plog', '|'.oo::user($mid)->get('seatId'));
				foreach($aCard as $card){
					$writePackage->WriteByte($card);
					$this->addStr('plog', '_'.$card);
				}
			}else{
				if(($status == 5) && oo::user($mid)->get('isPlay')){//范特西玩家到最后才广播
					$this->broadcastMycard($mid);
				}
				$writePackage->WriteByte(0);
			}
			$aWp[$mid] =  $writePackage;
		}
		$playNum = count($aPlay);
		foreach($aWp as $mid=>$wp){
			$wp->WriteByte($playNum);
			foreach($aPlay as $seatId=>$num){
				$wp->WriteByte($seatId);
				$wp->WriteByte($num);
			}
			$wp->WriteEnd();
			oo::user($mid)->sendPack($wp);
		}
		$this->addStr('plog', '#');
		$event = new TimerEvent($this->tid, TimerEvent::EVENT_TYPE_POKER_DO, array('tid'=>$this->tid));
		TimerHandler::after($event, $this->get('cfg', 'times'));
	}
	
	/**
	 * 查找下一个操作的座位ID
	 */
	protected function findNextOperating($inSeatId){
		$outSeatId = 0;
		$find = false;
		foreach($this->get('aSeat') as $seatId=>$mid){
			if(!$mid){
				continue;
			}
			if(!oo::user($mid)->get('isPlay')){//如果不在牌局中
				continue;
			}
			if(oo::user($mid)->get('isFtx')){//用户已经进入范特西了
				continue;
			}
			if($seatId == $inSeatId){
				$find = true;
				continue;
			}
			if($find){
				$outSeatId = $seatId;
				break;
			}
			if(!$outSeatId){
				$outSeatId = $seatId;
			}
		}
		return $outSeatId ? $outSeatId : $inSeatId;
	}
	
	/**
	 * 翻牌超时处理
	 */
	public function pokerOutTime(){
		$mid = $this->get('aSeat', $this->get('operating'));
		//oo::main()->adminLog('pokerOutTime>>'.$this->get('operating').' '.$mid);
		
		//给用户随机翻牌
		$aCard = oo::user($mid)->randSubmit();
		oo::main()->adminLog($this->tid." ". $mid .'超时，随机牌:'.json_encode($aCard));
		$this->submitCard($mid, $aCard, 1);
	}
	/**
	 * 范特西超时处理
	 */
	public function ftxOutTime($mid){
		$aCard = oo::user($mid)->randSubmit();
		oo::main()->adminLog($this->tid." ". $mid .'超时，随机牌:'.json_encode($aCard));
		$this->submitCard($mid, $aCard, 1);
	}
	
	/**
	 * 寻找下一个摆牌的玩家
	 */
	public function findNextPutPoker(){
		$operating = $this->findNextOperating($this->get('operating'));
		$this->set('operating',$operating);
		if($this->get('first_operating') == $operating){
			if($this->get('status') == 5){//状态是5了 要结束了
				if($this->get('isFtx')){//判断下范特西的用户好了没
					foreach($this->get('aSeat') as $seatId=>$mid){
						if($mid && oo::user($mid)->get('isPlay') && !oo::user($mid)->get('isPut')){
							oo::main()->adminLog($this->tid. ' mid:'.$mid.' 没有摆好牌');
							return;
						}
					}
				}
				$this->playOver();
			}else{
				$this->sendThreeCard();
			}
		}else{
			$mid = $this->get('aSeat', $operating);
			if(oo::user($mid)->get('isPut')){//将这个用户最新的牌型 广播下
				$this->broadcastMycard($mid);
				$this->findNextPutPoker();
			}else{//广播这个位置开始倒计时
				oo::main()->adminLog($this->tid. "下一个操作者:".$operating);
				$writePackage = new GSWritePackage();
				$writePackage->WriteBegin(gameConst::S_BROADCAST_OPT);
				$writePackage->WriteByte($operating);//操作者位置
				$writePackage->WriteInt($mid);//操作者mid
				$this->broadTablePackage($writePackage);
				$event = new TimerEvent($this->tid, TimerEvent::EVENT_TYPE_POKER_DO, array('tid'=>$this->tid));
				TimerHandler::after($event, $this->get('cfg', 'times'));
			}
		}
	}
	
	/**
	 * 用户准备好了
	 */
	public function userReady($mid){
		if(oo::user($mid)->get('seatId')){
			oo::user($mid)->set('isReady' , 1);
			$this->readyOk(oo::user($mid)->get('seatId'));
			$start = true;
			$playNum = 0;
			foreach($this->get('aSeat') as $seatId=>$mid){
				if(!$mid){
					continue;
				}
				if(oo::user($mid)->get('isReady')){
					$playNum++;
				}else{//有人没有准备
					$start = false;
				}
			}
			if($start && ($playNum >= 2)){//都准备好了 自动开始
				TimerHandler::del($this->tid, TimerEvent::EVENT_TYPE_START_READY);
				$this->startGame();
			}
		}
	}
	
	/**
	 * 开始结算
	 */
	public function playOver(){
		$aSet = $aPlayCards = array();
		$aSet['operating'] = 0;
		$aSet['mtime'] = $endTime = time();
		$this->addStr('plog', '5|'.$endTime);
		foreach($this->get('aSeat') as $seatId=>$mid){
			if($mid && oo::user($mid)->get('isPlay')){
				$aPlayCards[$mid]['fanRound'] = oo::user($mid)->get('isFtx');
				$aPlayCards[$mid]['cardList'] = oo::user($mid)->getAllCards();
				oo::main()->adminLog($this->tid." ".$mid." 成牌:".json_encode($aPlayCards[$mid]));
			}elseif($mid && oo::user($mid)->get('disconnect')){//用户断线了
				$this->userStandUp($mid);
			}
		}
		
		$aSet['isFtx'] = 0;
		$aResult = (array)CardsArbiter::calResult($aPlayCards);
		foreach($aResult as $mid=>$aRet){
			if($aRet['isFantasy']){
				$aSet['isFtx'] = 1;
				$ftx = oo::user($mid)->get('isFtx')+1;
				oo::user($mid)->set('isFtx' , $ftx);
			}else{
				oo::user($mid)->set('isFtx' , 0);
			}
			$score = oo::user($mid)->get('score')+$aRet['sumScore'];
			oo::user($mid)->set('score' , $score);
			oo::transit()->proc('pineapplePlay', array($this->tid, $this->get('bid'), $mid, $this->get('ante'), $aRet['sumScore'], $aRet['isFantasy']?1:0));
		}
		if($aSet['isFtx']){
			$aSet['status'] = 6;
		}else{
			$aSet['status'] = 7;
		}
		$this->setArr($aSet);
		oo::money()->tid = $this->tid;
		oo::money()->bid = $this->get('bid');
		foreach($aResult as $mid=>$aRet){
			$winMoney = $aResult[$mid]['money'] = $this->get('ante') *  $aRet['sumScore'];
			$fee = $this->getFee($winMoney);
			oo::user($mid)->playOver($aSet['isFtx']?1:0);
			$this->doOverMoney($mid,  $winMoney, $fee, $aRet['sumScore'], $endTime);
			$this->addStr('plog', '|'.$mid.'_'.$winMoney.'_'.$aRet['sumScore'].'_'.($aRet['isFantasy']?1:0).'_'.$aRet['isBust'].'_'.$aRet['allWinScore'].'_'.fun::serialize((array)$aRet['winScoreList']).'_'.fun::serialize((array)$aRet['typeScoreList']));
		}
		oo::money()->tid = 0;
		oo::money()->bid = 0;
		$this->broadcastPlayOver($aResult);
		$this->updateGameTable(array('status'=>$aSet['status']));
		$event = new TimerEvent($this->tid, TimerEvent::EVENT_TYPE_START_READY, array('tid'=>$this->tid));
		TimerHandler::after($event, oo::$cfg['overLeftTime']);
		oo::transit()->mf(TSWOOLE_SID, 0, 'pineapple_gambling_detail', array('tid'=>$this->tid, 'bid'=>$this->get('bid'),'blindmin'=>$this->get('ante'),'tcontent'=>$this->get('plog')),$endTime);
		$plog  = 'pineapplelog,'.$this->get('ante').','.$this->tid.','.$this->get('bid').','.$this->get('plog');
		oo::main()->adminLog($plog);
		$this->set('plog', '');
		CardsSender::end($this->tid);
		oo::main()->logs(array('playOver', $aResult) , 'table_'.$this->get('ante').'/'.$this->tid);
	}
	
	/**
	 * 加钱 扣钱操作
	 */
	public function doOverMoney($mid,  $winMoney, $fee, $score, $endTime){
		if($this->get('isFtx')){
			$aMoney = $this->get('aMoney', $mid);
			if($this->get('cfg', 'winCosts') && $fee){
				$aMoney[1] += $fee;
			}
			$aMoney[0] += $winMoney;
			$this->upArr('aMoney', $mid, $aMoney);
			$money = oo::user($mid)->get('money');
			oo::user($mid)->set('money', $money + $winMoney - $fee);
			oo::transit()->mf(TSWOOLE_SID, $mid, 'pineapple_user_gambling', array('tid'=>$this->tid, 'bid'=>$this->get('bid'),'blindmin'=>$this->get('ante'),
							'svid'=>oo::$cfg['svid'],'sid'=>oo::user($mid)->getGi('sid'),'score'=>$score,'vmoney'=>$this->get('cfg', 'winCosts')?$fee:0, 'coins'=>$money),$endTime);
			return true;
		}else{
			$money = oo::money()->getAvailableMoney($mid);
			oo::transit()->mf(TSWOOLE_SID, $mid, 'pineapple_user_gambling', array('tid'=>$this->tid, 'bid'=>$this->get('bid'),'blindmin'=>$this->get('ante'),
							'svid'=>oo::$cfg['svid'],'sid'=>oo::user($mid)->getGi('sid'),'score'=>$score,'vmoney'=>$fee, 'coins'=>$money),$endTime);
			$aMoney = $this->get('aMoney', $mid);
			$fee += (int)$aMoney[1];
			$winMoney += (int)$aMoney[0];
			$needMoney = $money + $winMoney - $fee;
			if($needMoney < 0){//用户目前身上钱不够
				$this->userStandUp($mid);//让用户站起
				oo::user($mid)->moneyLimit(1);
				$money = oo::money()->getAvailableMoney($mid);
			}
			if($fee){
				$ret = oo::money()->addWin($mid, 1155, 1, $fee);//扣除台费
				if($ret === false){//扣钱失败了 不许玩了
					oo::main()->logs(array('台费错误', $mid, $fee, $this->tid, $this->get('bid'), oo::money()->addWinMsg), 'moneyErr', 1);
				}else{
					$money = $money - $fee;
				}
			}
			if($money + $winMoney < 0){//钱不够 负分了
				oo::main()->logs(array('负分', $mid, $winMoney, $money, $this->tid, $this->get('bid')), 'moneyErr', 1);
			}
			$this->delArrKey('aMoney', $mid);
			if($winMoney){
				if($winMoney>0 ){
					$ret = oo::money()->addWin($mid, 1154, 0, $winMoney);//加钱
				}else{
					$ret = oo::money()->addWin($mid, 1153, 1, -$winMoney);//扣钱
				}
				
				if($ret === false){//扣钱失败了 不许玩了
					oo::main()->logs(array('加钱错误', $mid, $winMoney, $this->tid, $this->get('bid'), oo::money()->addWinMsg), 'moneyErr', 1);
					$this->userStandUp($mid);//让用户站起
					oo::user($mid)->moneyLimit(2);
					return false;
				}
				$money = $money + $winMoney;
			}
			if(oo::user($mid)->getGi('mstatus') == 1){//账号被封
				$this->userStandUp($mid);//让用户站起
				oo::user($mid)->moneyLimit(3);
				return false;
			}
			if(oo::user($mid)->get('seatId')){
				oo::user($mid)->set('money', $money + $this->get('cfg', 'mincarry'));
			}else{
				oo::user($mid)->set('money', 0);
			}
			return true;
		}
	}
	
	/**
	 * 获取应该交的台费
	 */
	public function getFee($winMoney){
		if($this->get('cfg', 'fee')){
			return $this->get('cfg', 'fee');
		}elseif($this->get('cfg', 'winCosts')){//赢钱玩家交台费
			return ($winMoney > 0) ?  ceil($winMoney*$this->get('cfg', 'winCosts')) : 0;
		}
		return 0;
	}
	
	/**
	 * 准备超时 进入下一局
	 */
	public function readyOutTime(){
		if($this->get('isFtx')){//如果是范特西 直接开始
			return $this->startGame();
		}
		$playNum = 0;
		foreach($this->get('aSeat') as $seatId=>$mid){
			if(!$mid){
				continue;
			}
			if(oo::user($mid)->get('isReady')){//没有准备 踢掉站起
				$playNum++;
			}else{
				$this->userStandUp($mid);
				oo::main()->adminLog($mid.' 被系统踢掉站起了,桌子ID：'.$this->tid);
			}
		}
		if($playNum >= 2){
			$this->startGame();
		}else{
			$this->set('status', 0);
			$this->updateGameTable(array('status'=>0));
			if(oo::game()->stopGame){
				$this->broadStopGame();
			}
		}
	}
	
	private function broadcastPlayOver($aResult){
		$writePackage = new GSWritePackage();
		$writePackage->WriteBegin(gameConst::S_BROADCAST_OVER);
		$writePackage->WriteByte(count($aResult));
		foreach($aResult as $mid=>$info){
			$writePackage->WriteByte(oo::user($mid)->get('seatId'));
			$writePackage->WriteInt($mid);
			$writePackage->WriteInt64($info['money']);//获得游戏币
			$writePackage->WriteInt64(oo::user($mid)->get('money'));//最新游戏币
			$writePackage->WriteByte($info['isBust']?1:0);//是否爆牌
			$writePackage->WriteByte($info['isFantasy']?1:0);//是否范特西
			$writePackage->WriteShort($info['sumScore']);//总分
			$writePackage->WriteShort($info['allWinScore']);//全胜分数
			$writePackage->WriteShort($info['winScoreList'][1] + $info['typeScoreList'][1]);//头道
			$writePackage->WriteShort($info['winScoreList'][2] + $info['typeScoreList'][2]);//中道
			$writePackage->WriteShort($info['winScoreList'][3] + $info['typeScoreList'][3]);//尾道
		}
		$this->broadTablePackage($writePackage);
	}
	
	/**
	 *  周知准备好了
	 */
	public function readyOk($seatId){
		$writePackage = new GSWritePackage();
		$writePackage->WriteBegin(gameConst::S_BROADCAST_READY);
		$writePackage->WriteByte($seatId);
		$this->broadTablePackage($writePackage);
	}
	
	/**
	 * 广播用户进房间
	 */
	public function broadcastUserLogin($mid){
		$writePackage = new GSWritePackage();
		$writePackage->WriteBegin(gameConst::S_BROADCAST_LOGIN);
		$writePackage->WriteInt($mid);
		$this->broadTablePackage($writePackage, $mid);
	}
	
	/**
	 * 重新加载配置
	 */
	public function reloadCfg(){
		$this->set('reload', 1);
	}
	/**
	 * 用户断线了
	 */
	public function onClose($mid){
		oo::user($mid)->set('disconnect' , 1);
		oo::main()->adminLog($mid.' 断线了');
		$event = new TimerEvent($mid.'|'.$this->tid, TimerEvent::EVENT_USERCLOSE, array('tid'=>$this->tid, 'mid'=>$mid));
		TimerHandler::after($event, 60);
	}
	
	public function userClose($mid){
		if($disConnect = oo::user($mid)->get('disconnect')){//如果用户是断线的
			if(oo::user($mid)->get('isPlay')){
				$ct = 10;
			}elseif(oo::user($mid)->get('seatId')){
				$ct = 5;
			}else{
				$ct = 3;
			}
			if($disConnect < $ct){
				$disConnect++;
				oo::user($mid)->set('disconnect' , $disConnect);
				$event = new TimerEvent($mid.'|'.$this->tid, TimerEvent::EVENT_USERCLOSE, array('tid'=>$this->tid, 'mid'=>$mid));
				TimerHandler::after($event, 60);
				return;
			}else{
				oo::main()->adminLog($mid.' 系统强制踢出房间:'.$this->tid);
				if(!$this->quitTable($mid)){//如果退出房间失败 基本是卡房间了
					oo::main()->logs(array($mid, $this->get('ante'), $this->tid, oo::user($mid)->get('isPlay')), 'quitTableErr' , 1);
				}
			}
		}
	}
	
	/**
	 * 聊天
	 */
	public function chat($mid, $aChat){
		$conent = $aChat['chats'];
		$writePackage = new GSWritePackage();
		$writePackage->WriteBegin(gameConst::S_BROADCAST_CHAT);
		$writePackage->WriteInt($mid);
		$aUser = oo::user($mid)->getUserInfo();
		$writePackage->WriteString($aUser['mnick']);
		$writePackage->WriteString($conent);
		$this->broadTablePackage($writePackage);
	}
	/**
	 * 表情发送
	 */
	public function phizSend($mid, $aPhiz){
		$phiz = $aPhiz['phiz'];
		$writePackage = new GSWritePackage();
		$writePackage->WriteBegin(gameConst::S_BROADCAST_PHIZ);
		$writePackage->WriteByte(oo::user($mid)->get('seatId'));
		$writePackage->WriteInt($phiz);
		$this->broadTablePackage($writePackage);
	}
	
	/**
	 * 广播添加好友
	 */
	public function addFriend($mid, $aInfo){
		$toMid = $aInfo['toMid'];
		$act = $aInfo['act'];
		if(!$seatId = oo::user($toMid)->get('seatId')){
			return;
		}
		if( !$mySeatId  = oo::user($mid)->get('seatId')){
			return;
		}
		$writePackage = new GSWritePackage();
		$writePackage->WriteBegin(($act==2)? gameConst::S_BROADCAST_ADDFRIENDSUC: gameConst::S_BROADCAST_ADDFRIEND);
		$writePackage->WriteByte($mySeatId);
		$writePackage->WriteByte($seatId);
		$writePackage->WriteInt($mid);
		$writePackage->WriteInt($toMid);
		$this->broadTablePackage($writePackage);
	}
	
	/**
	 * 广播我的牌型
	 */
	protected function broadcastMycard($mid, $aCard=array()){
		$writePackage = new GSWritePackage();
		$writePackage->WriteBegin(gameConst::S_BROADCAST_PUTCARD);
		$writePackage->WriteByte(oo::user($mid)->get('seatId'));
		if(!$aCard){
			$aCard = (array)oo::user($mid)->get('putCard');
		}
		if(!$aCard){
			return;
		}
		oo::user($mid)->set('putCard', array());
		$writePackage->WriteByte(count($aCard));
		foreach($aCard as $card){
			$writePackage->WriteByte($card[0]);
			$writePackage->WriteByte($card[1]);
		}
		$this->broadTablePackage($writePackage);
	}
	
	/**
	 * 广播现在排队人数
	 */
	protected function broadcastQueue(){
		$writePackage = new GSWritePackage();
		$writePackage->WriteBegin(gameConst::S_BROADCAST_QUEUE);
		$writePackage->WriteByte(count($this->get('aQueue')));
		$this->broadTablePackage($writePackage);
	}
	
	/*
	 * 桌子上广播数据 
	 */
	protected function broadTablePackage(GSWritePackage $wrpack , $mid=0){
		$wrpack->WriteEnd();
		$packData = $wrpack->GetPacketBuffer();
		foreach($this->get('aAllMid') as $toMid=>$status){
			if($toMid != $mid){
				oo::user($toMid)->sendPack($wrpack, $packData);
			}
		}
	}
	
	/**
	 * 广播给旁观玩家
	 */
	protected function broadViewTablePackage(GSWritePackage $wrpack){
		$wrpack->WriteEnd();
		$packData = $wrpack->GetPacketBuffer();
		foreach($this->get('aAllMid') as $toMid=>$status){
			if($status == 1){
				oo::user($toMid)->sendPack($wrpack, $packData);
			}
		}
	}
	
	/**
	 * 更新桌子的状态
	 */
	protected function updateGameTable($aInfo){
		global $game_table;
		$game_table->set($this->tid, $aInfo);
	}
	
	/**
	 * 游戏开始时处理台费 并没有真的扣钱 就是怕用户当前游戏币不够扣台费
	 */
	protected function doAllFee(){
		$fee = $this->get('cfg', 'fee');
		if($this->get('isFtx') || !$fee){
			return false;
		}
		
		$aUser = array();
		foreach($this->get('aSeat') as $seatId=>$mid){
			if($mid){
				$money = oo::user($mid)->get('money') - $fee;
				oo::user($mid)->set('money', $money);
				$aUser[$seatId]['money'] = $money;
			}
		}
		$writePackage = new GSWritePackage();
		$writePackage->WriteBegin(gameConst::S_BROADCAST_FEE);
		$writePackage->WriteInt($fee);
		$writePackage->WriteByte(count($aUser));
		foreach($aUser as $seatId=>$info){
			$writePackage->WriteByte($seatId);
			$writePackage->WriteInt64($info['money']);
		}
		$this->broadTablePackage($writePackage);
	}
	
	/**
	 * 更新游戏人数
	 * $type 1 在玩 2旁观
	 * $inc 1 增加 0减少
	 */
	protected function updateNum($type, $inc=0){
		global $game_ante;
		$aSet = array();
		$ante = $this->get('ante');
		if($type == 1){
			if($inc){
				$aSet['player'] = $game_ante->incr($ante, 'play', 1);
				$aSet['viewer'] = $game_ante->decr($ante, 'view', 1);
			}else{
				$aSet['player'] = $game_ante->decr($ante, 'play', 1);
				$aSet['viewer'] = $game_ante->incr($ante, 'view', 1);
			}
		}elseif($type == 2){
			if($inc){
				$aSet['viewer'] = $game_ante->incr($ante, 'view', 1);
			}else{
				$aSet['viewer'] = $game_ante->decr($ante, 'view', 1);
			}
		}
		if($aSet['player'] && ($aSet['player']< 0)){
			$aSet['player'] = 0;
			$game_ante->set($ante, array('play'=>0));
		}
		if($aSet['viewer'] && ($aSet['viewer']< 0)){
			$aSet['viewer'] = 0;
			$game_ante->set($ante, array('view'=>0));
		}
		oo::mongo('mongo')->update(mongoTable::papplePlaying(), array('_id'=>$ante) , array('$set'=>$aSet));
	}
}
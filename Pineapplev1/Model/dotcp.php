<?php
/**
* 在work进程处理TCP请求
*/
class ModelDoTcp{
	public $fd;
	private $a0x202 = array();
	
	/**
	 * 心跳
	 */
	public function tcp_0x2(){
		$this->tcp_0x109();
	}
	
	/**
	 * 追踪
	 */
	public function tcp_0x100(){
		$this->tcp_0x101(1);
	}
	/**
	 * 登录接口
	 */
	public function tcp_0x101($isTrack = 0){
		global $mid_table,$fd_table,$game_table,$tid_atomic;
		$mid = oo::main()->readPackage->ReadInt();
		$ante = oo::main()->readPackage->ReadInt();//底注
		
		
		//登录逻辑
		$mtkey = oo::main()->readPackage->ReadString();//mtkey
		
		if(!$aOnline = oo::member()->checkMtkey( $mid, $mtkey)){//mtkey 验证不通过
			oo::main()->adminLog($mid.' mtkey err');
			//return $this->fun0x202(3);
		}
		if($aOnline['mstatus'] == 1){//已经被封号了
			return $this->fun0x202(7);
		}
		if($aOnline['tid'] && ($aOnline['tid'] != $tid_atomic->get())){
			oo::main()->adminLog($mid.' 在别的游戏了');
			return $this->fun0x202(5);
		}
		if($aOnline['svid'] && ($aOnline['svid'] != oo::$cfg['svid'])){
			oo::main()->adminLog($mid.' 在其他svid');
			return $this->fun0x202(5);
		}
		$mInfoStr = oo::main()->readPackage->ReadString();//用户资料
		
		//判断用户是否在桌子里面
		$aMidTable = $mid_table->get($mid);
		if($aMidTable){
			$aMidTable['tid'] && $tid = $aMidTable['tid'];//如果在桌子里面 进到先前的桌子中去
			if($aMidTable['fd'] && ($aMidTable['fd'] != $this->fd)){//关闭上次的连接
				$fd_table->del($aMidTable['fd']);
				$this->relogin($aMidTable['fd']);
			}
		}
		if($tid && !$game_table->exist($tid)){//桌子不存在了
			$tid = 0;
		}
		if(!$tid){
			if($isTrack){
				$fmid = $ante;
				$aFMidTable = $mid_table->get($fmid);
				if($aFMidTable && $aFMidTable['tid']){
					$tid = $aFMidTable['tid'];
				}else{
					return $this->fun0x202(8);
				}
			}elseif(!$ante){
				return $this->fun0x202(4);
			}else{
				$tid = oo::findTable()->find($ante);
			}
			if(!$tid){//没有找到桌子ID
				oo::main()->adminLog($mid. "没有找到桌子ID");
				return $this->fun0x202(1);
			}
		}	
		oo::main()->adminLog($mid. "找到桌子". $tid . 'fd:' .$this->fd);
//		if($ante  && !oo::member()->updateOnlineInfo($mid, array('mtstatus'=>1,'svid'=>oo::$cfg['svid'], 'tid'=>$tid_atomic->get()))){
//			oo::main()->adminLog($mid. "updateOnlineInfo ERR");
//			return $this->fun0x202(6);
//		}
		$mid_table->set($mid, array('fd'=>$this->fd, 'tid'=>$tid));
		$fd_table->set($this->fd, array('mid'=>$mid));
		oo::work()->task(array('game', 'userLogin', array($mid, $tid, $mInfoStr)), oo::work()->dispatch($tid));
	}
	
	
	
	/**
	 * 点击退出房间按钮
	 */
	public function tcp_0x102(){
		$this->doTcp('quitTable');
	}
	
	/**
	 * 点击准备按钮
	 */
	public function tcp_0x103(){
		$this->doTcp('userReady');
	}
	
	/**
	 * 点击坐下按钮
	 */
	public function tcp_0x104(){
		$this->doTcp('userSit');
	}
	
	/**
	 * 点击站起按钮
	 */
	public function tcp_0x105(){
		$this->doTcp('userStandUp');
	}
	
	/**
	 * 点击排队按钮
	 */
	public function tcp_0x106(){
		$this->doTcp('userQueue');
	}
	

	/**
	 * 提交Ｘ张牌
	 */
	public function tcp_0x107(){
		$aCard = array();
		$num = oo::main()->readPackage->ReadByte();
		for($i=0;$i<$num;$i++){
			$aCard[] = array(oo::main()->readPackage->ReadByte(), oo::main()->readPackage->ReadByte());//位置 牌
		}
		$this->doTcp('submitCard', $aCard);
	}
	
	/**
	 * 添加好友
	 */
	public function tcp_0x108(){
		$mid = oo::main()->readPackage->ReadInt();
		$this->doTcp('addFriend', array( 'toMid' => $mid, 'act'=>1));
	}
	
	/**
	 * 聊天发送接口
	 */
	public function tcp_0x3(){
		$chats = oo::main()->readPackage->ReadString();
		$this->doTcp('chat', array('chats'=>$chats));
	}
	
	/**
	 * 表情发送接口
	 */
	public function tcp_0x4(){
		$phiz = oo::main()->readPackage->ReadInt();
		$this->doTcp('phizSend', array('phiz'=>$phiz));
	}
	
	
	/**
	 * 心跳包
	 */
	private $aErrFd = array();
	public function tcp_0x109(){
		if(!$this->sHeartbeat){
			$writePackage = new GSWritePackage();
			$writePackage->WriteBegin(gameConst::S_HEARTBEAT);
			$writePackage->WriteByte(1);
			$writePackage->WriteEnd();
			$this->sHeartbeat = $writePackage->GetPacketBuffer();
		}
		$aMidInfo = $this->getMidInfo();
		$tid = $aMidInfo['tid'];
		oo::main()->adminLog($this->fd.' 心跳：'.$aMidInfo['mid']. ' '. $tid);
		if($tid){
			oo::main()->swoole->send($this->fd, $this->sHeartbeat);
		}else{
			$i = date('i')>>3;
			$temp = $this->aErrFd[$i];
			$this->aErrFd = array();//清理其他的
			$temp && $this->aErrFd[$i] = $temp;
			if($this->aErrFd[$i][$this->fd]++ > 0){//第2次才关闭连接
				oo::main()->adminLog($this->fd.' 关闭连接 '.$this->aErrFd[$i][$this->fd]);
				unset($this->aErrFd[$i][$this->fd]);
				oo::main()->swoole->close($this->fd);
			}
		}
	}
	/**
	 * 添加好友成功
	 */
	public function tcp_0x10a(){
		$mid = oo::main()->readPackage->ReadInt();
		$this->doTcp('addFriend', array( 'toMid' => $mid, 'act'=>2));
	}
	
	/**
	 * 换桌
	 */
	public function tcp_0x10b(){
		$this->doTcp('otherTable');
	}
	
	/**
	 * 提交一张牌型
	 */
	public function tcp_0x10c(){
		$aCard = array();
		$aCard = array(oo::main()->readPackage->ReadByte(), oo::main()->readPackage->ReadByte(), oo::main()->readPackage->ReadByte());//位置 牌
		$this->doTcp('submitOneCard', $aCard);
	}
	
	
	/**
	 * 提交多张牌型
	 */
	public function tcp_0x10d(){
		$pos = oo::main()->readPackage->ReadByte();
		$num = oo::main()->readPackage->ReadByte();
		$aCard = array();
		for($i=0;$i<$num;$i++){
			$aCard[] = oo::main()->readPackage->ReadByte();
		}
		$this->doTcp('submiMoreCard', array('pos'=>$pos, 'aCard' =>$aCard));
	}

	private function doTcp($method, $aParam = array()){
		$aMidInfo = $this->getMidInfo();
		$mid = $aMidInfo['mid'];
		$tid = $aMidInfo['tid'];
		$tid && oo::work()->task(array('game', $method, array($mid, $tid, $aParam)), oo::work()->dispatch($tid));
	}
	
	/**
	 * 管理命令
	 */
	public function tcp_0x888(){
		oo::admin()->init($this->fd);
	}
	
	private function getMidInfo(){
		global $mid_table,$fd_table;
		$fdInfo = $fd_table->get($this->fd);
		if(!$fdInfo['mid']){
			$this->fun0x202(2);
			return array();
		}
		$aMidInfo = $mid_table->get($fdInfo['mid']);
		$aMidInfo['mid'] = $fdInfo['mid'];
		return $aMidInfo;
	}
	
	/*
	 * 重复登录
	 */
	private function relogin($fd){
		if(!oo::main()->swoole->exist($fd)){
			return;
		}
		if(!$this->sRelogin){
			$writePackage = new GSWritePackage();
			$writePackage->WriteBegin(gameConst::S_RELOGIN);
			$writePackage->WriteByte(1);
			$writePackage->WriteEnd();
			$this->sRelogin = $writePackage->GetPacketBuffer();
		}
		oo::main()->swoole->send($fd, $this->sRelogin);
	}
	/**
	 * 登录失败
	 */
	private function fun0x202($err){
		if(!$this->a0x202[$err]){
			$writePackage = new GSWritePackage();
			$writePackage->WriteBegin(gameConst::S_LOGIN_FAIL);
			$writePackage->WriteByte($err);
			$writePackage->WriteEnd();
			$this->a0x202[$err] = $writePackage->GetPacketBuffer();
		}
		oo::main()->swoole->send($this->fd, $this->a0x202[$err]);
	}
}
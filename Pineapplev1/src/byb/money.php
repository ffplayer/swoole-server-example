<?php
//博雅币操作函数
class ModelMoneyExt{
	public $moneySta = '';//游戏币状态 
	public $moneyTid = 0;//扣钱时的tid
	public $moneySvid = 0;//扣钱时的svid
	/**
	 * 获取玩家身上可用的游戏币数
	 * @param Int $mid
	 * @return Boolean|int,失败返回false 
	 */
	public function getAvailableMoney( $mid ){
		if( !$mid = fun::uint( $mid ) ){
			return false;
		}
		$aOnline = oo::member()->onlineinfo( $mid );
		if(($aOnline['tid'] > 0) && ($aOnline['svid'] > 0) && ($aOnline['mtstatus'] == 2)){
			$this->moneyTid = $aOnline['tid'];
			$this->moneySvid = $aOnline['svid'];
			$aServer = oo::servers()->getOneServer( $aOnline['svid']);
			if( empty( $aServer ) ){
				$this->moneySta = "sta:5;svid:{$aOnline['svid']};tid:{$aOnline['tid']};aServer err";
				return false;
			}
			$svip = $aServer['svlip'] ? $aServer['svlip'] : $aServer['svip'];

			$money = oo::CServer()->getAvailableMoney( $mid, $svip, $aServer['svport'] );
			$this->moneySta = "sta:1;svid:{$aOnline['svid']};tid:{$aOnline['tid']};money:{$money}";
			return ( $money === false ) ? false : (int) $money;
		}
		if( !$aRes = oo::MServer()->GetRecord( $mid, true) ){ //正确取到资料
			$this->moneySta =  "CServer GetRecord err";
			return false;
		}
		return (int)$aRes['mmoney'];
	}
	
	public $addWinMsg = '';//错误信息
	public function addWin($mid, $wmode, $wflag, $wchips){
		if( !$mid = fun::uint( $mid ) ){
			$this->addWinMsg = 'mid error';
			return false;
		}
		$this->moneyTid = 0;
		$this->moneySvid = 0;
		$wmode = fun::uint( $wmode );
		$wflag = fun::uint( $wflag );
		$wchips = fun::uint( $wchips );

		$mretstring = '';
		for($try = 0; $try < 3; $try++){
			$mmoney =  $this->setMoney( $mid, $wchips, $wflag, $wmode, oo::$logSid);//$mmoney存储最新的游戏币数
			if($mmoney !== false){
				break;
			}
			$mRetCode = (int) oo::MServer()->getRetCode();
			$tryTimes = $try + 1;
			$mretstring .= "try:{$tryTimes};mRetCode:{$mRetCode};";
			if( ! in_array( $mRetCode, array( 1, 4, 5 ) ) ){
				break;
			}
		}
		if($try > 0){
			$paysta = ( $mmoney === false ) ? '重试失败' : '重试成功';
			$mretstring .= "paysta:{$paysta}";
			Swoole_Log('addwin.error', date( 'Y-m-d H:i:s' ) . "-addwinError-mid:{$mid};wmode:{$wmode};chips:{$wchips};{$mretstring}");
		}
		
		if($mmoney === false){
			return false;
		}
		return (int)$mmoney;
	}
	
	public function setMoney($mid, $subMoney, $sflag, $wmode = 0, $sid = 0, $fromMSer = true){
		if( !$mid = fun::uint( $mid ) ){
			$this->addWinMsg = 'mid error';
			return false;
		}
		if( !$subMoney = fun::uint( $subMoney ) ){
			$this->addWinMsg = 'money error';
			return false;
		}
		$smoney = ($sflag == 0) ? $subMoney : -1 * $subMoney;
		$addmoney = ($sflag == 0) ? $subMoney : -1 * $subMoney;
		
		if( !$sid ){ //不存在SID不给加钱操作
			$this->addWinMsg = 'sid error';
			Swoole_Log('setmoney.err',  array( 'mid' => $mid, 'msg' => 'sid err', 'date' => date( 'Y-m-d H:i:s' ) ));
			return false;
		}
		if( !$aRes = oo::MServer()->GetRecord( $mid, $fromMSer ) ){ 
			$this->addWinMsg = 'GetRecord error';
			Swoole_Log('setmoney.err',  array( 'res' => $aRes, 'mid' => $mid, 'msg' => 'getdatainserver fail', 'date' => date( 'Y-m-d H:i:s' ) ));
			return false;
		}
		
		if( $aRes['mmoney'] + $smoney < 0 ){ //余额不足
			$this->addWinMsg = '余额不足'.$aRes['mmoney'].' '.$smoney;
			return false;
		}
		$aInfo = array( );
		$aInfo['mmoney'] = $smoney;
		$aInfo['wmode'] = $wmode;
		$aInfo['addmoney'] = $addmoney;
		$aInfo['sid'] = $sid;
        if( ! oo::MServer()->UpdateRecord( $mid, $aInfo, 0 ) ){ //此次更新失败
			$this->addWinMsg = 'UpdateRecord error';
			Swoole_Log('setmoney.err', json_encode(array( 'res' => $aRes, 'mid' => $mid, 'smoney' => $smoney, 'wmode' => $wmode, 'msg' => 'updaterecord fail', 'date' => date( 'Y-m-d H:i:s' ) ) ));
			return false;
		}
		return (int) ($aRes['mmoney'] + $smoney);
	}
}
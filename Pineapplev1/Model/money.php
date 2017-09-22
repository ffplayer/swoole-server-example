<?php
class ModelMoney{
	/**
	 * 获取玩家身上可用的游戏币数
	 * @param Int $mid
	 * @return Boolean|int,失败返回false 
	 */
	public function getAvailableMoney( $mid ){
		if( !$mid = fun::uint( $mid ) ){
			return false;
		}
		try{
			if( !$aRes = oo::MServer()->GetRecord( $mid, true) ){ //正确取到资料
				return false;
			}
			return (int)$aRes['mmoney'];
		} catch (Exception $ex){
			oo::main()->logs($ex->getMessage(), 'moneyErr');
			return false;
		}
	}
	
	public $addWinMsg = '';//错误信息
	public $tid = 0;
	public $bid = 0;
	/**
	 * $wflag 0 加 1 减少
	 */
	public function addWin($mid, $wmode, $wflag, $wchips){
		$this->addWinMsg = '';
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
			$ret =  $this->setMoney( $mid, $wchips, $wflag, $wmode, oo::user($mid)->getGi('sid'));//$mmoney存储最新的游戏币数
			if($ret !== false){
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
			$paysta = ( $ret === false ) ? '重试失败' : '重试成功';
			$mretstring .= "paysta:{$paysta}";
			Swoole_Log('addwin.error', date( 'Y-m-d H:i:s' ) . "-addwinError-mid:{$mid};wmode:{$wmode};chips:{$wchips};{$mretstring}");
		}
		
		if($ret === false){
			return false;
		}
		return true;
	}
	
	private function setMoney($mid, $subMoney, $sflag, $wmode = 0, $sid = 0){
		if( !$subMoney = fun::uint( $subMoney ) ){
			$this->addWinMsg = 'money error';
			return false;
		}
		$smoney = ($sflag == 0) ? $subMoney : -1 * $subMoney;
		
		if( !$sid ){ //不存在SID不给加钱操作
			$sid = TSWOOLE_SID;
		}
		try{
			$aInfo = array( );
			$aInfo['mmoney'] = $smoney;
			$aInfo['wmode'] = $wmode;
			$aInfo['sid'] = $sid;
			$aInfo['tid'] = $this->tid;
			$aInfo['bid'] = $this->bid;
	        if( ! oo::MServer()->UpdateMoney( $mid, $aInfo) ){ //此次更新失败
				$this->addWinMsg = 'UpdateRecord error';
				Swoole_Log('setmoney.err', json_encode(array( 'mid' => $mid, 'smoney' => $smoney, 'wmode' => $wmode, 'msg' => 'updaterecord fail', 'date' => date( 'Y-m-d H:i:s' ) ) ));
				return false;
			}
			return true;
		}catch (Throwable $ex){
			oo::main()->logs($ex->getMessage(), 'addWinErr');
			return false;
		}
	}
}
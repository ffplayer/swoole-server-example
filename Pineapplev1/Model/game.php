<?php
/**
* 游戏逻辑
*/
class ModelGame{
	public $stopGame = 0;//是否游戏停服状态
	/**
	 * 用户登录
	 */
	public function userLogin($aData){
		list($mid, $tid, $mInfoStr) = $aData;
		oo::main()->adminLog('userLogin>>'.$mid.' '.$tid);
		oo::tables($tid)->userLogin($mid, $mInfoStr);
	}
	
	/**
	 * 用户主动坐下
	 */
	public function userSit($aData){
		list($mid, $tid) = $aData;
		oo::tables($tid)->userSit($mid);
	}
	
	/**
	 * 用户站起
	 */
	public function userStandUp($aData){
		list($mid, $tid) = $aData;
		oo::tables($tid)->userStandUp($mid);
	}
	
	/**
	 * 用户退出房间
	 */
	public function quitTable($aData){
		list($mid, $tid) = $aData;
		oo::tables($tid)->quitTable($mid, $tid);
	}
	
	/**
	 * 用户点击排队
	 */
	public function userQueue($aData){
		list($mid, $tid) = $aData;
		oo::tables($tid)->userQueue($mid);
	}
	
	/**
	 * 用户点击准备
	 */
	public function userReady($aData){
		list($mid, $tid) = $aData;
		oo::tables($tid)->userReady($mid);
	}
	
	public function otherTable($aData){
		list($mid, $tid) = $aData;
		oo::tables($tid)->otherTable($mid);
	}
	
	public function enterRoom($aData){
		list($mid, $tid, $mInfoStr) = $aData;
		oo::tables($tid)->userLogin($mid, $mInfoStr);
	}
	
	/**
	 * 用户提交X张牌
	 */
	public function submitCard($aData){
		list($mid, $tid, $aCard) = $aData;
		oo::tables($tid)->submitCard($mid, $aCard);
	}
	/**
	 * 用户提交1张牌
	 */
	public function submitOneCard($aData){
		list($mid, $tid, $aCard) = $aData;
		oo::tables($tid)->submitOneCard($mid, $aCard);
	}
	/**
	 * 用户提交多张牌
	 */
	public function submiMoreCard($aData){
		list($mid, $tid, $aD) = $aData;
		oo::tables($tid)->submiMoreCard($mid, $aD);
	}
	/**
	* 操作牌型超时了
	*/
	public function pokerOutTime(array $aData){
		$tid = $aData['tid'];
		oo::tables($tid)->pokerOutTime();
	}
	
	/**
	 * 游戏结算之后 准备超时
	 */
	public function startReadyOutTime(array $aData){
		$tid = $aData['tid'];
		oo::tables($tid)->readyOutTime();
		oo::main()->adminLog($tid.' readyOutTime');
	}
	
	/**
	 * 范特西超时了
	 */
	public function ftxOutTime($aData){
		$tid = $aData['tid'];
		$mid = $aData['mid'];
		oo::tables($tid)->ftxOutTime($mid);
	}
	
	/**
	* 游戏结束
	*/
	public function playOver($tid){
		oo::tables($tid)->playOver();
		oo::main()->adminLog($tid.' playOver');
	}
	
	public function addFriend($aData){
		list($mid, $tid, $aInfo) = $aData;
		oo::tables($tid)->addFriend($mid, $aInfo);
	}
	
	
	/**
	 * 停服命令
	 */
	public function stopGame(){
		$this->stopGame = 1;
		$aTable = oo::data()->getTables();
		foreach($aTable as $tid=>$Info){
			oo::tables($tid)->stopGame();
		}
	}
	
	/**
	 * 加载配置
	 */
	public function reloadCfg($tid){
		oo::task()->loadTypeScore();
		oo::tables($tid)->reloadCfg();
	}
	
	/**
	 * 重新加载系统配置
	 */
	public function reloadSysCfg(){
		oo::initCfg(1);
	}
	
	/**
	 * 用户断线了
	 */
	public function onClose($aData){
		list($mid, $tid) = $aData;
		oo::tables($tid)->onClose($mid);
	}
	
	/**
	 * 用户断线定时处理
	 */
	public function userClose($aData){
		$tid = $aData['tid'];
		$mid = $aData['mid'];
		oo::tables($tid)->userClose($mid);
	}
	
	/**
	 * 聊天了
	 */
	public function chat($aData){
		list($mid, $tid, $aChat) = $aData;
		oo::tables($tid)->chat($mid, $aChat);
	}
	
	/**
	 * 发送聊天表情
	 */
	public function phizSend($aData){
		list($mid, $tid, $aPhiz) = $aData;
		oo::tables($tid)->phizSend($mid, $aPhiz);
	}
}
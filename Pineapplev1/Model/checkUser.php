<?php
/**
* 用户检测
*/
class ModelCheckUser{
	/**
	 * 每隔检测一次
	 */
	public function checkRun(){
		$aUser = oo::data()->getUsers();
		oo::main()->adminLog('CheckUser run 人数：'.count($aUser));
		$time = time();
		$checkTime = 30*60;//30*60
		foreach($aUser as $mid=>$aInfo){
			if($time - $aInfo['mtime'] > $checkTime){
				$this->doUser($mid, $aInfo['tid']);
			}
		}
	}
	
	/**
	 * 用户已经长久没有操作了
	 */
	private function doUser($mid, $tid=0){
		oo::main()->adminLog('CheckUser quitTable：'.$mid. ' '.$tid);
		if($tid){
			$midInfo = oo::user($mid)->getMidTableInfo();
			if(($midInfo['tid'] == $tid) && oo::main()->swoole->exist($midInfo['fd'])){
				oo::user($mid)->set('mtime', time());
				return;
			}
			if(!oo::tables($tid)->quitTable($mid)){//如果退出房间失败 基本是卡房间了
				oo::main()->logs(array('doUser', $mid, $tid, oo::user($mid)->get('isPlay')), 'quitTableErr' , 1);
			}
		}else{
			oo::user($mid)->del();
		}
	}
}
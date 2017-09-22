<?php
/**
* 在work进程做的事
*/
class ModelWork{
	public function workstart(){
		$workId = oo::main()->swoole->worker_id;
		if($workId == 1){
			$this->loadAnte();//加载底注信息
		}
	}
	
	/**
	 * 初始化时加载桌子信息
	 */
	public function loadAnte(){
		global $game_ante, $tid_atomic,$game_table;
		$aData = oo::mongo('mongo')->findOne(mongoTable::pappleCfg(), array('_id'=>'PAPPLECFG'), array('tables', 'tid'));
		$aAnte = array();
		$aData['tid'] && $tid_atomic->set($aData['tid']);
		if($aTable = (array)$aData['tables']){
			foreach($aTable as $data){
				if($data['svid'] != oo::$cfg['svid']){
					continue;
				}
				$aAnte[] = $ante = $data['num'];
				$acfg = array('times'=>$data['times'], 'mincarry'=>$data['mincarry']);
				if($data['coststype'] == 1){
					$acfg['fee'] = ceil($ante*$data['venuecosts']);
				}else{
					$acfg['winCosts'] = $data['venuecosts'];
				}
				$aSet = array('cfg'=> Fun::serialize($acfg));
				oo::main()->adminLog("load ante:".$ante);
				$game_ante->set($ante, $aSet);
			}
			$aDel = array();
			foreach($game_ante as $ante => $val){//删除已经删除的
				if(!in_array($ante, $aAnte)){
					$game_ante->del($ante);
					$aDel[] = $ante;
				}
			}
			if(count($aDel)){//删除已经不存在的桌子
				foreach($game_table as $tid=>$data){
					if(in_array($data['ante'],$aDel)){
						$game_table->del($tid);
					}
				}
			}
		}
	}
	
	
	/**
	 * 用户断线了
	 */
	public function onClose($fd){
		oo::main()->adminLog($fd . 'onClose');
		global $mid_table,$fd_table;
		$fdInfo = $fd_table->get($fd);
		if(!$mid = $fdInfo['mid']){
			return;
		}
		$fd_table->del($fd);
		$aMidInfo = $mid_table->get($mid);
		$tid = $aMidInfo['tid'];
		if(!$tid){
			$mid_table->del($mid);
			return;
		}
		oo::work()->task(array('game', 'onClose', array($mid, $tid)), oo::work()->dispatch($tid));
	}
	
	/**
	 * 分配task进程
	 * @param type $dispatch 当前以tid/mid来求模分配
	 */
	public function dispatch($dispatch=0){
		$dispatch = intval($dispatch);
		$taskNum = oo::main()->swoole->setting['task_worker_num'];
		//$taskId = $dispatch % ($taskNum - 1) + 1;
		$taskId = $dispatch % $taskNum;
		return $taskId;
	}
	
	/**
	*  统一丢task
	*/
	public function task($data, $dst_worker_id=-1){
		oo::main()->swoole->task($data, $dst_worker_id);
	}
	
	/**
	 * 收到停服命令了
	 */
	public function stopGame(){
		for($tid=0;$tid<oo::main()->swoole->setting['task_worker_num'];$tid++){
			oo::work()->task(array('game', 'stopGame'), $tid);
		}
	}
	/**
	 * 重新加载配置
	 */
	public function reloadCfg(){
		$this->loadAnte();
		$this->toAllTable('reloadCfg');
	}
	/**
	 * 重新加载系统配置
	 */
	public function reloadSysCfg(){
		for($tid=0;$tid<oo::main()->swoole->setting['task_worker_num'];$tid++){
			oo::work()->task(array('game', 'reloadSysCfg'), $tid);
		}
	}
	
	private function toAllTable($fun='reloadCfg'){
		global $game_table;
		foreach($game_table as $tid=>$info){
			oo::work()->task(array('game', $fun, $tid), oo::work()->dispatch($tid));
		}
	}
	
}
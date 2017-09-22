<?php
/**
* task进程 做事的
*/
class ModelTask{
	//public $fromId = 0;
	
	public function workstart(){
		$this->loadData();
		oo::main()->swoole->tick(1000, array('TimerHandler', 'tigger'));
		$workId = oo::main()->swoole->worker_id;
		if($workId%oo::main()->swoole->setting['task_worker_num'] == 0){
			oo::main()->swoole->tick(2*1000, array(oo::findTable(), 'doRun'));
		}
		oo::main()->swoole->tick(20*60*1000, array(oo::checkUser(), 'checkRun'));//每隔20分钟检测用户
		oo::main()->swoole->tick(3*60*1000, array(oo::task(), 'checkTable'));//每隔3分钟检测桌子信息
	}
	/**
	 * 启动的时候加载配置
	 */
	private function loadData(){
		$this->loadTypeScore();
		global $crontab_work_table;
		$workId = oo::main()->swoole->worker_id;
		$ver = $crontab_work_table->incr($workId, 'ver', 0);
		$file = SERVER_ROOT . 'data/sys_'.$workId. '_'. $ver .'.php';
		if(file_exists($file)){
			$aList = include $file;
			TimerHandler::$aEvent = json_decode($aList['timer'], true);
			CardsSender::$_cardList = json_decode($aList['cardsSender'], true);
			oo::data()->setTables(json_decode($aList['table'] , true));
			oo::data()->setUsers(json_decode($aList['user'] , true));
			oo::main()->logs('loadData|'.$ver, 'task');
			rename($file, SERVER_ROOT . 'data/sys_'.$workId. '_'. $ver .'.bak.php');//防止挂的时候没写 重新用了
		}
	}
	
	/**
	 * 加载牌型对应分值
	 */
	public function loadTypeScore(){
		$aData = oo::mongo('mongo')->findOne(mongoTable::pappleCfg(), array('_id'=>'PAPPLECFG'), array('cardtype'));
		if(!$aCardType = (array)$aData['cardtype']){
			return;
		}
		$aTypeScore = array();
		foreach($aCardType as $cT){
			if($cT['card']){
				$aTypeScore[$cT['road']][$cT['type']][$cT['card']] = $cT['score'];
			}else{
				$aTypeScore[$cT['road']][$cT['type']]['any'] = $cT['score'];
			}
		}
		CardsArbiter::$_specailTypeScore = $aTypeScore;
	}
	/**
	 * 重启之前保存数据
	 */
	public function workerStop($workId){
		global $crontab_work_table;
		$aList['timer'] = json_encode(TimerHandler::$aEvent);//定时任务
		$aList['cardsSender'] = json_encode(CardsSender::$_cardList);//正在发的牌
		$aTables = oo::data()->getTables();
		$aUsers = oo::data()->getUsers();
		$aList['table'] = json_encode($aTables);
		$aList['user'] = json_encode($aUsers);
		$ver = $crontab_work_table->incr($workId, 'ver', 1);
		$file = SERVER_ROOT . 'data/sys_'.$workId. '_'. $ver .'.php';
		file_exists($file) && unlink($file); //先删除在写入
		$sList = var_export($aList, true);
		$content = '<?php' . "\n" . 'return ' . $sList . ';';
		file_put_contents($file, $content);
		oo::main()->logs('workerStop|'.$ver, 'task');
	}
	
	/**
	* onTask调用函数
	* $aData array
	*/
	public function init($aData){
		$class = $aData[0];
		$class = oo::$class();
		$method = $aData[1];
		$class->$method($aData[2]);
		oo::main()->adminLog('taskInit '.$aData[0].' '. $aData[1]);
		
		oo::main()->destoryCache();
	}
	
	public function sendMessage($aMsg, $dispatch){
		$dst_worker_id = $this->getTaskId($dispatch);
		if($dst_worker_id == oo::main()->swoole->worker_id){
			oo::task()->init($aMsg);
		}else{
			oo::main()->swoole->sendMessage(json_encode($aMsg), $dst_worker_id);
		}
	}
	
	public function getTaskId($dispatch=0){
		return oo::work()->dispatch($dispatch) + oo::main()->swoole->setting['worker_num'];
	}
	
	/**
	 * 每隔3分钟检测桌子
	 */
	public function checkTable(){
		$aTable = oo::data()->getTables();
		$time = time();
		foreach($aTable as $tid=>$info){
			$ltime = $time - $info['mtime'];
			if($info['status'] && ($ltime > 100)){
				$msg  = "大菠萝桌子异常,tid:{$tid},status:".$info['status'].",用户：".json_encode($info['aSeat']);
				TSWOOLE_ENV && oo::Transit()->warning($msg);
			}elseif(($ltime > 3600) && (count($info['aAllMid']) == 0)){//60分钟没有更新过了
				oo::data()->delTableData($tid);
			}
		}
	}
}
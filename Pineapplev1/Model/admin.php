<?php
define('ADMINKEY', '380ea7150da15633f5a6536e4d15030e');
/**
* 管理员接口
*/
class ModelAdmin{
	/*
	* 调用入口
	*/
	public function init($fd){
		//判断是否为局域网ip
		$cinfo = oo::main()->swoole->connection_info($fd);
		$localIp = $cinfo['remote_ip'];
		$ip = ip2long($localIp);
		if (!($ip == 2130706433 || $ip >> 24 === 10 || $ip >> 20 === 2753 || $ip >> 16 === 49320)){
			oo::main()->swoole->close($fd);
			return;
		}
		$ckey = oo::main()->readPackage->ReadString();
		if(md5($ckey) != ADMINKEY){
			oo::main()->swoole->close($fd);
			return;
		}
		$cmd = oo::main()->readPackage->ReadByte(); //命令
		$data = 'ok';
		switch ($cmd) {
			case 1://只重启task进程
				oo::main()->swoole->reload(true);
				break;
			case 2://重启所有进程
				oo::main()->swoole->reload();
				break;
			case 3: //查看系统信息
				$aServerInfo = oo::main()->GetMonitorInfo();
				$data = json_encode($aServerInfo);
				break;
			case 4://重新加载系统配置
				oo::work()->reloadSysCfg();
				break;
			case 5: //重新加载配置
				oo::work()->reloadCfg();
				break;
			case 6://停服命令
				oo::work()->stopGame();
				break;
			case 7://设置牌
				if(!TSWOOLE_ENV){
					$mid = oo::main()->readPackage->ReadInt();
					$card = oo::main()->readPackage->ReadString();
					global $mid_table;
					$aMidTable = $mid_table->get($mid);
					if($tid = $aMidTable['tid']){
						oo::work()->task(array('admin', 'adminSetCard', array($tid, $card)), oo::work()->dispatch($tid));
					}else{
						$data = "用户不在桌子里面";
					}
				}else{
					$data = "err";
				}
				break;
			case 8://获取用户所在桌子信息
				$mid = oo::main()->readPackage->ReadInt();
				global $mid_table;
				$aMidTable = $mid_table->get($mid);
				if($tid = $aMidTable['tid']){
					return oo::work()->task(array('admin', 'getMidTableInfo', array($tid, $fd)), oo::work()->dispatch($tid));
				}else{
					$data = "用户不在桌子里面";
				}
				break;
			case 9://强制踢人 重新设置桌子
				$tid = oo::main()->readPackage->ReadInt();
				if($tid){
					return oo::work()->task(array('admin', 'tableReset', array($tid, $fd)), oo::work()->dispatch($tid));
				}
				break;
			case 10://强制数据写文件
				for($tid=0;$tid<oo::main()->swoole->setting['task_worker_num'];$tid++){
					oo::work()->task(array('admin', 'dataToFile'), $tid);
				}
				break;
		}
		$this->sendPack($fd, $data);
	}
	
	/**
	 * 将数据回写到文件中
	 */
	public function dataToFile(){
		$aList['timer'] = json_encode(TimerHandler::$aEvent);//定时任务
		$aList['cardsSender'] = json_encode(CardsSender::$_cardList);//正在发的牌
		$aTables = oo::data()->getTables();
		$aUsers = oo::data()->getUsers();
		$aList['table'] = json_encode($aTables);
		$aList['user'] = json_encode($aUsers);
		$file = SERVER_ROOT . 'data/sysData_'.oo::main()->swoole->worker_id. '_'. date('mdHi') .'.php';
		file_exists($file) && unlink($file); //先删除在写入
		$sList = var_export($aList, true);
		$content = '<?php' . "\n" . 'return ' . $sList . ';';
		file_put_contents($file, $content);
	}
	
	private function sendPack($fd, $data){
		$wrpack = new GSWritePackage();
		$wrpack->WriteBegin(0x888);
		$wrpack->WriteString($data);
		$wrpack->WriteEnd();
		oo::main()->swoole->send($fd, $wrpack->GetPacketBuffer());
	}
	
	
	/**
	 * 管理员设置牌
	 */
	public function adminSetCard($aData){
		list($tid, $scard) = $aData;
		$aCard = explode(',', $scard);
		if(count($aCard) > 28){
			foreach($aCard as &$v){
				$v = (int)$v;
			}
			CardsSender::$_cardtempList[$tid] = $aCard;
		}else{
			unset(CardsSender::$_cardtempList[$tid]);
		}
	}
	
	/**
	 * 获取用户所在桌子信息
	 */
	public function getMidTableInfo($aData){
		list($tid, $fd) = $aData;
		$aList['tid'] = $tid;
		$aList['table'] = oo::data()->getTableData($tid);
		foreach($aList['table']['aSeat'] as $seatId =>$mid){
			if($mid){
				$aList['user'][$mid] = oo::data()->getUserData($mid);
			}
		}
		$data = json_encode($aList);
		$this->sendPack($fd, $data);
	}
	
	/**
	 * 强制踢人 修改桌子状态
	 */
	public function tableReset($aData){
		list($tid, $fd) = $aData;
		$aTable = oo::data()->getTableData($tid);
		$aList = array();
		foreach($aTable['aSeat'] as $seatId =>$mid){
			if($mid){
				oo::user($mid)->set('isPlay', 0);
				$aList[$mid]['ret'] = oo::tables($tid)->quitTable($mid);
			}
		}
		oo::data()->delTableData($tid);
		$data = json_encode($aList);
		$this->sendPack($fd, $data);
	}
}
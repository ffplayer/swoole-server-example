<?php
/**
* 伪存储
*/
class ModelProc{
	private $aRedis = array();
	public  function send($aData){
		list($sid, $method, $args) = $aData;
		if(!ModelUdp::$cfg['procRedis'][$sid]){
			ModelUdp::logs(array('procErr', 2, date('Y-m-d H:i:s').' '.$sid.' Err', 0));
			return false;
		}
		$args = (array)$args;
		foreach($args as &$v){
			if(is_string($v)){
				$v = "'{$v}'";
			}elseif(is_array($v)){
				$v = "'". implode(',', $v) ."'";
			}elseif(is_bool($v)){
				$v = $v ? 1 : 0;
			}elseif(is_null($v)){
				$v = 0;
			}
		}
		$str =  "call ".$method."(".implode(',', $args).")";
		if(!$this->getRedis($sid)->lPush('PROC|'.$sid, $str)){
			ModelUdp::logs(array('procErr_'.$sid, 2, $str, 0));
		}
	}
	
	public function getRedis($sid){
		if(is_object($this->aRedis[$sid])){
			return $this->aRedis[$sid];
		}
		return $this->aRedis[$sid] = new muredis(ModelUdp::$cfg['procRedis'][$sid]);//array('192.168.202.200', '19001')
	}
}
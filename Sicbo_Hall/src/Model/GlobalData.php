<?php
namespace Model;
/**
 * 封装swoole跨进程全局数据操作
 */
class GlobalData{
	public static function getConnectInfoWithFd($fd){
		global $fd_table, $mid_table;
		$ret = $fd_table->get($fd);
		if (false == $ret){
			return null;
		}
		$mid = $ret['mid'];
		
		$ret  = $mid_table->get($mid);
		if (false == $ret){
			return null;
		}
		$rid = $ret['rid'];
		return array('fd'=>$fd, 'mid'=>$mid, 'rid'=>$rid);
	}
	
	public static function getConnectInfoWithMid($mid){
		global $fd_table, $mid_table;
		$ret = $mid_table->get($mid);
		if (false == $ret){
			return null;
		}
		return array('fd'=>$ret['fd'], 'mid'=>$mid, 'rid'=>$ret['rid']);
	}
	
	public static function existConnectInfoWithMid($mid){
		global $mid_table;
		return $mid_table->exist($mid);
	}
	
	public static function delConnectInfoWithFd($fd){
		global $fd_table, $mid_table;
		$connectInfo = self::getConnectInfoWithFd($fd);
		if (is_array($connectInfo)){
			$mid_table->del($connectInfo['mid']);
		}
		$fd_table->del($fd);
	}
	
	public static function delConnectInfoWithMid($mid){
		global $fd_table, $mid_table;
		$connectInfo = self::getConnectInfoWithMid($mid);
		if (is_array($connectInfo)){
			$fd_table->del($connectInfo['fd']);
		}
		$mid_table->del($mid);
	}
	
	public static function setConnectInfo($fd, $mid, $rid){
		global $fd_table, $mid_table;
		$fd_table->set($fd, array('mid'=>$mid));
		$mid_table->set($mid, array('fd'=>$fd, 'rid'=>$rid));
	}
	
	
	public static function getServerStatus(){
		global $server_status;
		return $server_status->get();
	}
	public static function setServerStatus($st){
		global $server_status;
		$server_status->set($st);
	}
}
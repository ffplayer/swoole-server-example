<?php
namespace Lib;
//中转server调用
class Transit{
	private $sid;
	private $ip = '127.0.0.1';
	private $port = 6001;
	public function __construct($sid, $aServer = array()){
		$this->sid = $sid;
		if($aServer){
			list($this->ip, $this->port) = $aServer;
		}
	}
	/**
	 * 上报mf
	 */
	public function mf($sid, $mid, $table, $aData, $time=0){
		$aData = array($sid, $mid, $table, $aData, $time);
		return $this->send(0x101, $aData);
	}
	
	/**
	 * 上报dc
	 */
	public function dc($sid, $act_name, $gdata){
		$aData = array($sid, $act_name, $gdata);
		return $this->send(0x102, $aData);
	}
	
	/**
	 * 上报日志
	 */
	public function logs($fname, $string, $fsize = 1, $isBak = 0){
		$aData = array($fname, $fsize, $string, $isBak);
		return $this->send(0x103, $aData);
	}
	
	/**
	 * 伪存储
	 */
	public function proc($method, $args){
		$aData = array($this->sid, $method, $args);
		return $this->send(0x104, $aData);
	}
	
	/**
	 * 报警
	 */
	public function warning($content){
		$aData = array($this->sid, $content);
		return $this->send(0x105, $aData);
	}
	
	private function send($cmd, $aData){
		$data = $cmd.'*$*'.gzcompress(json_encode($aData) ,9);
		$client = new \swoole_client(SWOOLE_SOCK_UDP, SWOOLE_SOCK_SYNC);
		$client->connect($this->ip, $this->port);
		return $client->send($data);//发送成功
	}
}
<?php
include_once TSWOOLE_INC . 'Protocols/GSPack/GSReadPackage.php';
include_once TSWOOLE_INC . 'Protocols/GSPack/GSWritePackage.php';
define('ADMINKEY', '380ea7150da15633f5a6536e4d15030e');
class ModelTcp{
	private static $swoole;
	private static $fd;
	private static $readPackage;
	public static function init($server, $fd, $data){
		if(!is_object(self::$readPackage)){
			self::$readPackage = new GSReadPackage();
		}
		self::$readPackage->ReadPackageBuffer($data);
		self::$swoole = $server;
		self::$fd = $fd;
		$cmd = self::$readPackage->GetCmdType();
		$method = 'tcp_' . $cmd;
		if(method_exists(ModelTcp, $method)){
			call_user_func(array(ModelTcp, $method));
		}
	}
	
	/**
	 * 管理员端口
	 */
	public static function tcp_0x888(){
		$cinfo = self::$swoole->connection_info(self::$fd);
		$localIp = $cinfo['remote_ip'];
		$ip = ip2long($localIp);
		if (!($ip == 2130706433 || $ip >> 24 === 10 || $ip >> 20 === 2753 || $ip >> 16 === 49320)){
			self::$swoole->close($fd);
			return;
		}
		$ckey = self::$readPackage->ReadString();
		if(md5($ckey) != ADMINKEY){
			self::$swoole->close($fd);
			return;
		}
		$cmd = self::$readPackage->ReadByte(); //命令
		$data = 'ok';
		switch ($cmd){
			case 2://重启进程
				self::$swoole->reload();
				break;
			case 3://获取信息
				$aData['stats'] = self::$swoole->stats();
				$aData['useMem'] = memory_get_usage(1) / 1024 / 1024;
				$data = json_encode($aData);
				break;
		}
		self::sendPack($data);
	}
	
	/**
	 * 发包
	 */
	private static function sendPack($data){
		$wrpack = new GSWritePackage();
		$wrpack->WriteBegin(0x888);
		$wrpack->WriteString($data);
		$wrpack->WriteEnd();
		self::$swoole->send(self::$fd, $wrpack->GetPacketBuffer());
	}
}
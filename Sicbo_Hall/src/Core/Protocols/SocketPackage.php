<?php
include_once __DIR__ . '/EncryptDecrypt.php';
/**
 * 适用于Tcp/UDP之间传递包读写
 *
 */
abstract class SocketPackage {
	
	const SERVER_PACEKTVER = 1;
	const SERVER_SUBPACKETVER = 2;
	const PACKET_BUFFER_SIZE = 8192;
	abstract function GetPacketBuffer();

	public function __construct(){

	}

	public function GetPacketSize() {
		return $this->m_packetSize;
	}

	public function GetCmdType() {
		return '0x' . dechex($this->CmdType);
	}

}

<?php
namespace Core\Protocols\MSPack;

/**
 * 适用于Tcp/UDP之间传递包读写
 *
 * @author JsonChen
 */
abstract class MSSocketPackage {
	
	const SERVER_PACEKTVER = 1;
	const SERVER_SUBPACKETVER = 2;
	const PACKET_BUFFER_SIZE = 8192;
	const PACKET_HEADER_SIZE = 13;
	abstract function GetPacketBuffer();

	public function __construct() {
	}

	public function GetPacketSize() {
		return $this->m_packetSize;
	}

	public function GetCmdType() {
		return '0x' . dechex($this->CmdType);
	}

}

<?php
include_once __DIR__ . '/SocketPackage.php';
/**
 * Description of ReadPackage
 *
 */
class ReadPackage extends SocketPackage {

	public $m_Offset = 0;

	public function ReadPackageBuffer($packet_buff){
	}

	public function GetPacketBuffer() {
		return $this->realpacket_buff;
	}

	public function GetLen() {
		return $this->package_realsize - $this->m_Offset;
	}

	public function ReadByte() {
		if ($this->package_realsize <= $this->m_Offset) {
			throw new Exception("读取溢出");
		}
		$temp = $this->m_packetBuffer->read($this->m_Offset, 1);
		if ($temp === false) {
			throw new Exception("读取溢出");
		}
		$value = unpack("C", $temp);
		$this->m_Offset+=1;
		return $value[1];
	}

	public function ReadShort() {
		if ($this->package_realsize <= $this->m_Offset) {
			throw new Exception("读取溢出");
		}
		$temp = $this->m_packetBuffer->read($this->m_Offset, 2);
		if ($temp === false) {
			throw new Exception("读取溢出");
		}
		$value = unpack("s", $temp);
		$this->m_Offset+=2;
		return $value[1];
	}

	public function ReadInt() {
		if ($this->package_realsize <= $this->m_Offset) {
			throw new Exception("读取溢出");
		}
		$temp = $this->m_packetBuffer->read($this->m_Offset, 4);
		if ($temp === false) {
			throw new Exception("读取溢出");
		}
		$value = unpack("i", $temp);
		$this->m_Offset+=4;
		return $value[1];
	}
	
	public function ReadInt64(){
		$low = $this->ReadInt();
		$high = $this->ReadInt();
		return $low | ($high>>32);
	}

	public function ReadUInt() {
		if ($this->package_realsize <= $this->m_Offset) {
			throw new Exception("读取溢出");
		}
		$temp = $this->m_packetBuffer->read($this->m_Offset, 4);
		if ($temp === false) {
			throw new Exception("读取溢出");
		}
		list(, $var_unsigned) = unpack("L", $temp);
		$this->m_Offset+=4;
		return floatval(sprintf("%u", $var_unsigned));
	}

	public function ReadString() {
		if ($this->package_realsize <= $this->m_Offset) {
			throw new Exception("读取溢出");
		}
		$len = $this->ReadInt();
		if ($len === false) {
			throw new Exception("读取溢出");
		}
		$realLen = $this->m_packetBuffer->length - $this->m_Offset;
		if ($realLen < $len - 1) {
			throw new Exception("读取溢出");
		}
		$value = $this->m_packetBuffer->read($this->m_Offset, $len - 1);
		$this->m_Offset+=$len;
		return $value;
	}	

}

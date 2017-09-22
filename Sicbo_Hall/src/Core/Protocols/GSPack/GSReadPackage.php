<?php
include_once __DIR__ . '/../ReadPackage.php';
/**
 * Description of ReadPackage
 *
 */
class GSReadPackage extends ReadPackage {
	const PACKET_HEADER_SIZE = 13;
	public function ReadPackageBuffer($packet_buff){
		if($packet_buff == ''){
			return -1;
		}
		$this->realpacket_buff = $packet_buff;
		if (!isset($this->m_packetBuffer)) {
			$this->m_packetBuffer = new swoole_buffer(9000);
		} else {
			$this->m_packetBuffer->clear();
		}
		$this->package_realsize = $this->m_packetBuffer->append($packet_buff);
		if ($this->package_realsize < self::PACKET_HEADER_SIZE) {
			//包头为9个字节
			return -1;
		}
		if ($this->package_realsize > self::PACKET_BUFFER_SIZE) {
			//包长度为2个字节，包内容最多65535个字节
			return -2;
		}
		$headerInfo = unpack("c2Iden/sCmdType/cVer/cSubVer/sLen/cCode/IInc", $this->m_packetBuffer->read(0, self::PACKET_HEADER_SIZE));
		if ($headerInfo['Len'] >= 0 && $headerInfo['Len'] != $this->package_realsize - self::PACKET_HEADER_SIZE) {
			//throw new VerifyException("非法包头",-3);
			return -3;
		}
		if ($headerInfo['Iden1'] != ord('I') || $headerInfo['Iden2'] != ord('C')) {
			//throw new VerifyException("非法包头",-4);
			return -4;
		}
		if ($headerInfo['Ver'] != self::SERVER_PACEKTVER) {
			//throw new VerifyException("非法包头",-5);
			return -5;
		}
		if ($headerInfo['CmdType'] <= 0 || $headerInfo['CmdType'] >= 32000) {
			//throw new VerifyException("非法包头",-6);
			return -6;
		}
		$this->CmdType = $headerInfo['CmdType'];
		$this->m_packetSize = $headerInfo['Len'];
		if ($this->m_packetSize) {
			$packetBuffer = $this->m_packetBuffer->read(self::PACKET_HEADER_SIZE, $this->m_packetSize);
			$DecryptObj = new EncryptDecrypt();
			$DecryptObj->DecryptBuffer($packetBuffer, $this->m_packetSize, $headerInfo['Code']);
			$this->m_packetBuffer->write(self::PACKET_HEADER_SIZE, $packetBuffer);
		}
		$this->m_Offset = self::PACKET_HEADER_SIZE;
		return 1;
	}
}

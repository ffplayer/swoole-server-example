<?php
include_once __DIR__ . '/../WritePackage.php';
class MSWritePackage extends WritePackage{
	public function WriteEnd() {
		$EncryptObj = new EncryptDecrypt();
		$content = $this->GetPacketBuffer();
		$code = $EncryptObj->EncryptBuffer($content, 0, $this->m_packetSize);
		$this->m_packetBuffer->clear();
		$this->m_packetBuffer->append("IC");
		$this->m_packetBuffer->append(pack("s", $this->CmdType));
		$this->m_packetBuffer->append(pack("c", self::SERVER_PACEKTVER));
		$this->m_packetBuffer->append(pack("c", self::SERVER_SUBPACKETVER));
		$this->m_packetBuffer->append(pack("s", $this->m_packetSize));
		$this->m_packetBuffer->append(pack("c", $code));
		$this->m_packetSize = $this->m_packetBuffer->append($content);
	}
}

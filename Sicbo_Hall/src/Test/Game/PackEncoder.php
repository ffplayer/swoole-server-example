<?php
namespace Test\Game;

class PackEncoder{
	public static function pack_login($mid){
		$unid = 13;
		$mtkey = 'aaaabb3d';
		$sendDataObj = new \GSWritePackage();
		$sendDataObj->WriteBegin(0x101);
		$sendDataObj->WriteInt($mid);
		$sendDataObj->WriteInt(50000);
		$sendDataObj->WriteShort(33);
		$sendDataObj->WriteShort(44);
		$sendDataObj->WriteString($unid);
		$sendDataObj->WriteString($mtkey);
		$sendDataObj->WriteEnd();
		return $sendDataObj->GetPacketBuffer();
	}
	
	public static function pack_logout(){
		$pack = new \GSWritePackage();
		$pack->WriteBegin(0x102);
		$pack->WriteByte(1);
		$pack->WriteEnd();
		return $pack->GetPacketBuffer();
	}
	
	public static function pack_sitDown($seatId){
		$pack = new \GSWritePackage();
		$pack->WriteBegin(0x110);
		$pack->WriteByte($seatId);
		$pack->WriteEnd();
		return $pack->GetPacketBuffer();
	}
	
	public static function pack_StandUp(){
		$pack = new \GSWritePackage();
		$pack->WriteBegin(0x111);
		$pack->WriteByte(0);
		$pack->WriteEnd();
		return $pack->GetPacketBuffer();
	}
	
	public static function pack_bet($areaId, $money){
		$pack = new \GSWritePackage();
		$pack->WriteBegin(0x201);
		$pack->WriteByte($areaId);
		$pack->WriteInt64($money);
		$pack->WriteEnd();
		return $pack->GetPacketBuffer();
	}
	
	public static function pack_repeatBet(){
		$pack = new \GSWritePackage();
		$pack->WriteBegin(0x202);
		$pack->WriteByte(0);
		$pack->WriteEnd();
		return $pack->GetPacketBuffer();
	}
	
	public static function pack_rollDice(){
		$pack = new \GSWritePackage();
		$pack->WriteBegin(0x207);
		$pack->WriteByte(0);
		$pack->WriteEnd();
		return $pack->GetPacketBuffer();
	}
	
}

<?php
include_once "comm.php";

$sendPoker = array(0x12, 0x13,0x14,0x34,0x45,0x46,0x47,0x48,0x48,0x32,0x33,0x34,0x35,0x36);
$pos = 3;
$sendPokerKey = array_rand($sendPoker, 13);
$i = 0;
foreach($sendPokerKey as $key){
	$aCard[$pos][] = dechex($sendPoker[$key]);
	if($pos == 1){
		$i++;
		$pos = 3;
	}elseif(($i >= 3) && ($pos == 2)){
		$pos = 3;
	}else {
		$pos--;	
	}
}
var_dump($aCard);
die();
$cardList = array(0x12, 0x13,0x14,0x34,0x45);
var_dump(CardsArbiter::getGameCardInfo(array(1=>array(), 2=>array(),3=>$cardList)));
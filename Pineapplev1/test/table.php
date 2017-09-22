<?php
include_once "comm.php";
$ante = 10000;

$aData = oo::mongo('mongo')->find(mongoTable::papplePlaying(), array(), array());
var_dump($aData);
die();
function test1(){
	throw new Exception('dgeawgeghewsgew23535');
}
try{
	test1();
} catch (Exception $ex){
	var_dump($ex->getMessage());
}
die();
//var_dump(oo::mongo('mongo')->update(mongoTable::tableInfo(), array('_id'=>$ante), array('$set' => array('ante'=>$ante, 'operateTime'=>20,'svid'=>100,'tstatus'=>0))));
//$aData = oo::mongo('mongo')->findOne(mongoTable::pappleCfg(), array(), array());
//var_dump($aData);
for($i=0;$i<60;$i++){
	$v =  $i>>3;
	echo $i.' '.$v."\n";
}
echo date('i')>>3;
die();
$aData = oo::mongo('mongo')->find(mongoTable::papplePlaying(), array(), array());
var_dump($aData);
die();
$aData = oo::mongo('mongo')->findOne(mongoTable::pappleCfg(), array(), array('tables', 'cardtype', 'tid'));
var_dump($aData);
die();
$aSet = array('player'=>10);
oo::mongo('mongo')->update(mongoTable::papplePlaying(), array('_id'=>50) , array('$set'=>$aSet));
$aData = oo::mongo('mongo')->find(mongoTable::papplePlaying(), array(), array());
var_dump($aData);

<?php
include_once "comm.php";
var_dump(json_encode(CardsArbiter::$_specailTypeScore));
die();
$aData = oo::mongo('mongo')->findOne(mongoTable::pappleCfg(), array('_id'=>'PAPPLECFG'), array('tables', 'cardtype', 'tid'));
var_dump($aData);
die();
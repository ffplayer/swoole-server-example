<?php
include_once "comm.php";
$mid = 9184;
$mid = 35458;
$mid = 2;
var_dump(oo::member()->updateOnlineInfo($mid ) );
var_dump(oo::member()->onlineinfo($mid));

var_dump(oo::money()->getAvailableMoney($mid ) );
var_dump(oo::member()->getMinfo($mid));
<?php
require_once __DIR__ . '/../Common.php';
use Model\Config;
use Model\MongoTable;

define('TSWOOLE_SID', 13);		//站点sid

Config::load();

$mongo = new MongoHelper(Config::$service['mainData']['mongo']);
// $mongo->insert('texas_13_sicboHall.sicboHall_user', array('mid'=>1000, 'rid'=>500, 'st'=>1, 'pos'=>-1));
// $mongo->insert('texas_13_sicboHall.sicboHall_user', array('mid'=>9301, 'rid'=>50, 'st'=>1, 'pos'=>-1));
// $mongo->insert('texas_13_sicboHall.sicboHall_user', array('mid'=>9061, 'rid'=>50, 'st'=>1, 'pos'=>-1));
// $mongo->insert('texas_13_sicboHall.sicboHall_user', array('mid'=>214, 'rid'=>50, 'st'=>1, 'pos'=>-1));
// $mongo->insert('texas_13_sicboHall.sicboHall_user', array('mid'=>147, 'rid'=>50, 'st'=>1, 'pos'=>-1));

// $mongo->delete('texas_110_sicboHall.sicboHall_user', array(), 0);
// $mongo->delete('texas_110_sicboHall.sicboHall_room', array(), 0);


$ret = $mongo->find('texas_110_mcache.mem', array('_id'=>'SICBOSERVERDATA'), 1);

$cfg = json_decode($ret[0]['v'], true);

var_dump($cfg);



// $ret = $mongo->find('texas_110_sicboHall.sicboHall_user', array());
// var_dump($ret);

// $ret = $mongo->find('texas_110_sicboHall.sicboHall_room', array());
// var_dump($ret);


<?php
//系统配置
return array(
	'mongo' => array('replicaSet'=>'pktest','servers'=>array( array('192.168.202.92', 5000), array('192.168.202.93', 5000))),
	'MemDataServer'=>array('192.168.202.92', 12585),
	'giredis' => array('192.168.202.200', 19011),
	'redis' => array('192.168.202.200', 19011),
	'giMem' => array(array(array('192.168.202.93', 10850, 100))),
);

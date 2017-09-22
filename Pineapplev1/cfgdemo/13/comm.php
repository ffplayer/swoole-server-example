<?php
//系统配置
return array(
	'mongo' => array('replicaSet'=>'pktest','servers'=>array( array('192.168.202.92', 5000), array('192.168.202.93', 5000))),
	'MemDataServer'=>array('192.168.202.92', 12515),
	'giredis' => array('192.168.202.200', '19001'),
	'redis' => array('192.168.202.200', '19001'),
	'giMem' => array(array(array('192.168.202.93', '10150', 100))),
	'gmMem' => array(array(array('192.168.202.93', '10159', 100))),
);

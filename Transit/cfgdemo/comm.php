<?php
//swoole 启动参数配置
return array(
	'procRedis'=>array(
		13 => array('192.168.202.200', '19001'),
		110 => array('192.168.202.200', '19011'),
	),
	'mfClient' => array('192.168.202.88'=>array(55001, 55002, 55003, 55004, 55005)),
);

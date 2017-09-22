<?php
/**
 * 服务相关配置
 */
return array(
		'svid'=>101,
		
		//中转服务（包含上报、日志、伪存储）
		'transit'=>array('192.168.202.101', 6001),
		
		//GI数据
		'giMem' => array(array(array('192.168.202.93', 10850, 100))),
		'giredis' => array('192.168.202.200', 19011),
		
		//meserver
		'mserver'=>array('192.168.202.92', 12585),
		
		//
		'mongo'=>array(
				'replicaSet'=>'pktest',
				'servers'=>array(array('192.168.202.92', 5000), array('192.168.202.93', 5000)),
		),
);
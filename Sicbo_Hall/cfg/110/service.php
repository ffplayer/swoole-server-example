<?php
/**
 * 服务相关配置
 */
return array(
		'svid'=>101,
		
		//中转服务（包含上报、日志、伪存储）
		'transit'=>array('127.0.0.1', 6105),
		
		//GI数据
		'giMem' => array(array(array('192.168.16.88', 11217, 100))),
		'giredis' =>array('192.168.16.88', 4515),
		
		//meserver
		'mserver'=>array('192.168.16.90', 6000),
		
		//
		'mongo'=>array('replicaSet'=>'pk11001','servers'=>array( array('192.168.16.21', 5000), array('192.168.16.25', 5000)))
);
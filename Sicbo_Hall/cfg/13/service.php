<?php
/**
 * 服务相关配置
 */
return array(
		'svid'=>101,
		
		//中转服务（包含上报、日志、伪存储）
		'transit'=>array('127.0.0.1', 6105),
		
		//GI数据
		'giMem' => array(array(array('172.19.228.23', 11309, 100))),
		'giredis' => array('172.19.228.23', 4555),
		
		//meserver
		'mserver'=>array('172.19.228.38', 6000),
		
		//
		'mongo'=>array('replicaSet'=>'pk01301','servers'=>array( array('172.19.228.35', 5000), array('172.19.228.8', 5000))),
);
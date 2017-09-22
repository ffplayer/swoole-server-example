<?php
//swoole 启动参数配置
return array(
	"Host" => "0.0.0.0",
	"Set" => array(
		'worker_num' => 4,
		'max_request'=> 200000,
		'dispatch_mode' => 3,
        'package_max_length' => 8000,
        'heartbeat_check_interval'=>60,
		'heartbeat_idle_time'=>300,//心跳检测
		'message_queue_key'=>65535 + TSWOOLE_PORT*10,
	)
);

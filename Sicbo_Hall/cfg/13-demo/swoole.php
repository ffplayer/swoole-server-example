<?php

/**
 * swoole 相关配置
 */

return array(
	"Host" => "0.0.0.0",
	"Set" => array(
		'worker_num' => 5,
		'dispatch_mode' => 3,
		'task_worker_num' => 5,
		'max_request'=>0,
		'task_ipc_mode' => 2,
		'task_max_request'=>0,
		'message_queue_key'=>65535 + TSWOOLE_PORT*10,
		'open_length_check' => true,
		'package_length_type' => 's',
		'package_length_offset' => 5,
		'package_body_offset' => 7,
		'package_max_length' => 2000,
		'heartbeat_check_interval'=>10,
		'heartbeat_idle_time'=>30,
		'discard_timeout_request'=>true,
		'enable_unsafe_event' => true,
	)
);

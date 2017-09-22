<?php
//这个文件的代码在reload的时候全部会重新加载
include_once TSWOOLE_INC.'Fun.php';
include_once TSWOOLE_INC . 'Core/SwooleBehavior.php';
include_once SERVER_ROOT . 'model/udp.php';
include_once SERVER_ROOT . 'model/tcp.php';
include_once SERVER_ROOT . 'model/sys.php';
include_once TSWOOLE_INC . 'Muredis.php';
class TransitBehivor extends SwooleBehavior{

	/**
	 * 处理TCP协议
	 * @param type $server
	 * @param type $fd
	 * @param type $from_id
	 * @param type $packet_buff
	 * @throws Exception
	 */
	public function onReceive($server, $fd, $from_id, $packet_buff){
		try {
			ModelTcp::init($server, $fd, $packet_buff);
		} catch (Throwable $ex){
			$info['ex'] = $ex;
			fun::logs('TransitonReceive', var_export($info, 1));
		}
	}
	
	/**
	 * 处理UDP协议
	 * @param type $server
	 * @param type $data
	 * @param type $client_info
	 */
	public function onPacket($server, $data, $client_info){
		try{
			ModelUdp::Process($data);
		} catch (Throwable $ex){
			$info['ex'] = $ex;
			fun::logs('TransitonPacketErr', var_export($info, 1));
		}
		$this->CheckMemoryLimitAndExit();
	}
	
	/**
	 * 达到内存峰值时(200M)则自动退出
	 * @param type $limit
	 */
	private function CheckMemoryLimitAndExit($limit = 200){
		$useMem = memory_get_usage(1) / 1024 / 1024;
		if ($useMem > $limit) {
			exit();
		}
	}

	/**
	 * Work/Task进程启动
	 * @global type $config
	 * @param type $serv
	 * @param type $worker_id
	 */
	public function onWorkerStart($serv, $worker_id){
		date_default_timezone_set('Asia/Shanghai');
		set_time_limit(0);
		ini_set('memory_limit', '512M');
		fun::logs('TransitWorkStart', date('Y-m-d H:i:s').' '.$worker_id);
		ModelSys::workStart($serv, $worker_id);
	}
}

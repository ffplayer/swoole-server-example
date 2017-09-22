<?php

include_once TSWOOLE_LIBROOT . 'Lib.php';
SwooleLib::LoadLibClass('SwooleBehavior', 'Core');
SwooleLib::LoadLibClass('SwooleHelper', 'Core');
include_once dirname(__FILE__) . '/Model/SwooleModelData.php';
include_once dirname(__FILE__) . '/Model/SwooleModelTaskData.php';
include_once dirname(__FILE__) . '/Model/SwooleModelTask.php';
include_once dirname(__FILE__) . '/Model/SwooleModelWork.php';

class ImBehivor extends SwooleBehavior {

	/**
	 * work进程业务处理-只有work事件才有
	 * @var SwooleModelWork
	 */
	private $workModel;

	/**
	 * task进程业务处理-只有task事件中才有
	 * @var SwooleModelTask
	 */
	private $taskModel;

	/**
	 * 处理TCP协议
	 * @param type $server
	 * @param type $fd
	 * @param type $from_id
	 * @param type $packet_buff
	 * @throws Exception
	 */
	public function onReceive($server, $fd, $from_id, $packet_buff) {
		try {
			$ret = $this->workModel->readPackage->ReadPackageBuffer($packet_buff);
			if ($ret != 1) {
				return;
			}
			SwooleHelper::I()->Reset($server, $fd, $from_id);
			$action = $this->workModel->readPackage->GetCmdType();
			$method = 'tcp_' . $action;
			if (method_exists($this->workModel, $method)) {
				$cinfo = $server->connection_info($fd);
				$this->workModel->localIp = $cinfo['remote_ip'];
				$this->workModel->$method();
			}
		} catch (Exception $ex) {
			Swoole_Log('recevive', 'excep:' . var_export($ex, 1));
		} catch (Error $ex) {
			Swoole_Log('recevive', 'err:' . var_export($ex, 1));
		}
	}

	/**
	 * 处理UDP协议
	 * @param type $server
	 * @param type $packet_buff
	 * @param type $client_info
	 */
	public function onPacket($server, $packet_buff, $client_info) {
		try {
			$ret = $this->workModel->readPackage->ReadPackageBuffer($packet_buff);
			if ($ret != 1) {
				return;
			}
			$this->workModel->localIp = $client_info['address'];
			$action = $this->workModel->readPackage->GetCmdType();
			$method = 'udp_' . $action;
			if (method_exists($this->workModel, $method)) {
				$this->workModel->$method();
			}
		} catch (Exception $ex) {
			Swoole_Log('packet', 'excep:' . var_export($ex, 1));
		} catch (Error $ex) {
			Swoole_Log('packet', 'err:' . var_export($ex, 1));
		}
	}

	/**
	 * task进程处理逻辑
	 * @param type $serv
	 * @param type $task_id
	 * @param type $from_id
	 * @param type $data
	 */
	public function onTask($serv, $task_id, $from_id, $data) {
		$this->taskModel->ipcPackage = IpcPackage::String2IpcPack($data);
		$method = 'ipc_' . $this->taskModel->ipcPackage->Action;
		if (method_exists($this->taskModel, $method)) {
			return $this->taskModel->$method();
		}
	}

	private $dataAccess, $taskApi, $headerPackage;

	/**
	 * Work/Task进程启动
	 * @global type $config
	 * @param type $serv
	 * @param type $worker_id
	 */
	public function onWorkerStart($serv, $worker_id) {
		ini_set('memory_limit', '512M');
		set_time_limit(0);
		if (!$serv->taskworker) {
			$this->workModel = new SwooleModelWork($serv);
		} else {
			$this->taskModel = new SwooleModelTask($serv);
		}
		$this->dataAccess = new SwooleModelData();
		$this->taskApi = new SwooleModelTask($serv);
		$wr = new WritePackage(true);
		$wr->WriteBegin(0x1);
		$wr->WriteEnd();
		$this->headerPackage = $wr->GetPacketBuffer();
		//第一个work进程用来做定时检测
		if ($worker_id == 0) {
			//每隔10s检测一次，踢出已断开连接的玩家(40s内无请求)
			$serv->tick(10000, function() use ($serv) {
				$sockets = $this->dataAccess->getAllSocketInfo();
				foreach ($sockets as $socket) {
					$socketmid = $this->dataAccess->getMidByfd($socket['fd']);
					$fdinfo = $serv->connection_info($socket['fd']);
					if (($socketmid != $socket['mid']) || !$fdinfo || (time() - $fdinfo['last_time']) > 60) {
						//清空被断开连接的内存中值
						$this->clearData($socket);
						if ($fdinfo) {
							$serv->close($socket['fd']);
						}
					} else {
						//发心跳包
						$serv->send($socket['fd'], $this->headerPackage);
					}
					if (TSWOOLE_DEBUG == 1) {
						echo 'mid:' . $socket['mid'] . '_fd:' . $socket['fd'] . PHP_EOL;
					}
				}
			});
		}
	}

	/**
	 * 清空被断开连接的内存中值
	 * @param type $socket
	 */
	private function clearData($socket) {
		$this->dataAccess->clearUserInfo($socket['mid'], $socket['fd']);
		if ($socket['tid']) {
			//清桌子数据
			$this->taskApi->ipc_6_api($socket['tid'], $socket['mid']);
		}
		if ($socket['fcnt']) {
			//清好友数据
			$this->taskApi->ipc_7_api($socket['mid']);
		}
	}

	/**
	 * 进程退出
	 * @param type $server
	 * @param type $worker_id
	 */
	public function onWorkerStop($server, $worker_id) {
		//暂不做处理
	}

	/**
	 * 断开连接
	 * @param type $server
	 * @param type $fd
	 * @param type $from_id
	 */
	public function onClose($server, $fd, $from_id) {
		$socket = $this->dataAccess->getUserInfoByFd($fd);
		if ($socket) {
			$this->clearData($socket);
		}
	}

}

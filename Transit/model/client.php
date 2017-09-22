<?php
class ModelClient{
	public function sendUdp($sendIp, $sendPort, $data){
		$client = new swoole_client(SWOOLE_SOCK_UDP, SWOOLE_SOCK_SYNC);
		$client->connect($sendIp, $sendPort);
		return $client->send($data);//发送成功
	}
}
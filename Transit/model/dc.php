<?php
/**
* 发送数据给d后台
*/
class ModelDc{
	private $aDcConfig;
	private $client;
	private $ip = '127.0.0.1';
	private $port = 1106;
	public function __construct(){
		$this->aDcConfig = include CFG_ROOT. 'config.dcmap.php';
		$this->client = new ModelClient();
	}
	public function send($aData){
		list($sid, $act_name, $gdata) = $aData;
		$sid = min(1001, $sid);
		$siteInfo = $this->aDcConfig[$sid];
		if(!$siteInfo){
			ModelUdp::logs(array('dcErr', 2, date('Y-m-d H:i:s').' '.$sid. ' Err', 0));
			return false;
		}
		$bpid = $siteInfo[0];
		array_walk($gdata, array($this, 'byl_encode_argument'));
        $log_buff_str = sprintf("%s|%s|%s\t%s\r\n", $bpid, time(), $act_name, json_encode($gdata));
        if(!TSWOOLE_ENV) { // 内网测试环境
        	$this->ip = '210.5.191.164';
        }
        if(!$this->client->sendUdp($this->ip, $this->port, $log_buff_str)){
			ModelUdp::logs(array('dcErr', 2, $log_buff_str, 0));
			return false;
		}
		return true;
	}
	
	public function byl_encode_argument(&$value, $key) {
        $value = strtr($value, array("\r" => "", "\n" => ""));
        $value = str_replace('|', "", $value);
    }
    
    //实时牌局 每隔5分钟一次
    public function gameparty($aData){
    	list($sid, $gdata) = $aData;
    	$siteInfo = $this->aDcConfig[$sid];
		if(!$siteInfo){
			ModelUdp::logs(array('gamePartyErr', 2, date('Y-m-d H:i:s').' '.$sid. ' Err', 0));
			return false;
		}
    	$bpid = $siteInfo[0];
	    $secret = $siteInfo[1];
	    $time = time();
	    $domain = "http://bylor.boyaa.com/gameparty";
        $sig = md5("{$bpid}{$time}{$secret}");
        $url = "{$domain}/{$bpid}/{$sig}/{$time}";
        $ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query(array('gameparty'=>json_encode($gdata))));
		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 5 );
		curl_setopt( $ch, CURLOPT_TIMEOUT, 5 );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_exec( $ch );
		$errno = curl_errno( $ch); 
		if($errno){//记录下错误日志
			$error = curl_error( $ch);
			ModelUdp::logs(array('gamePartyErr', 2, date('Y-m-d H:i:s')."(errno:{$errno}, error:{$error},time:{$time}.url:{$url})", 0));
		}
    }
}
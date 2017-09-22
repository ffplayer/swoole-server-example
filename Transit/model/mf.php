<?php
/**
* 发送数据给mf
*/
class ModelMf{
	private $aServer;
	private $aSiteConfig;
	private $client;
	public function __construct($aServer){
		if (!is_array($aServer)) {
            return 0;
        }
        foreach($aServer as $ip => $aPort){
            foreach ($aPort as $port){
                $this->aServer[] = array($ip, $port); 
            }
        }
		$this->aSiteConfig = include CFG_ROOT . 'config.sidmap.php';
		$this->client = new ModelClient();
	}
	public function send($aData){
		list($sid, $mid, $tnm, $gData, $time) = $aData;
		$sid = min(1001, $sid);
		
		$siteInfo = $this->aSiteConfig[$sid];
		if(!$siteInfo){
			ModelUdp::logs(array('mfErr', 2, date('Y-m-d H:i:s').' '.$sid. ' Err', 0));
			return false;
		}
		$aList = array();
		foreach ((array) $gData as $k => $val) {
			$aList[$k] = $val;
		}
		$aList['_tnm'] = $tnm;
		$aList['_bpid'] = $siteInfo['bpid'];
		$aList['_gid'] = $siteInfo['gid'];
		$aList['_plat'] = $siteInfo['plat'];
		$aList['_uid'] = $mid;
		$aList['_tm'] = $time ? $time : time();
		$sData = json_encode($aList);
		$gzdata = gzcompress($sData, 9); //压缩
		
		$randKey = array_rand($this->aServer, 1);
		$ip = $this->aServer[$randKey][0];
		$port = $this->aServer[$randKey][1];
		if(!$this->client->sendUdp($ip, $port, $gzdata)){
			ModelUdp::logs(array('mfErr_'.$port, 2, $mid.' '. $sData, 0));
			return false;
		}
		return true;
	}
}
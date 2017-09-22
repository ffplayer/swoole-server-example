<?php
include_once "comm.php";

class testClass{
	const EVENT_TYPE_FTX = 'ftxOutTime'; //范特西倒计时

	
	
	public $id;				//事件id

	public $type;			//事件类型
	
	private $_data;			//事件包含数据

	public function __construct($id, $type, array $data){
//		$this->id = $id;
//		$this->type = $type;
//		$this->_data = $data;
	}
	
	//事件处理逻辑
	public function run(){
		call_user_func(array(oo::game(), $this->type), $this->_data);
	}
	
	
}
$tid = 1;
$mid = 2;
$a = array();
for($i=0;$i<10000;$i++){
	$event = new testClass($tid.'|'.$mid, testClass::EVENT_TYPE_FTX, array('tid'=>$tid, 'mid'=> $mid));
//	$a[] = serialize($event);
	usleep(100000);
//	if($i%100 == 0){
//		foreach($a as $key=>$v){
//			unset($a[$key]);
//		}
//		$useMem = memory_get_usage(1)/1024;
//		echo $i. ' '.$useMem.PHP_EOL;
//	}
}
die();
$file = SERVER_ROOT . 'data/mem_110.php';
rename($file, $file.'bak');
die();
$suseMem = memory_get_usage() / 1024;
$aInfo = array( );
$aInfo['mmoney'] = 100;
$aInfo['wmode'] = 1154;
$aInfo['addmoney'] = 100;
$aInfo['sid'] = 110;
$aInfo['tid'] = 9998;
$aInfo['bid'] = 9827;
$b = array();
var_dump(memory_get_usage() / 1024 -$suseMem , $suseMem);
for($i=1;$i<5;$i++){
	$suseMem = memory_get_usage() / 1024;
	$b[] = range(1,10000);
	var_dump(memory_get_usage() / 1024 -$suseMem , $suseMem);
}


die();
$writePackage = new GSWritePackage();
$writePackage->WriteBegin(0x101);
//$writePackage->WriteInt(2318220444);
$writePackage->WriteInt64(-66);
$writePackage->WriteEnd();
$str = $writePackage->GetPacketBuffer();
$readPackage = new GSReadPackage();
$readPackage->ReadPackageBuffer($str);
//var_dump($readPackage->ReadInt());
var_dump($readPackage->ReadInt64());
die();
$value = 14;
$low = floor($value % pow(2, 32)); //($value | 0x0000ffff)
$high = floor($value / pow(2, 32)); // ($value | 0xffff0000) >> 32
var_dump($low, $high);
$low = $value & 0xFFFFFFFF;
$high = ($value >> 32) & 0xFFFFFFFF;
var_dump($low, $high);
die();
echo pow(2, 32);
$val = 2147483648;
$writePackage = new MSWritePackage();
$writePackage->WriteBegin(0x101);
$writePackage->WriteInt64(1);
$writePackage->WriteEnd();
$str = $writePackage->GetPacketBuffer();
echo strlen($str);
die();
$game_table1 = 11222;
$ttype = 1;
$game_table = ${'game_table'.$ttype};
var_dump($game_table);
die();
//oo::mongo('mongo')->insert(mongoTable::tableInfo(), array('_id'=>1, 'svid'=>100,'tstatus'=>0,'operateTime'=>20,'ante'=>50));
oo::mongo('mongo')->insert(mongoTable::tableInfo(), array('_id'=>5, 'svid'=>100,'tstatus'=>0,'operateTime'=>20,'ante'=>50));
//oo::mongo('mongo')->insert(mongoTable::tableInfo(), array('_id'=>3, 'svid'=>100,'tstatus'=>0,'operateTime'=>20,'ante'=>50));
$where = array('svid'=> 100,'tstatus'=>0);
$aData = oo::mongo('mongo')->find(mongoTable::tableInfo(), $where, array('operateTime','ante'), 0);
var_dump($aData);
die();
$tid = 1;
CardsSender::start($tid);
$writePackage = new MSWritePackage();
$writePackage->WriteBegin(0x101);
$writePackage->WriteShort(3);
$aCard = CardsSender::send($tid , 3);
foreach($aCard  as $card){
	$writePackage->WriteByte($card);
}
$writePackage->WriteEnd();
$readPackage = new MSReadPackage();
echo $writePackage->GetPacketBuffer();
$readPackage->ReadPackageBuffer($writePackage->GetPacketBuffer());
var_dump($readPackage->ReadShort());
var_dump($readPackage->ReadByte());
var_dump($readPackage->ReadByte());
var_dump($readPackage->ReadByte());
die();
//var_dump(oo::mongo('mongo')->update('texas_13_base.base_lbgtest2', array('_id'=>11), array('$inc' => array('tplayernow'=>1))));
//var_dump(oo::mongo('mongo')->find('texas_13_base.base_lbgtest2', array(), array()));

$client = new swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_SYNC);
$client->set(array(
	'open_length_check' => true,
	'package_length_type' => 's',
	'package_length_offset' => 6,
	'package_body_offset' => 9,
	'package_max_length' => 9000,
));
$ip = '192.168.56.104';
$port = 8501;
$result = $client->connect($ip, $port, 2);
if(!$result){
	die('connect ERR');
}

$writePackage = new MSWritePackage();
$writePackage->WriteBegin(0x101);
$writePackage->WriteInt(1);
$writePackage->WriteInt(2);
$writePackage->WriteEnd();
var_dump($client->send($writePackage->GetPacketBuffer()));
echo $responseData = $client->recv();
$readPackage = new MSReadPackage();
$readPackage->ReadPackageBuffer($responseData);
var_dump($readPackage->GetCmdType());
var_dump($readPackage->ReadShort());
$len = $readPackage->ReadShort();
for($i=0;$i<$len;$i++){
	var_dump($readPackage->ReadInt());
	var_dump($readPackage->ReadShort());
	var_dump($readPackage->ReadInt());
	var_dump($readPackage->ReadString());
}
sleep(5);
die();
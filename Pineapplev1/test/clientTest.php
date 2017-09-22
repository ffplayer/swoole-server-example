<?php
define('SERVER_ROOT', dirname(__FILE__).'/');
define('TSWOOLE_SERVERROOT', dirname(__FILE__) . '/../');
define('TSWOOLE_SID', 13); //站点sid
define('TSWOOLE_ENV', 0); //站点sid
define('TSWOOLE_LIBROOT', TSWOOLE_SERVERROOT . '../Lib/');
define('CFG_ROOT', SERVER_ROOT.(TSWOOLE_ENV ? 'cfg/' : 'cfgdemo/').TSWOOLE_SID . '/');
include_once SERVER_ROOT . 'Lib/Lib.php';

function Swoole_Log($fname, $fcontent, $file_append = true){
	clearstatcache();
	$file = TSWOOLE_SERVERROOT . '../data/' . $fname . '_' . TSWOOLE_SID . '.php';
	$dir = dirname($file);
	if (!is_dir($dir)) {
		mkdir($dir, 0775, true);
	}
	$prefix_header = "<?php (isset(\$_GET['p']) && (md5('&%$#'.\$_GET['p'].'**^')==='8b1b0c76f5190f98b1110e8fc4902bfa')) or die();?>\n";
	if ($file_append) {
		$size = file_exists($file) ? filesize($file) : 0;
		$flag = $size < 1 * 1024 * 1024; //标志是否附加文件.文件控制在1M大小
		$prefix = $size && $flag ? '' : $prefix_header; //有文件内容并且非附加写		
		file_put_contents($file, $prefix . $fcontent . "\n", $flag ? FILE_APPEND : null );
	} else {
		file_put_contents($file, $prefix_header . $fcontent . "\n", null);
	}
}

oo::initCfg();

$client = new swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);

//注册连接成功回调
$client->on("connect", function($cli) {
	$writePackage = new MSWritePackage();
	$writePackage->WriteBegin(0x999);
	$writePackage->WriteInt(1);
	$writePackage->WriteEnd();
	$cli->send($writePackage->GetPacketBuffer());
});

//注册数据接收回调
$client->on("receive", function($cli, $data){
	echo "Received: ".$data."\n";
});

//注册连接失败回调
$client->on("error", function($cli){
	echo "Connect failed\n";
});

//注册连接关闭回调
$client->on("close", function($cli){
	echo "Connection close\n";
});

$ip = '172.20.15.138';
$port = 9501;
$result = $client->connect($ip, $port, 2);

echo "----\n";
// die();
// $client->set(array(
// 	'open_length_check' => true,
// 	'package_length_type' => 's',
// 	'package_length_offset' => 6,
// 	'package_body_offset' => 9,
// 	'package_max_length' => 9000,
// ));
// $ip = '192.168.56.104';
// $port = 8501;
// $result = $client->connect($ip, $port, 2);
// if(!$result){
// 	die('connect ERR');
// }

// $writePackage = new MSWritePackage();
// $writePackage->WriteBegin(0x999);
// $writePackage->WriteInt(1);
// $writePackage->WriteEnd();

// var_dump($client->send($writePackage->GetPacketBuffer()));
// echo $responseData = $client->recv();
// $readPackage = new MSReadPackage();
// $readPackage->ReadPackageBuffer($responseData);
// var_dump($readPackage->GetCmdType());
// var_dump($readPackage->ReadShort());
// $len = $readPackage->ReadShort();
// for($i=0;$i<$len;$i++){
// 	var_dump($readPackage->ReadInt());
// 	var_dump($readPackage->ReadShort());
// 	var_dump($readPackage->ReadInt());
// 	var_dump($readPackage->ReadString());
// }
// sleep(5);
// die();
<?php
define('TSWOOLE_SID', 110);
define('TSWOOLE_ENV', 0);
define('TSWOOLE_PORT', 6630);

define('SERVER_ROOT', realpath(__DIR__.'/../') . '/');
define('CONFIG_ROOT', SERVER_ROOT.'/cfg/');
define('SRC_ROOT', SERVER_ROOT.'/src/');
define('INCLUDE_ROOT', realpath(SERVER_ROOT . '/../include/') . '/');

require_once SRC_ROOT . '/Common.php';


$data = getData();

//var_dump($data);

function debugTable($rid, $data){
	foreach ($data['table'] as $rid=>$rinfo){
		foreach ($rinfo as $tid=>$table){
			$userList = json_encode($table->userList);
			echo "[Table] rid:{$rid}, tid:{$tid}, user:{$userList}\n";
		}
	}
}

function debugUser($data){
	$userCount = count($data['user']);
	echo "[User] total user {$userCount}\n";
}

function debugRoomUser($data){
	
	foreach ($data['table'] as $rid=>$rinfo){
		$userCount = 0;
		$tableCount = 0;
		foreach ($rinfo as $tid=>$table){
			$tableCount += 1;;
			$userCount += count($table->userList);
		}
		
		echo "[Room_{$rid}] tableCount:{$tableCount}, userCount:{$userCount}\n";
	}
	
}



function getData(){
	$dataDir = '/media/sf_wwwroot/log/';
	$fileList = scandir($dataDir);
	if (false == $fileList){
		return array();
	}
	
	$data = array('room'=>array(), 'table'=>array(), 'user'=>array());
	
	foreach ($fileList as $fileName){
		if (is_dir($fileName)){
			continue;
		}
		if (!preg_match('/^sys_[0-9]{1,}\.data$/', $fileName)){
			continue;
		}
		$tmpData = readStorage($dataDir . '/' . $fileName);
		if (isset($tmpData['roomPool']['pool'])){
			foreach ($tmpData['roomPool']['pool'] as $rid=>$rinfo){
				$data['room'][$rid] = $rinfo;
			}
		}
		if (isset($tmpData['tablePool']['pool'])){
			foreach ($tmpData['tablePool']['pool'] as $rid=>$rinfo){
				foreach ($rinfo as $tid=>$tinfo){
					$data['table'][$rid][$tid] = $tinfo;
				}
			}
		}
		if (isset($tmpData['userPool']['pool'])){
			foreach ($tmpData['userPool']['pool'] as $uid=>$uinfo){
				$data['user'][$uid] = $uinfo;
			}
		}
		
		if ($fileName == 'sys_7.data') {
			var_export($tmpData['tablePool']['pool'][5000000]);
			//var_dump($tmpData['userPool']);
		}
		
	}
	
	return $data;
}


function readStorage($filePath){
	if (!file_exists($filePath)){
		return array();
	}
	$str = @file_get_contents($filePath);
	if (is_null($str)){
		return array();
	}
	$data = unserialize($str);
	if (!is_array($data)){
		return array();
	}
	return $data;
}



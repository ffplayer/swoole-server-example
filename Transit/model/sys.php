<?php
class ModelSys{
	//进程启动时调用
	public static function workStart($serv, $workId){
		if($workId == 1){
			$serv->tick(24*60*60*1000, function(){
				$path = SERVER_ROOT. 'data/';
				ModelSys::clearLog($path);
			});
		}
	}
	
	public static function clearLog($path, $isRoot=1){
		try{
			$handle = dir($path);
			$time = time();
			while($file = $handle->read()){
				if($file == '.' || $file== '..') {
					continue;
				}
				if($isRoot){
					$ltime = filemtime($path .'/'.$file);
					if($time - $ltime < 14*24*60*60){//14天内的不删除
						continue;
					}
				}
				if(is_dir($path .'/'.$file)){
					ModelSys::clearLog($path .'/'.$file, 0);
					rmdir($path .'/'.$file);
				} else {
					unlink($path .'/'.$file);
				}
			}
			$handle->close();
		}catch(Throwable $ex){
			fun::logs(__FUNCTION__, $ex->getMessage());
		}
	}
	
}
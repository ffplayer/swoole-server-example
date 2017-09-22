<?php
include_once __DIR__ . '/TimerEvent.php';

class TimerHandler{
	static $aEvent = array();
	/**
	 * 定时器轮询接口， 在swoole中开启一个定时器， 调用此方法
	 * @param null
	 * 
	 * @return null
	 */
	public static function tigger(){
		$currentTime = time();
		foreach (self::$aEvent as $key=>$row){
			if ($currentTime < $row['stime']+$row['interval']){
				continue;
			}
			
			if ($row['repeat'] == 0){
				unset(self::$aEvent[$key]);
			}else{
				self::$aEvent[$key]['stime'] = $row['stime'] + $row['interval'];
			}
			try {
				$eventObj = self::_decodeObj($row['event']);
				call_user_func(array($eventObj, 'run'));
			}catch (Throwable $ex){
				$info['exption'] = $ex;
				Swoole_Log('Pineappletigger', var_export($info, 1));
				oo::main()->logs($row['event'] , 'tigger');
			}
		}
	}

	/**
	 * 添加一个延迟执行一次的事件
	 * @param TimerEvent $event, 事件对象
	 * @param $deferTime, 事件延迟执行的时间（单位：S）
	 * 
	 * @return null
	 */
	public static function after(TimerEvent $event, $deferTime){
		if(!oo::main()->swoole->taskworker){
			return;
		}
		$key = self::_makeKey($event->id, $event->type);
		$eventStr = self::_encodeObj($event);
		self::$aEvent[$key] =  array('stime'=>time(), 'interval'=>$deferTime, 'repeat'=>0, 'event'=>$eventStr);
	}

	/**
	 * 添加一个定时重复执行的事件
	 * @param TimerEvent $event, 事件对象
	 * @param $interval, 事件重复执行时间间隔（单位：S）
	 * 
	 * @return null
	 */
	public static function tick(TimerEvent $event, $interval){
		if(!oo::main()->swoole->taskworker){
			return;
		}
		$key = self::_makeKey($event->id, $event->type);
		$eventStr = self::_encodeObj($event);
		oo::main()->adminLog('Add repeat time event, type:' . $event->type . ' lenth:' . strlen($eventStr));
		self::$aEvent[$key] =  array('stime'=>time(), 'interval'=>$interval, 'repeat'=>1, 'event'=>$eventStr);
	}

	/**
	 * 取消已经添加的定时事件， 取消不存在事件会失败
	 * @param $id, 事件id
	 * @param $type, 事件类型
	 *
	 * @return bool, 成功返回true， 失败返回false
	 */
	public static function del($id, $type){
		$key = self::_makeKey($id, $type);
		unset(self::$aEvent[$key]);
	}

	private static function _makeKey($id, $type){
		return $id.'_'.$type;
	}

	private static function _encodeObj($obj){
		return serialize($obj);
	}

	private static function _decodeObj($data){
		return unserialize($data);
	}
}
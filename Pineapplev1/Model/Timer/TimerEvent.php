<?php

class TimerEvent{
	
	const EVENT_TYPE_TABLE_READY = 'test';
	
	const EVENT_TYPE_START_READY = 'startReadyOutTime';//准备倒计时
	
	const EVENT_TYPE_POKER_DO = 'pokerOutTime';//操作倒计时
	
	const EVENT_TYPE_FTX = 'ftxOutTime'; //范特西倒计时
	
	const EVENT_USERCLOSE = 'userClose'; //用户掉线了
	
	
	public $id;				//事件id

	public $type;			//事件类型
	
	private $_data;			//事件包含数据

	public function __construct($id, $type, array $data){
		$this->id = $id;
		$this->type = $type;
		$this->_data = $data;
	}
	
	//事件处理逻辑
	public function run(){
		call_user_func(array(oo::game(), $this->type), $this->_data);
	}
	
	
}

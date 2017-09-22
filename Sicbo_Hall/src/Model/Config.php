<?php
namespace Model;

class Config{
	
	/**
	 * 服务相关配置
	 */
	public static $service = array();
	
	
	/**
	 * 游戏相关配置
	 */
	public static $game = array();
	
	
	
	public static $time = array(
			'time1'=>20,		//下注时间
			'time2'=>3,			//开奖倒计时
			'time3'=>10,		//摇骰+开骰结果， 中奖区域高亮
			'time4'=>10,		//下注结算， 彩池结算，结算弹窗 
			'time5'=>3,			//准备下局
	);
	
	/**
	 * 加载配置
	 */
	public static function load(){
		try {
			self::_loadServiceCfg();
			self::_loadGameCfg(self::$service['mongo']);
			
			//测试环境的tid和正式环境tid是独立的
			if (TSWOOLE_ENV == 0){
				self::$game['tid'] = self::$game['demo_tid'];
			}
		}catch (\Throwable $th){
			return false;
		}
		return true;
	}
	
	private static function _loadServiceCfg(){
		if (TSWOOLE_ENV == 0){
			self::$service = include CONFIG_ROOT . '/' . TSWOOLE_SID . '-demo/service.php';
		}else{
			self::$service = include CONFIG_ROOT . '/' . TSWOOLE_SID . '/service.php';
		}
	}
	
	private static function _loadGameCfg($mongoCfg){
		$mongo = new \MongoHelper($mongoCfg);
		$gameCfgJson = $mongo->find(MongoTable::tableNameCfg(), array('_id'=>'SICBOSERVERDATA'), array(), 1);
		$gameCfg = @json_decode($gameCfgJson[0]['v'], true);
		if (null == $gameCfg){
			throw new \Exception("load game cfg error");
		}
		self::$game = $gameCfg;
	}
	
}
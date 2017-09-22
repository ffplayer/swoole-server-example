<?php
class oo{//全局变量数据
	static $oo = array();
	static $cfg = array();
	static $aClass = array();//需要差异化的类
	/**
	* 初始化的时候调用的方法
	*/
	public static function initCfg($reload=0){
		if($reload && function_exists('opcache_reset')) {
			opcache_reset();
		}
		$cfgFile = CFG_ROOT.'comm.php';
		if(file_exists($cfgFile)){
			self::$cfg = include $cfgFile;
		}
		$serverFile = CFG_ROOT.TSWOOLE_GAMETYPE.'.php';
		if(file_exists($serverFile)){
			self::$cfg = array_merge(self::$cfg, include $serverFile);
		}
		if($reload){
			return;
		}
		/**
		 * 以下是差异化的类
		 */
		switch(TSWOOLE_GAMETYPE){
			case "yxb":
				//self::$aClass = array('Tables');
				break;
		}
		foreach(self::$aClass as $class){
			include_once SERVER_ROOT. 'src/'.TSWOOLE_GAMETYPE .'/' . lcfirst($class).'.php';
		}
	}

	/**
	* 需要用到的memcache
	*/
	public static function memcache($stype='giMem'){
		$k = 'mem|'.$stype;
		if( !is_object( self::$oo[$k] ) ){
			self::$oo[$k] = new mucache( self::$cfg[$stype]);
		}
		return self::$oo[$k];
	}
	/**
	* 需要用到的mongodb
	* @return mumongo
	*/
	public static function mongo($stype='mongodb'){
		$k = 'mongo|'.$stype;
		if( !is_object( self::$oo[$k] ) ){		
			self::$oo[$k] = new MongoHelper( self::$cfg[$stype]);//IP 端口
		}
		return self::$oo[$k];
	}
	
	/**
	* 需要用到的memcache
	* @return muredis
	*/
	public static function redis($stype='redis'){
		$k = 'redis|'.$stype;
		if( !is_object( self::$oo[$k] ) ){
			self::$oo[$k] = new muredis( self::$cfg[$stype]);
		}
		return self::$oo[$k];
	}
	/**
	 * @return ModelMain
	 */
	public static function main(){
		return self::mW('Main');
	}
	/**
	 * @return Member
	 */
	public static function member(){
		if(!is_object(self::$oo['member'])){
			self::$oo['member'] = new Member(oo::redis('giredis'), oo::memcache('giMem'));
		}
		return self::$oo['member'];
	}
	/**
	 * @return Servers
	 */
	public static function servers(){
		if(!is_object(self::$oo['servers'])){
			self::$oo['servers'] = new Servers(mongoTable::server(), oo::mongo('mongo'));
		}
		return self::$oo['servers'];
	}
	
	/**
	 * 中转UDP server
	 */
	public static function transit(){
		if(!is_object(self::$oo['transit'])){
			self::$oo['transit'] = new Transit(TSWOOLE_SID);
		}
		return self::$oo['transit'];
	}
	
	/**
	 * @return Proc
	 */
	public static function proc(){
		if(!is_object(self::$oo['proc'])){
			self::$oo['proc'] = new Proc(TSWOOLE_SID, oo::redis('procredis'));
		}
		return self::$oo['proc'];
	}
	
	/**
	 * @return ModelFindTable
	 */
	public static function findTable(){
		return self::mW('FindTable');
	}
	/**
	 * @return ModelMoney
	 */
	public static function money(){
		return self::mW('Money');
	}
	
	/**
	 * @return ModelDoTcp
	 */
	public static function dotcp(){
		return self::mW('Dotcp');
	}
	
	/**
	 * @return ModelWork
	 */
	public static function work(){
		return self::mW('Work');
	}
	/**
	 * @return ModelTask
	 */
	public static function task(){
		return self::mW('Task');
	}
	/**
	 * @return ModelGame
	 */
	public static function game(){
		return self::mW('Game');
	}

	/**
	 * @return ModelAdmin
	 */
	public static function admin(){
		return self::mW('Admin');
	}
	/**
	 * @return ModelTables
	 */
	public static function tables($tid){
		self::mW('Tables')->tid = fun::uint($tid);
		return self::mW('Tables');
	}
	
	/**
	 * @return ModelUser
	 */
	public static function user($mid){
		self::mW('User')->mid = fun::uint($mid);
		return self::mW('User');
	}
	
	/**
	 * @return ModelData
	 */
	public static function data(){
		return self::mW('Data');
	}
	
	/**
	 * @return ModelCheckUser
	 */
	public static function checkUser(){
		return self::mW('CheckUser');
	}
	
	/**
	 * @return ModelCheckTable
	 */
	public static function checkTable(){
		return self::mW('CheckTable');
	}
	
	/**
	 * Swoole版的mserver对象
	 * @return MServer
	 */
	public static function MServer(){
		if( !is_object( self::$oo['MServer'] ) ){
			self::$oo['MServer'] = new MServer( oo::$cfg['MemDataServer'][0],oo::$cfg['MemDataServer'][1]);
		}
		return self::$oo['MServer'];
	}
	
	/**
	 * 自定义日志文件上报类
	 */
	public static function logs(){
		if( !is_object( self::$oo['logs'] ) ){
			self::$oo['logs'] = new LogClient(oo::$cfg['logservers'][0], oo::$cfg['logservers'][1]);
		}
		return self::$oo['logs'];
	}
	
	/**
	* 写一个公用的方法
	*/
	protected static function mW($str){
		if(!is_object(self::$oo[$str])){
			if(in_array($str , self::$aClass)){
				$model = 'Model'.$str.'Ext';
			}else{
				$model = 'Model'.$str;
			}
			self::$oo[$str] = new $model();
		}
		return self::$oo[$str];
	}
}
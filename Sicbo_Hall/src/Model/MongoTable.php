<?php
namespace Model;

class MongoTable{
	public static function tableNameRoom(){
		return self::_mgtblbase('sicboHall', 'room');
	}
	
	
	public static function tableNameUser(){
		return self::_mgtblbase('sicboHall', 'user');
	}
	
	public static function tableNameCfg(){
		return 'texas_' . TSWOOLE_SID . '_mcache.mem';
	}
	
	/**
	 * 表名设置处理
	 * @param type $prefix
	 * @return type
	 */
	private static function _mgtblbase($tbl ,$prefix='a'){
		return self::_getMongoDb($tbl) .'.'.$tbl.'_' .$prefix;
	}
	/**
	 * 设置mongo表名
	 * @param type $dbnm
	 * @return type
	 */
	private static function _getMongoDb($dbnm){
		return 'texas_' . TSWOOLE_SID . '_' . $dbnm;
	}
}

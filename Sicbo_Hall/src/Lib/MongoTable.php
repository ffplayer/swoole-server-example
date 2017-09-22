<?php
/**
* mongo表注册地方
*/
class mongoTable{
	//桌子数据表
	static function tableInfo(){//表结构： tid ttype tplayernow tviewers svid tstatus operateTime ante底注  tbuymin
		return self::mgtblbase('pineapple', 'table');
	}
	
	static function server(){
		return self::mgtblbase('base', 'server');
	}
	
	/**
	 * 表名设置处理
	 * @param type $prefix
	 * @return type
	 */
	static function mgtblbase($tbl ,$prefix='a'){
		return self::getMongoDb($tbl) .'.'.$tbl.'_' .$prefix;
	}
	/**
	 * 设置mongo表名
	 * @param type $dbnm
	 * @return type
	 */
	static function getMongoDb($dbnm){
		return 'texas_' . TSWOOLE_SID . '_' . $dbnm;
	}
}
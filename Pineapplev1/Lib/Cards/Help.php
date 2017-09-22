<?php
class Help{
	/**
	 * 检查数据的value是否有重复
	 * @param array $arr
	 * 
	 * @return bool	true/false
	 */
	public static function checkArrRepeat(array $arr){
		$list = array();
		foreach ($arr as $v){
			if (isset($list[$v])){
				return true;
			}
			$list[$v] = 1;
		}
		return false;
	}
	
	/**
	 * 检测数组的value是否连续
	 * @param array $arr， 待排序数组
	 * 
	 * @return bool true/false
	 */
	public static function checkArrContinue(array $arr){
		$ret = 1;
		sort($arr, SORT_NUMERIC);
		$testValue = null;
		$hasA = false;
		foreach ($arr as $key=>$value){
			if($value == Card::NUMBER_MAX){
				$hasA = true;
				$arr[$key] = 0x1;
			}
			if (is_null($testValue)){
				$testValue = $value;
				continue;
			}
			if (abs($testValue-$value) != 1){
				$ret = 0;
				break;
			}
			$testValue = $value;
			continue;
		}
		if(!$ret && $hasA){
			return self::checkArrContinue($arr) ? 2 : 0;
		}
		return $ret;
	}
	
	
	/**
	 * 获取数组中重复出现的value的重复次数和value种类的关联数组
	 * @param array $arr
	 * 
	 * @return array array(repeatCount=>valueCount,...)
	 */
	public static function getArrRepeatCountList(array $arr){
		$list = array();
		$valueList = array_count_values($arr);
		foreach ($valueList as $val=>$counts){
			if (!isset($list[$counts])){
				$list[$counts] = 0;
			}
			$list[$counts]++;
		}
		return $list;
	}
	
	
	/**
	 * 格式化数组的value为16进制字符串， 并返回
	 * @param array $arr
	 * @param unknow $minLen  16进制字符串最小长度， 不足的会用0补齐		
	 * 
	 * @return
	 */
	public static function formatArrValueToHex(array $arr, $minLen){
		$res = array();
		foreach ($arr as $k=>$v){
			if (!is_numeric($v)){
				continue;
			}
			$hexStr = sprintf('%X', $v);
			$hexStr = str_pad($hexStr, $minLen, '0', STR_PAD_LEFT);
			$res[$k] = '0x' . $hexStr;
		}
		return $res;
	}
	
	
}
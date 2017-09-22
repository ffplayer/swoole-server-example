<?php
/**
* 保存桌子  用户数据
*/
class ModelData{
	private $aUser = array();
	private $aTable = array();
	
	public function getUsers(){
		return $this->aUser;
	}
	public function setUsers($aUser){
		$this->aUser = $aUser;
	}
	
	public function setUserData($mid, $field, $data){
		if(!$this->aUser[$mid]){
			$this->aUser[$mid] = array(
				'tid' => 0,
				'seatId' =>0,
				'money'=>0, //用户身上的钱
				'isReady' =>0, //是否准备好了
				'isPlay' => 0, //是否在牌局中
				'sendPoker' => array(), //刚发的牌
				'foldPoker' => array(), //弃牌
				'isFtx' => 0, //用户是否是范特西
				'isPut' => 0, //用户是否摆好牌
				'myCards' => array(1=>array(),2=>array(),3=>array()),//头道的牌 中道的牌 尾道的牌
				'userInfo' => array(), //用户资料
				'userInfoStr' => '', //用户资料string
				'inQueue' => 0, //是否排队中
				'score' => 0, //得分
				'disconnect' => 0, //是否断线
				'aGiInfo' => array(), //gi中信息
				'mtime' => 0,//最后时间
				'putCard' => array(),
				'oneCard' => array(), //一张张提交的牌型
			);
		}
		$this->aUser[$mid][$field] = $data;
	}
	
	public function setUserDatas($mid, $aData){
		foreach($aData as $field=>$data){
			$this->setUserData($mid, $field, $data);
		}
	}
	
	/**
	* 数组覆盖式添加
	*/
	public function setUserAddArray($mid, $field, $aData){
		if(!$this->aUser[$mid]){
			$this->setUserData($mid, 'mtime', time());
		}
		$this->aUser[$mid][$field] = array_merge((array)$this->aUser[$mid][$field], (array)$aData);
	}
	
	public function getUserData($mid, $field=false){
		if(!$this->aUser[$mid]){
			$this->setUserData($mid, 'mtime', time());
		}
		if($field){
			return $this->aUser[$mid][$field];
		}
		return (array)$this->aUser[$mid];
	}
	
	public function delUserData($mid){
		unset($this->aUser[$mid]);
		oo::main()->logs('del:'.$mid, 'data_user');
	}
	
	
	public function getTables(){
		return $this->aTable;
	}
	
	public function setTables($aTable){
		$this->aTable = $aTable;
	}
	
	/**
	 * 桌子信息
	 */
	public function setTableData($tid, $field, $data){
		if(!$this->aTable[$tid]){
			$this->aTable[$tid] = array(
				'bid' => 0,//局ID
				'status' => 0,//游戏状态
				'aSeat' => array(1=>0, 2=>0, 3=>0),//座位上的用户
				'aAllMid' => array(),//存放所有用户的mid array($mid=>$status)  $status 1 旁观 2 坐下
				'makers' => 3,//庄家的位置
				'operating' => 0,//当前操作的座位ID
				'first_operating' => 0,//第一个操作者
				'ante' => 0,//底注
				'isFtx' => 0,//是否范特西牌局 
				'cfg' => array(),//桌子配置
				'aQueue' => array(),//排队的mid
				'reload' => 0,
				'plog' => '',//牌局日志
				'aMoney' => array(),//上局时需要扣的钱
				'mtime' => 0,//最后更新时间
			);
			oo::tables($tid)->setCfg();
		}
		$this->aTable[$tid][$field] = $data;
	}
	
	public function setTableDatas($tid, $aData){
		foreach($aData as $field=>$data){
			$this->setTableData($tid, $field, $data);
		}
	}
	
	public function upArrTable($tid, $field, $k , $val){
		if(!$this->aTable[$tid]){
			$this->setTableData($tid, 'mtime', time());
		}
		$this->aTable[$tid][$field][$k] = $val;
	}
	
	public function delArrTable($tid, $field, $k){
		unset($this->aTable[$tid][$field][$k]);
	}
	
	/**
	 * 字符串添加
	 */
	public function addTableStr($tid, $field, $msg){
		if(!$this->aTable[$tid]){
			$this->setTableData($tid, 'mtime', time());
		}
		$this->aTable[$tid][$field] .= $msg;
	}
	
	public function getTableData($tid, $field=false, $key = ''){
		if(!$this->aTable[$tid]){
			$this->setTableData($tid, 'mtime', time());
		}
		if($field){
			if($key && is_array($this->aTable[$tid][$field])){
				return $this->aTable[$tid][$field][$key];
			}
			return $this->aTable[$tid][$field];
		}
		return (array)$this->aTable[$tid];
	}
	
	public function delTableData($tid){
		unset($this->aTable[$tid]);
		oo::main()->logs('del:'.$tid, 'data_table');
	}
}
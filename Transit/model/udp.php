<?php
include_once __DIR__ . '/mf.php';
include_once __DIR__ . '/dc.php';
include_once __DIR__ . '/client.php';
include_once __DIR__ . '/proc.php';
class ModelUdp{
	private static $I = false;
	public static $cfg = array();
	private $mf;
	private $dc;
	private $proc;
	public function __construct(){
		self::$cfg = include CFG_ROOT.'comm.php';
		$this->mf = new ModelMf(self::$cfg['mfClient']);
		$this->dc = new ModelDc();
		$this->proc = new ModelProc();
	}
	public static function Process($buff_data){//格式 cmd*$*gzcompress(json_encode(array()) ,9)
		if(!self::$I){
			self::$I = new ModelUdp();
		}
		list($cmd , $data) = explode('*$*', $buff_data);
		if(!in_array($cmd, array(0x104, 0x105, 0x106))){
			$data = @gzuncompress($data);
		}
		$aData = json_decode($data , true);
		self::adminLog("cmd:{$cmd},data:{$data}");
		switch($cmd){
			case 0x101://上报mf
				self::$I->mf->send($aData);
				break;
			case 0x102://上报dc
				self::$I->dc->send($aData);
				break;
			case 0x103://记录日志
				self::logs($aData);
				break;
			case 0x104://伪存储
				self::$I->proc->send($aData);
				break;
			case 0x105://报警
				self::warning($aData);
			case 0x106://实时牌局
				self::$I->dc->gameparty($aData);
				break;
		}
	}
	
	/**
	* 输出调试信息
	*/
	public static function adminLog($str=''){
		fun::logs('Transit/sys.txt', date('Y-m-d H:i:s').' >> '. $str);
	}
	/**
	 * 记录日志
	 */
	public static function logs($aData){
		list($fname, $fsize, $string, $isBak) = $aData;
		$file = SERVER_ROOT. 'data/'.date('Ymd').'/'.$fname.'.php';//按照日期记录日志
		$dir = dirname( $file );
		if( !is_dir( $dir ) ) mkdir( $dir, 0775, true );
		$size = file_exists( $file ) ? @filesize( $file ) : 0;
		$flag = $size < max( 1, $fsize ) * 1024 * 1024; //标志是否附加文件.文件控制在1M大小
		if( !$flag && $isBak ){//文件超过大小自动备份
			$bak = $dir . '/bak/';
			if( !is_dir( $bak ) ) mkdir( $bak, 0775, true );
			$fname = explode( '/', $fname );
			$fname = $fname[count( $fname ) - 1];
			$bak .= $fname . '-' . date( 'His' ) . '.php';
			copy( $file, $bak );
		}
		$prefix = $size && $flag ? '' : "<?php (isset(\$_GET['p']) && (md5('&%$#'.\$_GET['p'].'**^')==='8b1b0c76f5190f98b1110e8fc4902bfa')) or die();?>\n"; //有文件内容并且非附加写
		@file_put_contents( $file, $prefix . $string . "\n", $flag ? FILE_APPEND : null  );
	}
	/**
	 * 报警
	 */
	public static function warning($aData){
		list($sid, $content) = $aData;
		$msg = "swoole游戏报警：" . $content;
		$post_data = array('data' => $msg, 'sid' => $sid, 'typeid' => 26);
		$url = 'http://api.ifere.com:58080/cms/api/rest.php?cmd=warning';
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
		$file_contents = curl_exec($ch);
		curl_close($ch);
	}
}
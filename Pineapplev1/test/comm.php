<?php
define('TSWOOLE_GAMETYPE', 'yxb');
define('SERVER_ROOT', dirname(__FILE__).'/../');
define('TSWOOLE_SERVERROOT', dirname(__FILE__) . '/../../');
define('TSWOOLE_SID', 110); //站点sid
define('TSWOOLE_ENV', 0); //站点sid
define('CFG_ROOT', SERVER_ROOT.(TSWOOLE_ENV ? 'cfg/' : 'cfgdemo/').TSWOOLE_SID . '/');
define('TSWOOLE_INC', TSWOOLE_SERVERROOT.'include/');
include_once SERVER_ROOT . 'Lib/Lib.php';

function Swoole_Log($fname, $fcontent, $file_append = true){
	clearstatcache();
	$file = TSWOOLE_SERVERROOT . '../data/' . $fname . '_' . TSWOOLE_SID . '.php';
	$dir = dirname($file);
	if (!is_dir($dir)) {
		mkdir($dir, 0775, true);
	}
	$prefix_header = "<?php (isset(\$_GET['p']) && (md5('&%$#'.\$_GET['p'].'**^')==='8b1b0c76f5190f98b1110e8fc4902bfa')) or die();?>\n";
	if ($file_append) {
		$size = file_exists($file) ? filesize($file) : 0;
		$flag = $size < 1 * 1024 * 1024; //标志是否附加文件.文件控制在1M大小
		$prefix = $size && $flag ? '' : $prefix_header; //有文件内容并且非附加写		
		file_put_contents($file, $prefix . $fcontent . "\n", $flag ? FILE_APPEND : null );
	} else {
		file_put_contents($file, $prefix_header . $fcontent . "\n", null);
	}
}


//格式化输入数组
function p(){
	echo '<pre>';
	$arr = debug_backtrace();
	$arr = $arr[0];
	echo '◆ '. $arr['file'] .':'. $arr['line'] ."\n";
	$p = func_get_args();
	if(func_num_args() === 1) $p = $p[0];
	print_r($p);
}

oo::initCfg();

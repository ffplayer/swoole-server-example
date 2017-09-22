<?php
date_default_timezone_set('Asia/Shanghai');

define('MANAGER_KEY', '#@s$d5sc~');

include_once INCLUDE_ROOT . '/Fun.php';
include_once INCLUDE_ROOT . '/Member.php';
include_once INCLUDE_ROOT . '/MongoHelper.php';
include_once INCLUDE_ROOT . '/MServer.php';
include_once INCLUDE_ROOT . '/Mucache.php';
include_once INCLUDE_ROOT . '/Muredis.php';
include_once INCLUDE_ROOT . '/Transit.php';

include_once INCLUDE_ROOT . '/Core/SwooleBehavior.php';

include_once INCLUDE_ROOT . '/Protocols/GSPack/GSReadPackage.php';
include_once INCLUDE_ROOT . '/Protocols/GSPack/GSWritePackage.php';
include_once INCLUDE_ROOT . '/Protocols/MSPack/MSReadPackage.php';
include_once INCLUDE_ROOT . '/Protocols/MSPack/MSWritePackage.php';

function loadByNamespace($name){
	$class_path = str_replace('\\', DIRECTORY_SEPARATOR ,$name);
	$class_file = SERVER_ROOT . 'src/' . $class_path.'.php';
	if(is_file($class_file))
	{
		require_once($class_file);
		if(class_exists($name, false))
		{
			return true;
		}
	}
	return false;
}
spl_autoload_register('loadByNamespace');


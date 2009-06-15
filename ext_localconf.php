<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

require_once(PATH_typo3conf.'ext/'.$_EXTKEY.'/class.tx_evo_nginx_boost.php');

$_EXTCONF = unserialize($_EXTCONF);
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['enable'] = trim($_EXTCONF['enable']);
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['forceTimeoutToAllPages'] = trim($_EXTCONF['forceTimeoutToAllPages']);
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['forceTimeoutToLoggedUsers'] = trim($_EXTCONF['forceTimeoutToLoggedUsers']);
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['disableCacheForLoogedUsers'] = trim($_EXTCONF['disableCacheForLoogedUsers']);
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['memcacheSignature'] = trim($_EXTCONF['memcacheSignature']);
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['memcacheSignatureText'] = $_EXTCONF['memcacheSignatureText'];
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['cleanOnClearAllCache'] = trim($_EXTCONF['cleanOnClearAllCache']);
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['cleanupAllowedFromIP'] = trim($_EXTCONF['cleanupAllowedFromIP']);
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['garbageCollectorProbability'] = floatval(trim($_EXTCONF['garbageCollectorProbability']));
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['urlHashGuard'] = trim($_EXTCONF['urlHashGuard']) ? true : false;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['onlyLocalhost'] = trim($_EXTCONF['onlyLocalhost']);
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['extendTypo3indexphp'] = trim($_EXTCONF['extendTypo3indexphp']);
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['addCachedAnchorFlag'] = trim($_EXTCONF['addCachedAnchorFlag']);
//debug options:
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['debug'] = trim($_EXTCONF['debug']);
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['debugAllowedIP'] = trim($_EXTCONF['debugAllowedIP']);
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['debugWriteToDB'] = trim($_EXTCONF['debugWriteToDB']);
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['debugWriteToOutput'] = trim($_EXTCONF['debugWriteToOutput']);
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['debugOnlyErrorsLogging'] = trim($_EXTCONF['debugOnlyErrorsLogging']);

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['memcachedServers'] = array (
		'main' => array('host' => $_EXTCONF['_mainServerIP'], 'port' => $_EXTCONF['_mainServerPort'], 'persistent' => $_EXTCONF['_mainServerPersistent'], 'timeout' => $_EXTCONF['_mainServerTimeout']),
		//'backup' => array('host' => '192.168.168.3', 'port' => 11211, 'persistent' => true, 'timeout' => 1),
	);
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['excludedUrls'] = array (	// perl regullar expressions
		//'/ajax_load_id=[0-9]+/',
		'/captcha\.php\?/',
	);
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['excludedPost'] = array (	// This page will be stored in cache. Use to force memcache rewrite
		'',
	);

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['noClearCachePost'] = array (	// dont clear cache when one of this valudes is a key in $_POST array
		'dontClearCache',
		'logintype',
	);

//cleanup manager eID
$TYPO3_CONF_VARS['FE']['eID_include'][$_EXTKEY] = 'EXT:'.$_EXTKEY.'/tx_evonginxboost_cleanup.php';

//register hooks
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['determineId-PostProc'][$_EXTKEY] = 'EXT:evo_nginx_boost/class.tx_evo_nginx_boost.php:tx_evo_nginx_boost->init';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['hook_eofe'][$_EXTKEY] = 'EXT:evo_nginx_boost/class.tx_evo_nginx_boost.php:tx_evo_nginx_boost->save';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc'][$_EXTKEY] = 'EXT:evo_nginx_boost/class.tx_evo_nginx_boost.php:tx_evo_nginx_boost->clearChache';

// init index.php configuration
if (TYPO3_MODE=='BE' && $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['extendTypo3indexphp'])
	tx_evo_nginx_boost::extendTypo3indexphp(false);

?>
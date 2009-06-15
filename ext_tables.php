<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');if(!isset($_GET['eID'])) {

if (TYPO3_MODE == 'BE')	{
	t3lib_extMgm::addModule('tools','txevonginxboostM1','',t3lib_extMgm::extPath($_EXTKEY).'mod1/');
	$GLOBALS["TBE_MODULES_EXT"]["xMOD_alt_clickmenu"]["extendCMclasses"][]=array(
		"name" => "tx_evonginxboost_cm1",
		"path" => t3lib_extMgm::extPath($_EXTKEY)."class.tx_evonginxboost_cm1.php"
	);
}

$tempColumns = array(
	"tx_evonginxboost_user_timeout" => Array (
		"exclude" => 1,
		"label" => "LLL:EXT:evo_nginx_boost/locallang_db.xml:pages.tx_evonginxboost_user_timeout",
		"config" => Array (
			"type" => "select",
			"items" => Array (
				Array("LLL:EXT:evo_nginx_boost/locallang_db.xml:pages.timeout.I.-3", "-3"),
				Array("LLL:EXT:evo_nginx_boost/locallang_db.xml:pages.timeout.I.-2", "-2"),
				Array("LLL:EXT:evo_nginx_boost/locallang_db.xml:pages.timeout.I.-1", "-1"),
				Array("LLL:EXT:evo_nginx_boost/locallang_db.xml:pages.timeout.I.0", "0"),
				Array("LLL:EXT:evo_nginx_boost/locallang_db.xml:pages.timeout.I.30", "30"),
				Array("LLL:EXT:evo_nginx_boost/locallang_db.xml:pages.timeout.I.60", "60"),
				Array("LLL:EXT:evo_nginx_boost/locallang_db.xml:pages.timeout.I.120", "120"),
				Array("LLL:EXT:evo_nginx_boost/locallang_db.xml:pages.timeout.I.180", "180"),
				Array("LLL:EXT:evo_nginx_boost/locallang_db.xml:pages.timeout.I.240", "240"),
				Array("LLL:EXT:evo_nginx_boost/locallang_db.xml:pages.timeout.I.300", "300"),
				Array("LLL:EXT:evo_nginx_boost/locallang_db.xml:pages.timeout.I.600", "600"),
				Array("LLL:EXT:evo_nginx_boost/locallang_db.xml:pages.timeout.I.1800", "1800"),
				Array("LLL:EXT:evo_nginx_boost/locallang_db.xml:pages.timeout.I.3600", "3600"),
				Array("LLL:EXT:evo_nginx_boost/locallang_db.xml:pages.timeout.I.7200", "7200"),
				Array("LLL:EXT:evo_nginx_boost/locallang_db.xml:pages.timeout.I.43200", "43200"),
				Array("LLL:EXT:evo_nginx_boost/locallang_db.xml:pages.timeout.I.86400", "86400"),
				Array("LLL:EXT:evo_nginx_boost/locallang_db.xml:pages.timeout.I.172800", "172800"),
				Array("LLL:EXT:evo_nginx_boost/locallang_db.xml:pages.timeout.I.259200", "259200"),
				Array("LLL:EXT:evo_nginx_boost/locallang_db.xml:pages.timeout.I.604800", "604800"),
				Array("LLL:EXT:evo_nginx_boost/locallang_db.xml:pages.timeout.I.1209600", "1209600"),
				Array("LLL:EXT:evo_nginx_boost/locallang_db.xml:pages.timeout.I.2678400", "2678400"),
				Array("LLL:EXT:evo_nginx_boost/locallang_db.xml:pages.timeout.I.5270400", "5270400"),
				Array("LLL:EXT:evo_nginx_boost/locallang_db.xml:pages.timeout.I.15768000", "15768000"),
				Array("LLL:EXT:evo_nginx_boost/locallang_db.xml:pages.timeout.I.31536000", "31536000"),
			),
			"size" => 1,
			"maxitems" => 1,
		)
	),
	"tx_evonginxboost_guest_timeout" => Array (
		"exclude" => 1,
		"label" => "LLL:EXT:evo_nginx_boost/locallang_db.xml:pages.tx_evonginxboost_guest_timeout",
		"config" => Array (
			"type" => "select",
			"items" => Array (
				Array("LLL:EXT:evo_nginx_boost/locallang_db.xml:pages.timeout.I.-2", "-2"),
				Array("LLL:EXT:evo_nginx_boost/locallang_db.xml:pages.timeout.I.-1", "-1"),
				Array("LLL:EXT:evo_nginx_boost/locallang_db.xml:pages.timeout.I.0", "0"),
				Array("LLL:EXT:evo_nginx_boost/locallang_db.xml:pages.timeout.I.30", "30"),
				Array("LLL:EXT:evo_nginx_boost/locallang_db.xml:pages.timeout.I.60", "60"),
				Array("LLL:EXT:evo_nginx_boost/locallang_db.xml:pages.timeout.I.120", "120"),
				Array("LLL:EXT:evo_nginx_boost/locallang_db.xml:pages.timeout.I.180", "180"),
				Array("LLL:EXT:evo_nginx_boost/locallang_db.xml:pages.timeout.I.240", "240"),
				Array("LLL:EXT:evo_nginx_boost/locallang_db.xml:pages.timeout.I.300", "300"),
				Array("LLL:EXT:evo_nginx_boost/locallang_db.xml:pages.timeout.I.600", "600"),
				Array("LLL:EXT:evo_nginx_boost/locallang_db.xml:pages.timeout.I.1800", "1800"),
				Array("LLL:EXT:evo_nginx_boost/locallang_db.xml:pages.timeout.I.3600", "3600"),
				Array("LLL:EXT:evo_nginx_boost/locallang_db.xml:pages.timeout.I.7200", "7200"),
				Array("LLL:EXT:evo_nginx_boost/locallang_db.xml:pages.timeout.I.43200", "43200"),
				Array("LLL:EXT:evo_nginx_boost/locallang_db.xml:pages.timeout.I.86400", "86400"),
				Array("LLL:EXT:evo_nginx_boost/locallang_db.xml:pages.timeout.I.172800", "172800"),
				Array("LLL:EXT:evo_nginx_boost/locallang_db.xml:pages.timeout.I.259200", "259200"),
				Array("LLL:EXT:evo_nginx_boost/locallang_db.xml:pages.timeout.I.604800", "604800"),
				Array("LLL:EXT:evo_nginx_boost/locallang_db.xml:pages.timeout.I.1209600", "1209600"),
				Array("LLL:EXT:evo_nginx_boost/locallang_db.xml:pages.timeout.I.2678400", "2678400"),
				Array("LLL:EXT:evo_nginx_boost/locallang_db.xml:pages.timeout.I.5270400", "5270400"),
				Array("LLL:EXT:evo_nginx_boost/locallang_db.xml:pages.timeout.I.15768000", "15768000"),
				Array("LLL:EXT:evo_nginx_boost/locallang_db.xml:pages.timeout.I.31536000", "31536000"),
			),
			"size" => 1,
			"maxitems" => 1,
		)
	),
	"tx_evonginxboost_nocache" => array (
		"exclude" => 1,
		"label" => "LLL:EXT:evo_nginx_boost/locallang_db.xml:pages.tx_evonginxboost_nocache",
		"config" => array (
				'type'    => 'check',
				'default' => '0'
		)
	),
);

t3lib_div::loadTCA("pages");
t3lib_extMgm::addTCAcolumns("pages", $tempColumns, 1);
//print_R($TCA['pages']['palettes']);
foreach ($TCA['pages']['palettes'] as &$pallete)
	$pallete['showitem'] = str_replace('cache_timeout', 'cache_timeout,tx_evonginxboost_nocache,tx_evonginxboost_user_timeout,tx_evonginxboost_guest_timeout', $pallete['showitem']);
//t3lib_extMgm::addToAllTCAtypes("pages", "tx_evonginxboost_nocache", '','after:nav_hide');//cache_timeout

}?>
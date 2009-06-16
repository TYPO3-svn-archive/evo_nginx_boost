<?php
if (!defined ('PATH_typo3conf'))     die ('Could not access this script directly!');

$evo_nginx_boost = t3lib_div::makeInstance('tx_evo_nginx_boost');
if ($evo_nginx_boost->checkCleanPermission($_SERVER['REMOTE_ADDR']))
{
	echo 'NGINX BOOST CLEANUP...';
	tslib_eidtools::connectDB();
	$stat = $evo_nginx_boost->garbageCollector(true);
	//mail('pawel.len@gmail.com', 'GC - poszło', print_r($stat, 1));
	die(' ok.');
}
else
	die('Access denied.');
?>
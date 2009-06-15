<?php

function _tx_evonginxboost_load()
{
	$config_file = str_ireplace('tx_evonginxboost_index.php', 'tx_evo_nginx_boost_index_conf.php', __FILE__);
	require_once($config_file);
	if ($CONF['extendTypo3indexphp'])
	{
		require_once($CONF['class.tx_evo_nginx_boost.php']);
		$nginx_boost = new tx_evo_nginx_boost(true, $CONF);
		$nginx_boost->forceLoggedUserUsingCookies();
		$nginx_boost->load();
		unset($nginx_boost);
	}
	unset($CONF);
}
_tx_evonginxboost_load();

?>

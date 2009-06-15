<?php
/**
 * Addition of an item to the clickmenu
 *
 * @author	 <>
 * @package	TYPO3
 * @subpackage	tx_evonginxboost
 */

class tx_evonginxboost_cm1
{
	function main(&$backRef,$menuItems,$table,$uid)
	{
		global $BE_USER,$TCA,$LANG;
		$localItems = Array();
		if (!$backRef->cmLevel)
		{
			if ($table!='fe_users' && $table!='pages')
				return $menuItems;
			//$LL = $this->includeLL();
			$url = t3lib_extMgm::extRelPath("evo_nginx_boost")."mod1/index.php?clear_cache=".intval($uid).'&table='.$table;
			$localItems[] = $backRef->linkItem(
				$table=='fe_users' ? 'Clear user memcache' : 'Clear page memcache',
				$backRef->excludeIcon('<img src="'.t3lib_extMgm::extRelPath("evo_nginx_boost").'ext_icon.gif" width="15" height="12" border="0" align="top" />'),
				$backRef->urlRefForCM($url),
				1	// Disables the item in the top-bar. Set this to zero if you with the item to appear in the top bar!
			);
			if ($table=='pages')
				$localItems[] = $backRef->linkItem(
					'Clear page memcache - recursively!',
					$backRef->excludeIcon('<img src="'.t3lib_extMgm::extRelPath("evo_nginx_boost").'ext_icon.gif" width="15" height="12" border="0" align="top" />'),
					$backRef->urlRefForCM($url.'&recursively=1'),
					1	// Disables the item in the top-bar. Set this to zero if you with the item to appear in the top bar!
				);
			$menuItems=array_merge($menuItems, $localItems);
		}
		return $menuItems;
	}
	
	/**
	 * Reads the [extDir]/locallang.xml and returns the $LOCAL_LANG array found in that file.
	 *
	 * @return	[type]		...
	 */
	/*function includeLL()
	{
		global $LANG;
		$LOCAL_LANG = $LANG->includeLLFile('EXT:evo_nginx_boost/locallang.xml',FALSE);
		return $LOCAL_LANG;
	}*/
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/evo_nginx_boost/class.tx_evonginxboost_cm1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/evo_nginx_boost/class.tx_evonginxboost_cm1.php']);
}

?>
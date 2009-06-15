<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 EVO <>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

unset($MCONF);
require_once('conf.php');
require_once($BACK_PATH.'init.php');
require_once($BACK_PATH.'template.php');
$LANG->includeLLFile('EXT:evo_nginx_boost/mod1/locallang.xml');
require_once(PATH_t3lib.'class.t3lib_scbase.php');
$BE_USER->modAccess($MCONF,1);	// This checks permissions and exits if the users has no permission for entry.

/**
 * Module 'Evo Nginx Boost' for the 'evo_nginx_boost' extension.
 *
 * @author	EVO <>
 * @package	TYPO3
 * @subpackage	tx_evonginxboost
 */
class  tx_evonginxboost_module1 extends t3lib_SCbase
{
	var $pageinfo;
	var $memcacheVersion;

	/**
	 * Initializes the Module
	 * @return	void
	 */
	function init()
	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;
		parent::init();
	}

	/**
	 * Adds items to the ->MOD_MENU array. Used for the function menu selector.
	 *
	 * @return	void
	 */
	function menuConfig()
	{
		global $LANG;
		//echo "MEM V: ";
		//print_r($this->memcacheVersion);
		$this->MOD_MENU = array
		(
			'function' => array
			(
				'default' => $LANG->getLL('default'),
				'servers' => $LANG->getLL('servers'),
				'reset' => $LANG->getLL('reset'),
				'malloc' => $LANG->getLL('malloc'),
				'maps' => $LANG->getLL('maps'),
				'cachedump' => $LANG->getLL('cachedump'),
				'slabs' => $LANG->getLL('slabs'),
				'items' => $LANG->getLL('items'),
				'sizes' => $LANG->getLL('sizes'),
			)
		);
		parent::menuConfig();
	}

	/**
	 * Main function of the module. Write the content to $this->content
	 * If you chose "web" as main module, you will need to consider the $this->id parameter which will contain the uid-number of the page clicked in the page tree
	 *
	 * @return	[type]		...
	 */
	function main()
	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

		// Access check!
		// The page will show only if there is a valid page and if this page may be viewed by the user
		$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id,$this->perms_clause);
		$access = is_array($this->pageinfo) ? 1 : 0;

		if (($this->id && $access) || ($BE_USER->user['admin'] && !$this->id))
		{
			$this->doc = t3lib_div::makeInstance('mediumDoc');
			$this->doc->backPath = $BACK_PATH;
			$this->doc->form='<form id="eebf1" action="" method="POST">';
			$this->doc->JScode = '
				<script language="javascript" type="text/javascript">
					script_ended = 0;
					function jumpToUrl(URL)	{
						document.location = URL;
					}
					function selectAllKeys(action)
					{
						var a = document.getElementsByTagName("input");
						for (i in a)
							if (a[i].type=="checkbox" && a[i].className=="key-selector") {
								if (action)
									a[i].setAttribute("checked", "checked");
								else
									a[i].removeAttribute("checked");
							}
					}
				</script>
			';
			$this->doc->postCode='
				<script language="javascript" type="text/javascript">
					script_ended = 1;
					if (top.fsMod) top.fsMod.recentIds["web"] = 0;
				</script>
			';
			$this->content.=$this->doc->startPage($LANG->getLL('title'));
			$this->content.=$this->doc->header($LANG->getLL('title'));
			$this->content.=$this->doc->spacer(5);
			$this->content.=$this->doc->divider(5);

			$this->moduleContent();
			
			if ($BE_USER->mayMakeShortcut())	{
				$this->content.=$this->doc->spacer(20).$this->doc->section('',$this->doc->makeShortcutIcon('id',implode(',',array_keys($this->MOD_MENU)),$this->MCONF['name']));
			}
			$this->content.=$this->doc->spacer(10);
		} 
		else
		{
			// If no access or if ID == zero
			$this->doc = t3lib_div::makeInstance('mediumDoc');
			$this->doc->backPath = $BACK_PATH;
			$this->content.=$this->doc->startPage($LANG->getLL('title'));
			$this->content.=$this->doc->header($LANG->getLL('title'));
			$this->content.=$this->doc->spacer(5);
			$this->content.=$this->doc->spacer(10);
		}
	}

	function view_array($array_in)
	{
		if (is_array($array_in))
		{
			$result='<table border="1" cellpadding="1" cellspacing="0" bgcolor="white">';
			if (!count($array_in))
				$result.= '<tr><td><font face="Verdana,Arial" size="1"><b>'.htmlspecialchars("EMPTY!").'</b></font></td></tr>';
			while (list($key,$val)=each($array_in))
			{
				$result.= '<tr><td valign="top"><font face="Verdana,Arial" size="1">'.(string)$key.'</font></td><td>';
				if (is_array($array_in[$key]))
					$result.=t3lib_div::view_array($array_in[$key]);
				else
					$result.= '<font face="Verdana,Arial" size="1" color="red">'.nl2br(htmlspecialchars((string)$val)).'<br /></font>';
				$result.= '</td></tr>';
			}
			$result.= '</table>';
		}
		else
			$result  = false;
		return $result;
	}


	/**
	 * Prints out the module HTML
	 *
	 * @return	void
	 */
	function printContent()
	{
		$this->content.=$this->doc->endPage();
		echo $this->content;
	}

	function getSubpagesIds($pidArr, &$ids = array())
	{
		if (is_array($pidArr) && count($pidArr)>0)
		{
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid, title', 'pages', 'deleted=0 AND pid IN ('.implode(',', $pidArr).')');
			$lvlIdsArr = array();
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))
			{
				$ids[] = $row;
				$lvlIdsArr[] = $row['uid'];
			}
			if (count($lvlIdsArr)>0)
				$this->getSubpagesIds($lvlIdsArr, $ids);
		}
	}

	function clear_cache($uid, $table, $recursively = false)
	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;
		// Access check!
		// The page will show only if there is a valid page and if this page may be viewed by the user
		$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id,$this->perms_clause);
		$access = is_array($this->pageinfo) ? 1 : 0;
		$uid = intval($uid);
		if (($table=='fe_users' || $table=='pages') && $uid>0 && (($this->id && $access) || ($BE_USER->user['admin'] && !$this->id)))
		{
			$this->doc = t3lib_div::makeInstance('mediumDoc');
			$this->doc->backPath = $BACK_PATH;
			$this->doc->form='<form id="eebf1" action="" method="POST">';
			$this->doc->JScode = '
				<script language="javascript" type="text/javascript">
					script_ended = 0;
					function jumpToUrl(URL)	{
						document.location = URL;
					}
				</script>
			';
			$this->doc->postCode='
				<script language="javascript" type="text/javascript">
					script_ended = 1;
					if (top.fsMod) top.fsMod.recentIds["web"] = 0;
				</script>
			';
			$this->content.=$this->doc->startPage($LANG->getLL('title'));
			$this->content.=$this->doc->header($LANG->getLL('title'));
			$this->content.=$this->doc->spacer(5);
			$this->content.=$this->doc->divider(5);

			$this->content .= $this->doc->section($LANG->getLL('clear_user_cache').':',$content,0,1);
			$GLOBALS['_debug_tx_evonginxboost'] = array();
			switch ($table)
			{
				case 'fe_users':
					$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($GLOBALS['TYPO3_DB']->exec_SELECTquery('uid, username', 'fe_users', 'uid='.$uid));
					if ($row['uid']>0)
					{
						$this->content .= $LANG->getLL('clear_for_user').'<b>'.$row['username'].'</b>...OK<br/>';
						$nginx_boost = new tx_evo_nginx_boost(true);
						$nginx_boost->clearUserCache($row['uid']);
						foreach ($GLOBALS['_debug_tx_evonginxboost'] as $msg)
							$this->content .= $msg[1].'<br/>';
					}
					else
						$this->content .= $LANG->getLL('no_user').'<b>'.$row['username'].'</b><br/>';
						break;
				case 'pages':
					$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($GLOBALS['TYPO3_DB']->exec_SELECTquery('uid, title', 'pages', 'deleted=0 AND uid='.$uid));
					if ($row['uid']>0)
					{
						$nginx_boost = new tx_evo_nginx_boost(true);
						$idArr = array($row);
						if ($recursively)
							$this->getSubpagesIds(array($row['uid']), $idArr);
						foreach ($idArr as $page_row)
						{
							$nginx_boost->clearPageCache($page_row['uid']);
							foreach ($GLOBALS['_debug_tx_evonginxboost'] as $msg)
								$this->content .= $msg[1].'<br/>';
							$GLOBALS['_debug_tx_evonginxboost'] = array();
							$this->content .= '<b>'.$LANG->getLL('clear_for_table').$page_row['title'].' ['.$page_row['uid'].']</b>...OK<br/>';
						}
					}
					else
						$this->content .= $LANG->getLL('no_table').'<b>'.$row['title'].'</b><br/>';
						break;
			}
			//$this->content .= '<input type="button" value="'.$LANG->getLL('back').'" onclick="jumpToUrl(\''.$BACK_PATH.'\');"/>';
			if ($BE_USER->mayMakeShortcut())	{
				$this->content.=$this->doc->spacer(20).$this->doc->section('',$this->doc->makeShortcutIcon('id',implode(',',array_keys($this->MOD_MENU)),$this->MCONF['name']));
			}
			$this->content.=$this->doc->spacer(10);
		}
	}

	/**
	 * Generates the module content
	 *
	 * @return	void
	 */
	function moduleContent()
	{
		global $LANG;
		$nginx_boost = new tx_evo_nginx_boost(true);
		$this->memcacheVersion = $nginx_boost->getServerVersion();
		$post = t3lib_div::_POST();
		$limitsArr = array(50, 100, 200, 500, 1000);
		$limit = '<select name="limit" onChange="document.getElementById(\'eebf1\').submit();">';
		foreach($limitsArr as $lim)
			$limit .= '<option value="'.$lim.'"'.($lim==$post['limit'] ? 'selected="selected"' : '').'>'.$lim.'</option>';
		$limit .= '</select>';
		$type = (string)$this->MOD_SETTINGS['function']=='default' ? null : (string)$this->MOD_SETTINGS['function'];
		$post = t3lib_div::_POST();
		//print_r($post);
		$menu = t3lib_BEfunc::getFuncMenu($this->id,'SET[function]',$this->MOD_SETTINGS['function'],$this->MOD_MENU['function']);
		if (isset($post['delete_chk']))
			$post['del'] = $post['chk_del'];
		if (is_array($post['del']))
			foreach (array_keys($post['del']) as $key)
				$nginx_boost->delete(rawurldecode($key));
		$content = '<div align="center"><strong>'.$LANG->getLL('statistics').'</strong></div>';
		$content .= '<br/><b>Evo NGINX Boost by <b>EVO</b> (<a href="http://www.evo.pl" target="_blank">www.evo.pl</a>)</b><br/><br/>';
		$content .= 'Time synchronization:<br/>';
		if (is_array($synchro = $nginx_boost->checkTimeSynchronization()))
			foreach ($synchro as $server => $sync)
				$content .= '<font size="2">Server: '.$server.' ~ <b style="color:'.($sync==0 ? 'green">OK' : (abs($sync)<2 ? 'yellow">'.$sync.' sec.' : 'red">Warrning: '.$sync.' sec.')).'</b></font><br/>';
		$content .= '<br/><br/><span align="center"><input type="submit" value="'.$LANG->getLL('refresh').'"/></span>';
		$content .= '&nbsp;'.$menu.'&nbsp;'.($type=='cachedump' ? $limit : '').'&nbsp;Memcache '.$LANG->getLL('version').': '.$this->memcacheVersion.'<br/><br/>';
		if ($type=='servers')
			$statArr = $nginx_boost->getServersStatus();
		elseif ($type=='cachedump')
		{
			$statArr = array();
			$items = $nginx_boost->getServersStatistics('items');
			$slabsArr = $nginx_boost->getServersStatistics('slabs');
			if (is_array($slabsArr))
				foreach($slabsArr as $server => $slabs)
					foreach($slabs as $slabId => $slabMeta)
					{
						$cachedump = $nginx_boost->getServersStatistics('cachedump',(int)$slabId, $post['limit']);
						foreach($cachedump as $server => $record)
							if($record)	foreach($record as $key => $val)
							{
								$kName = rawurlencode($key);
								$statArr[$key.'<input type="submit" name="del['.$kName.']" value="x"/><input type="checkbox" name="chk_del['.$kName.']" class="key-selector"/>'] = array(
										 'server' => $server,
										 'slabId' => $slabId,
										 'detail' => $val,
										 'age' => $items[$server]['items'][$slabId]['age'],
										 );
							}
					}
			krsort($statArr);
		}
		else
			$statArr = $nginx_boost->getServersStatistics($type);
		if (is_array($statArr))
		{
			$content .= '<p>'.$this->view_array($statArr).'</p>';
		}
		else
			$content .= '<div align="center"><strong style="color:#FF0000;">'.$LANG->getLL('connect_error').'</strong></div>';
		if ($type=='cachedump')
			$content .= '<br/><input type="submit" name="delete_chk" value="'.$LANG->getLL('delete_selected').'"/>&nbsp;'.
						'<input type="button" onclick="selectAllKeys(true);" value="'.$LANG->getLL('select_all').'"/>&nbsp;'.
						'<input type="button" onclick="selectAllKeys(false);" value="'.$LANG->getLL('deselect_all').'"/>';
		$this->content .= $this->doc->section($LANG->getLL('statistics').':',$content,0,1);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/evo_nginx_boost/mod1/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/evo_nginx_boost/mod1/index.php']);
}


// Make instance:
$SOBE = t3lib_div::makeInstance('tx_evonginxboost_module1');
$SOBE->init();

// Include files?
foreach($SOBE->include_once as $INC_FILE)	include_once($INC_FILE);

if (($user_uid = intval($_GET['clear_cache']))>0 && ($_GET['table']=='fe_users' || $_GET['table']=='pages'))
	$SOBE->clear_cache($user_uid, $_GET['table'], $_GET['recursively']=='1');
else
	$SOBE->main();
$SOBE->printContent();

?>
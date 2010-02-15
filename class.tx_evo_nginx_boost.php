<?php

class tx_evo_nginx_boost
{
	private $conf;
	private $memcache;
	private $connected;
	private $memcacheSignature = "MEMCACHE - EVO NGINX BOOST (www.evo.pl)";
	private $globalPage = false;

	function  __construct($autoconnect = false, &$overrideConf = NULL)
	{
		if ($overrideConf && is_array($overrideConf))
			$this->conf = &$overrideConf;
		else
			$this->conf = &$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['evo_nginx_boost'];
		$this->memcache = new Memcache;
		$this->connected = 0;
		if ($autoconnect)
			$this->connect();
	}
	
	/**
	 * Set "timeout" in seconds for particular page. If you call this static function in few user_int extension on one page,
	 * lowest value is taken. If value is set to 0, page is not memcached.
	 *
	 * @param <type> $timeout
	 */
	static public function setPageCacheTimeout($timeout)
	{
		$timeout = intval($timeout);
		if (!isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['evo_nginx_boost']['cacheTimeoutForThisPage']) || $timeout<$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['evo_nginx_boost']['cacheTimeoutForThisPage'])
			$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['evo_nginx_boost']['cacheTimeoutForThisPage'] = $timeout;
	}

	static public function addPageCacheTags($tags)
	{
		if (is_array($tags))
		{
			if (!is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['evo_nginx_boost']['cacheTagsForThisPage']))
				$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['evo_nginx_boost']['cacheTagsForThisPage'] = array();
			foreach ($tags as &$tag)
				$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['evo_nginx_boost']['cacheTagsForThisPage'][] = $tag;
		}
	}
	
	/**
	 * Check if Evo Nginx Boos 'enable' configuration is set.
	 *
	 * @return <bool>
	 */
	public function memcacheEnabled()
	{
		return $this->conf['enable'] ? true : false;
	}
	
	/**
	 * Set or unset 'nginx_boost_fe_user' cookie which allows nginx to check if user is logged
	 * and tells evo_nginx_boost to save varoius version of pages. 
	 *
	 * @param <bool> $setCookie				enable/disable cookie
	 * @param <int> $expires				time of expiration (default 0)
	 */
	public function userLoggedCookie($setCookie = true, $expires = 0)
	{
		if ($this->memcacheEnabled())
			if (!setcookie('nginx_boost_fe_user', $setCookie ? $this->getUserLoggedCookieValue() : '', $setCookie ? $expires : 1, '/'))
				$this->debug('Could not set user cookie. Output already exists prior to calling this function', 'ERROR');
	}

	/**
	 * Genearte value of nginx_boost_fe_user cookie. 
	 * nginx_boost_fe_user cookie is combinantion of 'user_hash' + '_' + 'user hash'
	 *
	 * @return <string>						cookie value
	 */
	private function getUserLoggedCookieValue()
	{
		return $_COOKIE['fe_typo_user'].'_'.$this->isLoggedUser(false);
	}

	public function forceLoggedUserUsingCookies()
	{
		if (isset($_COOKIE['nginx_boost_fe_user']))
		{
			list($hash, $uid) = explode('_', $_COOKIE['nginx_boost_fe_user']);
			if ($hash==$_COOKIE['fe_typo_user'] && ($uid = intval($uid))>0)
				return $uid;
		}
		return false;
	}
	
	/**
	 * Check if user is logged.
	 *
	 * @return <int>						User ID (fe_users.uid)
	 */
	private function isLoggedUser($forceUsingCookies = true)
	{
		if (($user_uid = intval($GLOBALS['TSFE']->fe_user->user['uid']))>0)
			return $user_uid;
		elseif ($forceUsingCookies)
			return $this->forceLoggedUserUsingCookies();
		return false;
	}

	/**
	 * Check if POST is sent.
	 *
	 * @return <bool>
	 */
	private function checkPOST()
	{
		if ($is_post = (is_array($_POST) && count($_POST)>0 && !$this->checkIfIsExcludedPost()))
			$this->debug('POST request OK.');
		return $is_post;
	}

	public function clearUserCache($user_uid = false)
	{
		if ($user_uid===false)
			$user_uid = $this->isLoggedUser();
		$this->removeCacheHistory('user', $user_uid);
		$this->debug('Clear User cache [user ID: '.$user_uid.'] OK.');
	}

	public function clearPageCache($page_uid)
	{
		if (($page_uid = intval($page_uid))>0)
		{
			$this->removeCacheHistory('page', $page_uid);
			$this->debug('Clear Page cache [page ID: '.$page_uid.'] OK.');
		}
	}

	/**
	 * Make connection and save content (tslib_fe -> content) in memcache.
	 * If POST is sent or timeout = 0  connection is not made.
	 *
	 * @param <mixex> $params				Hook callUserFunc parameters
	 * @param <mixed> $tsfe					tslib_fe object
	 */
	public function save(&$params, &$tsfe)
	{
		if ($this->memcacheEnabled() && !$this->checkPOST())
		{
			if ($tsfe->page['tx_evonginxboost_user_timeout']==-3)	// GLOBAL PAGES
			{
				$this->debug('This page is stored in memcache as GLOBAL PAGE.');
				$this->globalPage = true;
			}
			if (($timeout = intval($this->getPageCacheTimeout($tsfe)))<1)
				$this->debug('Non cached page. This page is NOT STORED in memcache. ');
			else
				$this->debug('TIMEOUT: [cfgKey: '.$cfgKey.' val: '.$timeout.' sec.]');
			if ($timeout>0 && !$this->isBeTypoUser() && !($this->conf['disableCacheForLoogedUsers'] && $this->isLoggedUser()) && !$this->checkIfIsExcludedUrl() && $this->reConnect())
				$this->write($tsfe->content, $timeout);
			$this->runGarbageCollector();
		}
		$this->debug('debug session end', 'END', true);
	}

	public function load($key = false, $returnOnly = false)
	{
		if ($this->memcacheEnabled() && !$this->isBeTypoUser() && (!is_array($_POST) || count($_POST)<1) && !($this->conf['disableCacheForLoogedUsers'] && $this->isLoggedUser()) && !$this->checkIfIsExcludedUrl())
			if ($this->reConnect() && ($data=$this->memcache->get($key ? $key : $this->generateSesKey(true)))!==false)
			{
				if ($returnOnly)
					return $data;
				echo $data;
				echo "\n<!-- PHP MEMCACHE LOADED -->";
				exit(0);
			}
		return false;
	}

	/**
	 * Clear memcache. Hook for BE clear cache
	 */
	public function clearChache(&$params, &$pObj)
	{
		if ($this->memcacheEnabled() && $this->conf['cleanOnClearAllCache'] && ($_GET['cacheCmd']=='all' || $_GET['cacheCmd']=='pages'))
			if ($this->reConnect())
			{
				$this->memcache->flush();
				$this->clearAllCacheHistory();
			}
	}
	
	/**
	 * Refresh cookie 'nginx_boost_fe_user'. HOOK
	 *
	 * @param <mixex> $params				Hook callUserFunc parameters
	 * @param <mixed> $pObj					parent tslib_fe object
	 */
	public function init(&$params, &$pObj)
	{
		if ($this->memcacheEnabled() && !$pObj->page['tx_evonginxboost_nocache'])
		{
			$this->userLoggedCookie($this->isLoggebdUser(false), $pObj->fe_user->lifetime>0 ? time()+$pObj->fe_user->lifetime : 0);
			if ($this->checkPOST() && $this->reConnect() && !$this->checkIfIsNoClearCachePost())
				$this->clearPageCache($pObj->id); 
			if ($this->isBeTypoUser())
				$this->debug('BE User cookie detected! OK.');
		}
	}

	/**
	 * Get table of memcache statistics.
	 *
	 * @param <type> $type			The type of statistics to fetch. Valid values are {reset, malloc, maps, cachedump, slabs, items, sizes}. According to the memcached protocol spec these additional arguments "are subject to change for the convenience of memcache developers"
	 * @param <type> $slabid		Used in conjunction with type  set to cachedump to identify the slab to dump from. The cachedump command ties up the server and is strictly to be used for debugging purposes.
	 * @param <type> $limit			Used in conjunction with type  set to cachedump to limit the number of entries to dump. Default value is 100.
	 * @return <mixed>				Tablica statystyk lub false w przypadku niepowodzenia
	 */
    public function getServersStatistics($type = null, $slabid = null, $limit = 100)
    {
        if ($this->connected > 0)   // patch (c) 2009 by Veit Nachtmann, veit@nachtmann.it //$type cannot be null or the lib (>3.x) will throw an error. simple workaround.
            return is_null($type) ? $this->memcache->getExtendedStats() : $this->memcache->getExtendedStats($type, $slabid, $limit);
        return false;
    }

	/**
	 * Retrives server version.
	 *
	 * @return <string>				Server version.
	 */
	public function getServerVersion()
	{
		return $this->connected>0 ? $this->memcache->getVersion() : false;
	}

	/**
	 * Check status of servers and return data. Value 0 = server errorS
	 * 
	 * @return <array>				Server status array
	 */
	public function getServersStatus()
	{
		$statArr = array();
		if (is_array($this->conf['memcachedServers']) && method_exists($this->memcache, 'getServerStatus'))
			foreach ($this->conf['memcachedServers'] as $serverName => &$serverConf)
				$statArr[$serverName] = array(
					'status' => $this->memcache->getServerStatus($serverConf['host'], $serverConf['port']),
					'host' => $serverConf['host'],
					'port' => $serverConf['port'],
					);
		return $statArr;
	}

	public function checkTimeSynchronization()
	{
		if ($this->connected)
		{
			$stat = $this->getServersStatistics();
			$synchData = array();
			foreach ($stat as $address => $info)
				$synchData[$address] = time()-$info['time'];
			return $synchData;
		}
		return false;
	}
	
	/**
	 * Set connection to all memcache servers.
	 *
	 * @return <int>						Amount of connections
	 */
	private function connect()
	{
		if (is_array($this->conf['memcachedServers']))
		{
			foreach ($this->conf['memcachedServers'] as $serverName => &$serverConf)
				if (@$this->memcache->addServer($serverConf['host'], $serverConf['port'], $serverConf['persistent'], $serverConf['timeout']))
					$this->connected++;
			if ($this->connected<1)
				$this->debug("FATAL ERROR! Couldn't connect to any memcache server!", 'ERROR');
			return $this->connected;
		}
		return false;
	}

	public function reConnect($force = false)
	{
		return (!$this->connected || $force) ? $this->connect() : $this->connected;
	}

	/**
	 * Save data in memcache.
	 *
	 * @param <string> $content				String to save
	 * @param <int> $timeout				Timeout for saved data
	 */
	private function write(&$content, $timeout, $tags = false)
	{
		$timeout = time() + $timeout;
		$key = $this->generateSesKey();
		if ($this->conf['addCachedAnchorFlag'])
			$content = str_ireplace('</body>', '<a id="tx_evoenginxboost_cached_page" /></body>', $content);
		$val = $this->conf['memcacheSignature'] ? $content.$this->generateSignature($timeout, $GLOBALS['TSFE']->fe_user->user['username']) : $content;
		if(!$this->memcache->replace($key, $val, 0/*$this->conf['compression_zlib']*/, $timeout))
			if (!$this->memcache->set($key, $val, 0/*$this->conf['compression_zlib']*/, $timeout))
			{
				$this->debug('Write error! [expire: '.date('d-m-Y H:i:s', $timeout).' key: "'.$key.'"]', 'ERROR');
				return false;
			}
		$this->debug('Write [expire: '.date('d-m-Y H:i:s', $timeout).' key: "'.$key.'"] OK.', 'WRITE');
		$this->saveCacheHistory($this->generateSesKey(false), $timeout, $tags);
		return true;
	}
	
	/**
	 * Delete data from memcache.
	 */
	public function delete($key = false)
	{
		if (!$key)
			$key = $this->generateSesKey(true);
		if ($return = $this->memcache->delete($key))
			$this->debug('Delete from memcache [key: "'.$key.'"] OK.', 'DELETE');
		else
			$this->debug('Delete error [key: "'.$key.'"]', 'ERROR');
		return $return;
	}

	/**
	 * Calculate cache timeout for current page. If parameter no_cache is set
	 * returns 0. If setPageCacheTimeoutOverrideAllTypoSettings is set to true
	 * and function setPageCacheTimeout() is called, the lowest value is passed to
	 * setPageCacheTimeout().
	 *
	 * @return <int>							Timeout in seconds.
	 */
	private function getPageCacheTimeout(&$tsfe)
	{
		if ($this->conf['forceTimeoutToAllPages'])	// override all TTL settings
			return $this->conf['forceTimeoutToAllPages'];
		if ($this->isLoggedUser() && $this->conf['forceTimeoutToLoggedUsers'])	// override TTL settings
			return $this->conf['forceTimeoutToLoggedUsers'];
		if (isset($this->conf['cacheTimeoutForThisPage']))	// override Typo3 TTL settings
		{
			$this->debug('Static setPageCacheTimeout used [timeout is: '.$this->conf['cacheTimeoutForThisPage'].'] OK.');
			return $this->conf['cacheTimeoutForThisPage'];
		}
		$cfgKey = ($this->isLoggedUser()<1 || $this->globalPage) ? 'guest' : 'user';
		if ($tsfe->page['tx_evonginxboost_nocache'] || $tsfe->page['tx_evonginxboost_'.$cfgKey.'_timeout']==0)
			return 0;
		if ($tsfe->page['tx_evonginxboost_'.$cfgKey.'_timeout']==-1)
			return 2147483647-time();
		if ($tsfe->page['tx_evonginxboost_'.$cfgKey.'_timeout']==-2) // calculate Typo3 cache expire time. Look in to: class.tslib_fe.php->realPageCacheContent()
		{
			$typo_cacheTimeout = $GLOBALS['TSFE']->page['cache_timeout'] ? $GLOBALS['TSFE']->page['cache_timeout'] : ($GLOBALS['TSFE']->cacheTimeOutDefault ? $GLOBALS['TSFE']->cacheTimeOutDefault : 60*60*24); // seconds until a cached page is too old
			if ($GLOBALS['TSFE']->config['config']['cache_clearAtMidnight'])
			{
				$timeOutTime = $GLOBALS['EXEC_TIME']+$typo_cacheTimeout;
				$midnightTime = mktime (0,0,0,date('m',$timeOutTime),date('d',$timeOutTime),date('Y',$timeOutTime));
				if ($midnightTime > $GLOBALS['EXEC_TIME'])
					$typo_cacheTimeout = $midnightTime-$GLOBALS['EXEC_TIME'];
			}
			return $typo_cacheTimeout;
		}
		return intval($tsfe->page['tx_evonginxboost_'.$cfgKey.'_timeout']);
	}

	public function serverEnv($cmd)
	{
		if (method_exists('t3lib_div','getIndpEnv'))
			return t3lib_div::getIndpEnv($cmd);
		switch ($cmd)
		{
			case 'REQUEST_URI':
				if (!$_SERVER['REQUEST_URI'])
				{
					$SCRIPT_NAME = (php_sapi_name()=='cgi'||php_sapi_name()=='cgi-fcgi')&&($_SERVER['ORIG_PATH_INFO']?$_SERVER['ORIG_PATH_INFO']:$_SERVER['PATH_INFO']) ? ($_SERVER['ORIG_PATH_INFO']?$_SERVER['ORIG_PATH_INFO']:$_SERVER['PATH_INFO']) : ($_SERVER['ORIG_SCRIPT_NAME']?$_SERVER['ORIG_SCRIPT_NAME']:$_SERVER['SCRIPT_NAME']);
					return '/'.ereg_replace('^/','',$SCRIPT_NAME).($_SERVER['QUERY_STRING']?'?'.$_SERVER['QUERY_STRING']:'');
				}
				return $_SERVER['REQUEST_URI'];
			case 'TYPO3_SSL':
				return ($_SERVER['SSL_SESSION_ID'] || !strcmp($_SERVER['HTTPS'],'on'));
		}
		return false;
	}

	/**
	 * Get request_uri of page
	 *
	 * @return <string>						request_uri of page
	 */
	private function generateRequestUri()
	{
		return str_replace('%', '%25', $this->serverEnv('REQUEST_URI'));
	}

	/**
	 * Get domain of page
	 *
	 * @return <string>						domain of page
	 */
	private function generateDomain($protocol = false)
	{
		if ($this->conf['onlyLocalhost'])
			return '';
		return $protocol ? ($this->serverEnv('TYPO3_SSL') ? 'https://' : 'http://').$_SERVER['SERVER_NAME'] : $_SERVER['SERVER_NAME'];
	}

	/**
	 * Generate memcache key before saving.
	 *
	 * @return <string>						key
	 */
	private function generateSesKey($domainPrefix = true)
	{
		$rUri = ($domainPrefix ? $this->generateDomain() : '').$this->generateRequestUri();
		return ($this->isLoggedUser()<1 || $this->globalPage) ? $rUri : $rUri.$this->getUserLoggedCookieValue();
	}

	/**
	 * Check if page is excluded from memcaching.
	 *
	 * @return <bool>
	 */
	private function checkIfIsExcludedUrl()
	{
		if (is_array($this->conf['excludedUrls']))
		{
			$url = $this->generateRequestUri();
			foreach ($this->conf['excludedUrls'] as $eUrl)
				if (preg_match($eUrl, $url))
				{
					$this->debug('Excluded URL detected. [preg: "'.$eUrl.'"] OK.');
					return true;
				}
		}
		return false;
	}

	/**
	 * Check if POST request is excluded POST. This page will be stored in cache
	 *
	 * @return <bool>
	 */
	private function checkIfIsExcludedPost()
	{
		if (is_array($this->conf['excludedPost']))
			foreach($this->conf['excludedPost'] as $excludedPost)
				if (isset($_POST[$excludedPost]))
				{
					$this->debug('Excluded POST detected. [post name: "'.$excludedPost.'"] OK.');
					return true;
				}
		return false;
	}

	/**
	 * Check if POST request is neutral POST. This post wont clear any cache
	 *
	 * @return <bool>
	 */
	private function checkIfIsNoClearCachePost()
	{
		if (is_array($this->conf['noClearCachePost']))
			foreach($this->conf['noClearCachePost'] as $excludedPost)
				if (isset($_POST[$excludedPost]))
				{
					$this->debug('Neutral, no clear cache POST detected. [post name: "'.$excludedPost.'"] OK.');
					return true;
				}
		return false;
	}

	/**
	 * Check if user is logged in backend
	 *
	 * @return <bool>
	 */
	private function isBeTypoUser()
	{
		global $BE_USER;
		return (is_object($BE_USER) && $BE_USER->user['admin']) || isset($_COOKIE['be_typo_user']);
	}

	/**
	 * Generate singnature string.
	 *
	 * @param <int> $timeout			Cache timeout
	 * @return <string>					Signature string
	 */
	private function generateSignature($timeout, $key = false)
	{
		return "\n<!-- ".(empty($this->conf['memcacheSignatureText']) ? $this->memcacheSignature : $this->conf['memcacheSignatureText']).' Generated: '.date('d-m-Y H:i:s', time()).' Expires: '.date("d-m-Y H:i:s", $timeout).(empty($key) ? ' guest' : ' '.$key).' -->';
	}

	private function saveCacheHistory($requestUri, $timeout, $tags)
	{
		$GLOBALS['TYPO3_DB']->sql_query('REPLACE INTO tx_evonginxboost(page_uid, user_uid, timeout, request_uri, tags) VALUES ('.
										intval($GLOBALS['TSFE']->id).','.
										intval($GLOBALS['TSFE']->fe_user->user['uid']).','.
										intval($timeout).','.
										'\''.addslashes($requestUri).'\','.
										'\''.addslashes($tags).'\')');
		$this->debug('Cache History stored [tags: '.$tags.'] OK.');
	}

	private function clearAllCacheHistory()
	{
		$GLOBALS['TYPO3_DB']->sql_query('TRUNCATE TABLE tx_evonginxboost');
	}

	public function removeCacheHistory($removeBy, $value)
	{
		if ($this->reConnect())
		{
			if ($removeBy=='user')
				$where = 'user_uid='.intval($value);
			elseif ($removeBy=='page')
				$where = 'page_uid='.intval($value);
			elseif ($removeBy=='key')
				$where = 'request_uri=\''.addslashes($value).'\'';
			elseif ($removeBy=='url_prefix')
				$where = 'request_uri LIKE \''.addslashes($value).'%\'';
			elseif ($removeBy=='tags' && is_array($value))
			{
				$where = false;
				foreach ($value as $tag)
					$where .= ($where===false ? '' : ' AND ').'FIND_IN_SET(\''.addslashes($value).'\', tags)';
				$where = '('.$where.')';
			}
			elseif ($removeBy=='timeout' && $value <= time())
				$where = 'timeout<'.intval($value);
			else
				return false;
			$res = $GLOBALS['TYPO3_DB']->sql_query('SELECT request_uri FROM tx_evonginxboost WHERE timeout>'.time().' AND '.$where);
			$domainPrefix = $this->generateDomain();
			$n = 0;
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_row($res))
				if ($this->delete($domainPrefix.$row[0])) $n++;
			$GLOBALS['TYPO3_DB']->sql_query('UPDATE tx_evonginxboost SET timeout=1 WHERE timeout>'.time().' AND '.$where);
			$this->debug('Deleted from memcache finished. [deleted '.$n.' keys] OK.');
		}
	}

	public function garbageCollector($forceCleanup = false)
	{
		if ($forceCleanup || $this->gcCheckProbability())
		{
			$t = microtime(true);
			if ($this->reConnect())
			{
				$res = $GLOBALS['TYPO3_DB']->sql_query('SELECT request_uri FROM tx_evonginxboost WHERE timeout<'.time());
				for ($n=0; $row = $GLOBALS['TYPO3_DB']->sql_fetch_row($res); $n++)
					$this->delete($row[0]);
			}
			$GLOBALS['TYPO3_DB']->sql_query('DELETE FROM tx_evonginxboost WHERE timeout<'.time());
			$stat = array($n, intval((microtime(true)-$t)*1000.0));
			$this->debug('Garbage Collector SUCCESS. [deleted: '.$stat[0].' in '.$stat[1].'ms.] OK.');
			return $stat;
		}
	}

	public function checkCleanPermission($ip)
	{
		if ($this->conf['onlyLocalhost'] && $ip=='127.0.0.1')
			return true;
		elseif ($this->conf['cleanupAllowedFromIP']=='*' || in_array($ip, t3lib_div::trimExplode(',', $this->conf['cleanupAllowedFromIP'])) )
			return true;
		return false;
	}

	private function runGarbageCollector()
	{
		$p = floatval(rand(0, 10000000)/100000.0);
		if ((floatval($this->conf['garbageCollectorProbability'])>=$p))
		{
			$host = $_SERVER["SERVER_NAME"];
			$port = 80;
			$this->debug('Running Garbage Collector [host: "'.$host.'", port: "'.$port.'"] OK.');
			if ($f = fsockopen($host, $port, $errno, $errstr))
			{
				stream_set_blocking($f, 0);
				$data = "postRequest=true";
				$headers = "POST /index.php?eID=evo_nginx_boost HTTP/1.1\r\n".
							"Host: $host\r\n".
							"Content-type: application/x-www-form-urlencoded\r\n".
							"Content-length: ". strlen($data) ."\r\n".
							"Connection: Close\r\n\r\n";
				fwrite($f, $headers.$data);
				fclose($f);
				return true;
			}
		}
		return false;
	}

	static function array_php_code($array_in)
	{
		if (!is_array($array_in))
			return false;
		$p = '';
		$result = "array(";
		while (list($key,$val)=each($array_in))
		{
			$result.= $p."\n'".(string)$key."' => ";
			$result .= is_array($array_in[$key]) ? tx_evo_nginx_boost::array_php_code($array_in[$key]) : "'".(string)$val."'";
			$p = ',';
		}
		return $result."\n)";
	}

	static public function extendTypo3indexphp($extendIndex = false)
	{
		$file = str_ireplace('class.tx_evo_nginx_boost.php', 'tx_evo_nginx_boost_index_conf.php', __FILE__);
		$confArr = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['evo_nginx_boost'];
		$confArr['class.tx_evo_nginx_boost.php'] = __FILE__;
		file_put_contents($file, "<?php\n\$CONF = ".tx_evo_nginx_boost::array_php_code($confArr).";\n?>");
	}

	private function debug($msg, $type = 'INFO', $writeOutput = NULL)
	{
		if (!$this->conf['debug'] && !$this->isBeTypoUser())
			return false;
		if ($this->isBeTypoUser() || $this->conf['debugAllowedIP']=='*' || in_array((string)$_SERVER['REMOTE_ADDR'], t3lib_div::trimExplode(',', $this->conf['debugAllowedIP'])))
		{
			session_start();
			if (!is_array($GLOBALS['_debug_tx_evonginxboost']))
				$GLOBALS['_debug_tx_evonginxboost'] = array(array('START', 'debug session start at: '.date('d-m-Y H:i:s', time())));
			$GLOBALS['_debug_tx_evonginxboost'][] = array($type, $msg);
			if ($writeOutput)
			{
				echo "\n";
				foreach ($GLOBALS['_debug_tx_evonginxboost'] as $msg)
					if (!$this->conf['debugOnlyErrorsLogging'] || $msg[0]=='ERROR')
					{
						if ($this->conf['debugWriteToOutput'])
							echo "\n<!-- evonginxboost ".$msg[0].": ".$msg[1].' -->';
						if ($this->conf['debugWriteToDB'])
							$values .= (empty($values) ? '' : ',')."('".addslashes($msg[0])."','".addslashes($GLOBALS['TSFE']->fe_user->user['username'])."','".addslashes($msg[1])."','".addslashes($_SERVER['REQUEST_URI'])."','".addslashes($_SERVER['REMOTE_ADDR'])."','".addslashes(session_id())."')";
					}
				if (!empty($values))
					$GLOBALS['TYPO3_DB']->sql_query('INSERT INTO log_evonginxboost(type, user, message, url, ip, ses) VALUES '.$values);
			}
		}
		return true;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/evo_nginx_boost/class.tx_evo_nginx_boost.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/evo_nginx_boost/class.tx_evo_nginx_boost.php']);
}

?>
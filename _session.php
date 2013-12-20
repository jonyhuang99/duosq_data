<?php
//DAL:SESSION访问层，lazy原则，只用当引用到变量时才会初始化

namespace SESSION;

class _Session extends \Object {

	function init() {

		if(!isset($_SESSION)){

			switch(CAKE_SECURITY) {
				case 'high':
				$cookieLifeTime=0;
				//ini_set('session.referer_check', $this->host);
				break;
				case 'medium':
				$cookieLifeTime = WEEK;
				break;
				case 'low':
				default:
				$cookieLifeTime = YEAR;
				break;
			}

			ini_set('url_rewriter.tags', '');
			ini_set('session.save_handler', 'redis');
			ini_set('session.save_path', 'tcp://127.0.0.1:6380');
			ini_set('session.use_cookies', 1);
			ini_set('session.name', CAKE_SESSION_COOKIE);
			ini_set('session.cookie_lifetime', $cookieLifeTime);
			ini_set('session.gc_maxlifetime', $cookieLifeTime);
			ini_set('session.cookie_domain', CAKE_SESSION_DOMAIN);
			ini_set('session.gc_probability', 0);
			ini_set('session.auto_start', 0);
			ini_set('session.cookie_httponly', 1);
			session_start();
		}
	}

	function __get($key){

		if(!isset($_SESSION)){
			$this->init();
		}
		$trueKey = $this->__sessionVarNames($key);
		return eval("return @{$trueKey};");
	}

	function __set($key, $value){

		if(!isset($_SESSION)){
			$this->init();
		}
		$trueKey = $this->__sessionVarNames($key);

		eval("@{$trueKey} = \$value;");
	}

	function __sessionVarNames($key) {

		if (is_string($key)) {
			if (strpos($key, ".")) {
				$keys = explode(".", $key);
			} else {
				$keys = array($key);
			}
			$expression = '$_SESSION';
			foreach($keys as $item) {
				$expression .= is_numeric($item) ? "[$item]" : "['$item']";
			}
			return $expression;
		}
		return false;
	}

	function close(){

		if(!isset($_SESSION)){
			$this->init();
		}

		$_SESSION = array();
		session_destroy();

		setcookie(CAKE_SESSION_COOKIE, '', time()-42000, '/');
	}

}

?>
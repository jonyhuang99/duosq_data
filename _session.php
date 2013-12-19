<?php
namespace Session;
/**
 * DAL:SESSION访问层，lazy原则，只用当引用到变量时才会初始化
 */
class _Session extends \Object {

	static $sess = array();

	function init() {

		if(!self::$sess){

			switch(CAKE_SECURITY) {
				case 'high':
				$cookieLifeTime=0;
				//ini_set('session.referer_check', $this->host);
				break;
				case 'medium':
				$cookieLifeTime = 7 * 86400;
				break;
				case 'low':
				default:
				$cookieLifeTime = 365 * 86400;
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

			session_start();
			self::$sess = $_SESSION;
		}
	}

	function __get($key){

		if(!self::$sess){
			$this->init();
		}

		return self::$sess[$key];
	}

	function __set($key, $value){

		if(!self::$sess){
			$this->init();
		}

		self::$sess[$key] = $value;
	}

}

?>
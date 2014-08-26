<?php
//DAL:SESSION访问层，lazy原则，只用当引用到变量时才会初始化

namespace SESSION;

class _Session extends \Object {

	//初始化redis session
	function init() {

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
			$cookieLifeTime = MONTH;
			break;
		}

		ini_set('url_rewriter.tags', '');
		ini_set('session.save_handler', 'redis');
		ini_set('session.save_path', 'tcp://'.REDIS_SESSION);
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

	//变量获取入口
	function __get($key){

		if(!isset($_SESSION)){

			//如果没携带cookie直接弹回
			if(!isset($_COOKIE[CAKE_SESSION_COOKIE]))return;

			$this->init();
		}
		$trueKey = $this->__sessionVarNames($key);
		return eval("return @{$trueKey};");
	}

	//变量设置入口
	function __set($key, $value){

		if(!isset($_SESSION)){
			$this->init();
		}
		$trueKey = $this->__sessionVarNames($key);

		eval("@{$trueKey} = \$value;");
	}

	//包裹变量使之支持.代表数组
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

	//删除变量
	function delete($key){

		if(!isset($_SESSION)){
			$this->init();
		}
		$trueKey = $this->__sessionVarNames($key);

		eval("unset({$trueKey});");
	}

	//注销session
	function close(){

		if(!isset($_SESSION)){
			$this->init();
		}

		$_SESSION = array();
		session_destroy();

		setcookie(CAKE_SESSION_COOKIE, '', time()-42000, '/', CAKE_SESSION_DOMAIN);
	}

}

?>
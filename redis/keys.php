<?php
//各类存于redis的临时KEY管理底层

namespace REDIS;

class Keys extends _Redis {

	protected $namespace = 'keys';
	protected $dsn_type = 'database';

	/**
	 * 多多集分宝打款key，7天过期，value为空表示获取数据
	 * @param  string  $new_value 新的key值
	 * @param  integer $expire    过期时间
	 * @return [type]             array()
	 */
	function duoduo($new_value='',$expire=43200){

		if($new_value){
			return $this->set('duoduo:jfb', $new_value, $expire);
		}else{
			$value = $this->get('duoduo:jfb');
			$ttl = $this->ttl('duoduo:jfb');
			if(!$value){
				return array('value'=>'');
			}else{
				return array('value'=>$value, 'ttl'=>$ttl);
			}
		}
	}

	/**
	 * 商品分类匹配规则存取
	 * @param  string $cat    分类
	 * @param  string $subcat 子分类
	 * @param  string $rules  存入规则
	 * @return [type]         [description]
	 */
	function goodsCatRules($cat='', $subcat='', $rules='', $clean=true){

		static $cache_cat;
		static $cache_cat_all;

		if($rules){
			if(!$cat || !$subcat)return;

			$this->hset('goods:cat_rules', $cat . '_' . $subcat, trim($rules));
			if($clean)$this->goodsCatClean();
			return true;
		}else{
			if($cat && $subcat){

				if(isset($cache_cat[$cat][$subcat]))return $cache_cat[$cat][$subcat];
				$rules_o = $this->hget('goods:cat_rules', $cat . '_' . $subcat);
				$rules = array();
				if($rules_o){
					$lines = explode("\n", $rules_o);
					foreach ($lines as $line) {
						if(strpos($line, '&')){
							$line = explode('&', trim($line));
							$rules[] = $line;
						}else{
							$rules[] = trim($line);
						}
					}
				}
				$cache_cat[$cat][$subcat] = $rules;
				return $cache_cat[$cat][$subcat];
			}
			else{
				if($cache_cat_all)return $cache_cat_all;
				$rules_all_o = $this->hgetall('goods:cat_rules');
				$rules_all = array();
				if($rules_all_o){
					foreach($rules_all_o as $cat_subcat => $rules_o){
						list($cat, $subcat) = explode('_', $cat_subcat);
						$rules = array();
						if($rules_o){
							$lines = explode("\n", $rules_o);
							foreach ($lines as $line) {
								if(strpos($line, '&')){
									$line = explode('&', trim($line));
									$rules[] = $line;
								}else{
									$rules[] = trim($line);
								}
							}
						}
						$rules_all[$cat][$subcat] = $rules;
					}
				}
				$cache_cat_all = $rules_all;
				return $cache_cat_all;
			}

		}
	}

	private function goodsCatClean(){

		//清除不存在的cat/subcat
		$config_cat = D('promotion')->getCatConfig();
		$rules = array();
		foreach($config_cat as $cat => $subcats){
			foreach($subcats as $subcat){
				$rules[$cat][$subcat] = $this->goodsCatRules($cat, $subcat);
			}
		}

		$cache_rules = D('promotion')->getCatRules();
		foreach ($cache_rules as $cat => $subcats) {
			foreach($subcats as $subcat => $value){
				if(!isset($rules[$cat][$subcat])){
					$this->hdel('goods:cat_rules', $cat.'_'.$subcat);
				}
			}
		}
	}
}
?>
<?php
//DAL:推广任务访问模块
namespace DAL;

class Advtask extends _Dal {

	//判断任务是否已存在
	function existed($type, $url){

		if(!$type || !$url)return;
		return $this->db('advtask')->find(array('type'=>$type, 'url'=>$url));
	}

	//新增任务完成数据
	function add($type, $url){

		if(!$type || !$url)return;
		$user_id = D('myuser')->getID();
		if(!$user_id)return;

		$this->db('advtask')->create();
		return $this->db('advtask')->save(array('user_id'=>$user_id, 'type'=>$type, 'url'=>$url));
	}

	//探测任务是否超过速控
	function isOverSpeed($type, $task_url){

		if(!$type || !$task_url || !valid($task_url, 'url'))return true;

		$url_id = $this->_getUrlId($task_url);
		if(!$url_id)return true;

		$conf_speed = $this->_getConfSpeed($type);
		if(!$conf_speed)return true;

		$speed_limit = @$conf_speed[$url_id];
		if(!$speed_limit)return true;

		$key = "type:{$type}:{$url_id}";

		return D('speed')->advtask($key, $speed_limit, 'get');
	}

	//累计任务速控
	function incrSpeed($type, $task_url){

		if(!$type || !$task_url || !valid($task_url, 'url'))return;

		$url_id = $this->_getUrlId($task_url);
		if(!$url_id)return true;

		$conf_speed = $this->_getConfSpeed($type);

		if(!$conf_speed)return true;

		if(!$conf_speed[$url_id])return true;

		$key = "type:{$type}:{$url_id}";
		return D('speed')->advtask($key, $conf_speed[$url_id], 'set');
	}

	//从url中获取标识
	private function _getUrlId($url){

		if(!$url || !valid($url, 'url'))return;
		$url_ex = explode('.', $url);
		$url_id =  r('/', '', r('http://', '', $url_ex[0]).'.'.$url_ex[1]);
		return $url_id;
	}

	//获取指定任务类型的速控配置
	private function _getConfSpeed($type){

		$this->db('advtask');
		$conf = '';
		switch ($type) {
			case \DB\Advtask::TYPE_WENDA_ASK:
				$conf = C('task_ask');
				break;
			case \DB\Advtask::TYPE_WENDA_ANSWER:
				$conf = C('task_answer');
				break;
			case \DB\Advtask::TYPE_TIEBA:
				$conf = C('task_tieba');
				break;
			case \DB\Advtask::TYPE_BBS:
				$conf = C('task_bbs');
				break;
			case \DB\Advtask::TYPE_ZUANKE:
				$conf = C('task_zuanke');
				break;
		}

		if(!$conf)return true;

		$conf_speed = array();

		foreach($conf['site'] as $i_site){
			$i_ex = explode('|', $i_site);
			$i_limit = intval(array_pop($i_ex));
			$i_url = array_pop($i_ex);
			$i_url_id = $this->_getUrlId($i_url);
			if(!$i_url_id || !$i_limit)continue;
			$conf_speed[$i_url_id] = $i_limit;
		}

		return $conf_speed;
	}
}
?>
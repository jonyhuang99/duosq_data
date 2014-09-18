<?PHP
//APP接口底层
namespace API;

class App extends _Api {

	public $err_code = 0;

	//推送消息
	function pushMessage($device_id, $platform, $message, $notify_num, $url=''){

		if(!$device_id || !in_array($platform, array('ios', 'android')) || !$message)return;

		if($platform == 'ios'){
			I('api/push_ios');

			if(isDevelop()){
				$push_token = C('comm', 'monitor_ios');
			}else{
				$push_token = D('subscribe')->detail($device_id, $platform, 'push_token');
			}

			if(!$push_token)return true;

			$obj = new \pushIos();
			$ret = $obj->pushMessage($push_token, $message, $notify_num, $url);
			if($ret){
				//标识最后通知的未读信息数
				D('subscribe')->setAppLastNotifyNum($device_id, $platform, $notify_num);
			}

		}elseif($platform == 'android'){

			I('api/push_android');
			if(isDevelop()){
				$push_token = C('comm', 'monitor_android');
			}else{
				$push_token = D('subscribe')->detail($device_id, $platform, 'push_token');
			}
			if(!$push_token)return true;

			$obj = new \pushAndroid();
			$ret = $obj->pushMessage($push_token, $message, $url);
			$this->err_code = $obj->err_code;
		}

		return $ret;
	}

	//推送消息数
	function pushNotify($device_id, $platform, $num=0){

		if(!$device_id || !in_array($platform, array('ios', 'android')))return;

		if($platform == 'ios'){
			I('api/push_ios');

			if(isDevelop()){
				$push_token = C('comm', 'monitor_ios');
			}else{
				$push_token = D('subscribe')->detail($device_id, $platform, 'push_token');
			}

			if(!$push_token)return false;

			$obj = new \pushIos();
			return $obj->pushBadge($push_token, $num);
		}
	}
}

?>
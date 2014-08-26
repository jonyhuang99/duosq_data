<?PHP
//APP接口底层
namespace API;

class App extends _Api {

	//推送消息
	function pushMessage($device_id, $platform, $message, $notify_num, $url=''){

		if(!$device_id || !in_array($platform, array('ios', 'android')) || !$message)return;

		if($platform == 'ios'){
			I('api/ios_push');

			$push_token = D('subscribe')->detail($device_id, $platform, 'push_token');
			if(!$push_token)return false;

			$obj = new \iosPush();
			$ret = $obj->pushMessage($push_token, $message, $notify_num, $url);
			if($ret){
				//标识最后通知的未读信息数
				D('subscribe')->setAppLastNotifyNum($device_id, $platform, $notify_num);
			}

			return $ret;
		}
	}

	//推送消息数
	function pushNotify($device_id, $platform, $num=0){

		if(!$device_id || !in_array($platform, array('ios', 'android')))return;

		if($platform == 'ios'){
			I('api/ios_push');

			$push_token = D('subscribe')->detail($device_id, $platform, 'push_token');
			if(!$push_token)return false;

			$obj = new \iosPush();
			return $obj->pushBadge($push_token, $num);
		}
	}
}

?>
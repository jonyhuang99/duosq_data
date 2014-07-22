<?PHP
//内部接口访问底层
namespace API;

class Interal extends _Api {

	var $pre = 'http://api.duosq.com:8080/Interal/';

	function pay($user_id){

		if(!$user_id)return ;

		$url = $this->pre . 'pay/' . $user_id;
		$api_ret = file_get_contents($url);
		$api_return = json_decode($api_ret, true);
		return $api_return;
	}
}

?>
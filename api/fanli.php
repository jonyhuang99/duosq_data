<?PHP
//返利网接口访问底层
namespace API;

class Fanli extends _Api {

	//获取商品历史价格
	function goodsPrice($url){
		$api = 'http://www.budou.com/priceflash/flash?callback=jQuery183005654067010618746_1401779816056';
		I('curl');
		$curl = new \CURL();
		$post = array();
		$post['url'] = $url;
		$post['day'] = 91;
		//$post['newprice'] = 1780; //最新价格
		//$post['price'] = 1780; //收藏价格
		$post['datetime'] = date('Y-m-d H:i:s', time()-DAY*2);
		$ret = $curl->post($api, $post);

		if(stripos($ret, 'success')!==false){
			$ret = preg_replace('/jQuery.+\(/', '', $ret);
			$ret = trim($ret, ')');
			$ret = json_decode($ret, true);
			if($ret['data']['url']){
				$ret = parse_url($ret['data']['url']);
				$query = $ret['query'];
				$data = str_replace('data=', '', $query);
				$info = parse_url(urldecode($data));
				$query = $info['query'];
				parse_str($query, $r);
				return $r;
			}
		}

		return $ret;
	}
}

?>
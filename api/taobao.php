<?PHP
//淘宝开放平台接口访问底层
namespace API;

class Taobao extends _Api {

	var $err_code = 0;

	/**
	 * 获取淘宝商品信息
	 * @param  int  $p_id        淘宝商品信息
	 * @param  boolean $bak_channel 是否启用备用通道
	 * @return array              商品详情(p_title,p_seller,p_price,p_pic_url,channel,has_fanli)
	 */
	function getItemDetail($p_id, $bak_channel = false) {

		if(!$p_id)return;
		I('api/taobao/top/TopClient');
		I('api/taobao/top/request/TbkItemsDetailGetRequest.php');
		//实例化TopClient类
		$client = new \TopClient;

		if ($bak_channel) {
			$key = C('keys', 'taobao_api_appkey_backup');
			$client->appkey = $key['key'];
			$client->secretKey = $key['secret'];
			D('log')->action(1010, 1, array('data1'=>$client->appkey));
		} else {
			$rd = rand(1, 10);
			$keys = C('keys', 'taobao_api_appkey_main');

			if ($rd < 8) {
				$client->appkey = $keys[0]['key'];
				$client->secretKey = $keys[0]['secret'];
			} else {
				$client->appkey = $keys[1]['key'];
				$client->secretKey = $keys[1]['secret'];
			}
		}

		//$client->fanliNick = '苹果元元88';
		$client->format = 'json';
		$req = new \TbkItemsDetailGetRequest;
		$req->setFields("num_iid,seller_id,nick,title,price,volume,pic_url,item_url,shop_url");
		$req->setNumIids($p_id);
		$resp = $client->execute($req);

		// if($client->appkey == '21306056')$resp->code = 7;
		if (!@$resp->code && @$resp->tbk_items) {
			$item = $resp->tbk_items->tbk_item[0];
			$info = array();
			$info['p_title'] = $item->title;
			$info['p_seller'] = $item->nick;
			$info['p_price'] = $item->price;
			$info['p_pic_url'] = $item->pic_url;
			$info['key'] = $client->appkey;
			$info['has_fanli'] = 1;

			if (!$info['p_title']){
				D('log')->action(1000, 1, array('status'=>0, 'data1'=>$p_id));
				$info = array();
				$info['errcode'] = '0';
				$info['key'] = $client->appkey;
			}

		} else{

			if (@$resp->code) {

				D('log')->action(1001, 1, array('status'=>0, 'data1'=>$p_id, 'data2'=>$resp->code));
				if ($resp->code == 7 && !$bak_channel) {
					return $this->getItemDetail($p_id, true);
				}
				$info = array();
				$info['errcode'] = $resp->code;
				$info['key'] = $client->appkey;

			} else {

				$info = array();
				$info['has_fanli'] = 0;
				$info['key'] = $client->appkey;
			}
		}

		return $info;
	}
}
?>
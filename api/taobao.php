<?PHP
//淘宝开放平台接口访问底层
namespace API;

class Fanli extends _Api {

	function getItemDetail($p_id, $bak_channel = false) {

		$stat_obj = new StatApi();
		$cache = $stat_obj->find(array('p_id' => $p_id, 'created' => date('Y-m-d')));
		clearTableName($cache);

		if ($cache) return json_decode($cache['content'], true);
		I('api/lib_taobao/top/TopClient');
		I('api/lib_taobao/top/request/TbkItemsDetailGetRequest.php');
		//实例化TopClient类
		$client = new TopClient;

		if ($bak_channel) {
			$client->appkey = '12019508';
			$client->secretKey = '4c079fe9f7edb17e1878f789d04896cf';
			alert('taobao api', '[warning][switch][' . $client->appkey . ']');
		} else {
			$rd = rand(1, 10);

			if ($rd < 8) {
				$client->appkey = '21074255';
				$client->secretKey = 'ff2712ae1ad2f824259107b06188bcb8';
			} else {
				$client->appkey = '21306056';
				$client->secretKey = 'f0362fe1abacd41cb0f4495c63c9c0c6';
			}
		}

		//$client->fanliNick = '苹果元元88';
		$client->format = 'json';
		$req = new TbkItemsDetailGetRequest;
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
			$info['p_fanli'] = 1;
			$info['p_rate'] = 1;
			$info['channel'] = $client->appkey;

			if ($info['p_title']) $stat_obj->add(1, 'succ', $p_id, json_encode($info), $info['channel']);
			else alert('taobao api', '[error][api fatal error][' . $p_id . '][!!!!!!!!!!!]');
		} else
		if (@$resp->code) {
			//TODO alert 记录错误日志
			alert('taobao api', '[error][' . $resp->code . '][' . $p_id . ']');
			$stat_obj->add(0, $resp->code, '', '', $client->appkey);

			if ($resp->code == 7 && !$bak_channel) {
				return taobaoItemDetail($p_id, true);
			}
			$info = array();
		} else {
			//无返利
			$stat_obj->add(1, 'no_rebate', $p_id, '', $client->appkey);
			$info = array();
		}
		return $info;
	}
}
?>
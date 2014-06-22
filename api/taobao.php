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
			if($rd < 6){
				$client->appkey = $keys[0]['key'];
				$client->secretKey = $keys[0]['secret'];
			}else{
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

		} else{

			//尝试用服务器端来获取
			$info = $this->getItemDetailByServer($p_id);
			if(!$info){
				if (@$resp->code) {

					D('log')->action(1001, 1, array('status'=>0, 'data1'=>$p_id, 'data2'=>$resp->code));
					if ($resp->code == 7 && !$bak_channel) {
						return $this->getItemDetail($p_id, true);
					}
					$info = array();
					$info['errcode'] = $resp->code;
					$info['key'] = $client->appkey;

				} else {
					if(!$info){
						$info = array();
						$info['has_fanli'] = 0;
						$info['key'] = $client->appkey;
					}
				}

				//server_tbk报警
				D('alarm')->api(array('tbk.getItemDetailByServer'));
			}
		}

		return $info;
	}

	//判断商品是否支持返利
	function isRebateAuth($num_iid){

		I('api/taobao/top/TopClient');
		I('api/taobao/top/request/TaobaokeRebateAuthorizeGetRequest.php');
		//实例化TopClient类
		$client = new \TopClient;
		$client->format = 'json';
		$key = C('keys', 'taobao_api_appkey_backup');
		$client->appkey = $key['key'];
		$client->secretKey = $key['secret'];

		$req = new \TaobaokeRebateAuthorizeGetRequest;
		$req->setNumIid($num_iid);
		$resp = $client->execute($req);
		if($resp && isset($resp->rebate)){
			return $resp->rebate;
		}
		return true;
	}

	//TODO用户搜索时，使用daemon模式获取淘点金跳转链接，默认渲染到taobao跳转页面，提高兼容性
	function getItemDetailByServer($iid){

		$rf=urlencode(MY_HOMEPAGE_URL.'/go/taobao?param='.$iid.'&tc=index');
		$pid=C('keys', 'taobao_mm');

		$json=json_encode(array());
		$pgid=md5($iid);
		$url='http://g.click.taobao.com/load?rf='.$rf.'&pid='.$pid.'&pgid='.$pgid.'&cbh=261&cbw=1436&re=1440x900&cah=870&caw=1440&ccd=32&ctz=8&chl=2&cja=1&cpl=0&cmm=0&cf=10.0&cb=jsonp_callback_0049675575148'.mt_rand(10000,99999);
		$a = file_get_contents($url);
		preg_match('/jsonp_callback_\d+\(\{"code":"([0-9a-z]+)"\}\)/',$a,$b);
		if($b[1]!=''){
			$url='http://g.click.taobao.com/display?cb=jsonp_callback_03655084007659234&pid='.$pid.'&wt=0&ti=7&tl=628x100&rd=1&ct=itemid%3D'.$iid.'&st=2&rf='.$rf.'&et='.$b[1].'&pgid='.$pgid.'&v=2.0';
			$a = file_get_contents($url);
			$a=preg_replace('/jsonp_callback_\d+\(/','',$a);
			$json=preg_replace('/\)$/','',$a);
		}

		$goods = array();
		$a=json_decode($json,1);

		if(is_array($a) && isset($a['data']['items'])){
			$goods['p_price']=$a['data']['items'][0]['ds_reserve_price'];
			$promotion_price=$a['data']['items'][0]['ds_discount_price'];
			if($goods['p_price']<=$promotion_price){
				$promotion_price=0;
			}
			if($promotion_price > 0){
				$goods['p_price'] = $promotion_price;
			}
			$goods['p_title']=$a['data']['items'][0]['ds_title'];
			$goods['p_seller']=$a['data']['items'][0]['ds_nick'];
			$goods['p_pic_url']=$a['data']['items'][0]['ds_img']['src'];
			$goods['key']='tbk_server_get';
			if($a['data']['items'][0]['ds_istmall']){
				$goods['has_fanli'] = 1;
				$goods['is_tmall'] = 1;
			}
			else{
				$goods['has_fanli']=0;
			}
			//$goods['item_click_url']=$a['data']['items'][0]['ds_item_click'];
			//$goods['shop_click_url']=$a['data']['items'][0]['ds_shop_click'];
		}
		return $goods;
	}

	//访问淘宝suggest接口
	function getSuggest($keyword, $limit = 10){

		$data = file_get_contents('http://suggest.taobao.com/sug?code=utf-8&callback=jQuery'.rand(10000,99999).'&q='.urlencode($keyword));
		if(!$data)return array();

		$suggest = trimJQuery($data);
		if(!$suggest || !$suggest['result'])return array();

		$suggest = array_slice($suggest['result'], 0, 10);
		$tmp = array();
		foreach($suggest as $val){
			$tmp[] = $val[0];
		}

		return $tmp;
	}
}
?>
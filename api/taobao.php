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

		if(!$num_iid)return;
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
	function getItemDetailByServer($iid, $sp='', $goods_id=''){

		static $api_ret;
		if(isset($api_ret[$iid]))return $api_ret[$iid];
		if(!$iid)return;
		if($sp && $goods_id){
			$rf=MY_WWW_URL.'/item-'.$sp.'-'.$goods_id;
		}else{
			$rf=urlencode(MY_WWW_URL.'/go/taobao?param='.$iid.'&tc=index');
		}

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
			$goods['p_price_avg']=$a['data']['items'][0]['ds_reserve_price'];
			$goods['p_title']=$a['data']['items'][0]['ds_title'];
			$goods['p_seller']=$a['data']['items'][0]['ds_nick'];
			$goods['p_pic_url']=$a['data']['items'][0]['ds_img']['src'];
			$goods['key']='tbk_server_get';
			if($a['data']['items'][0]['ds_istmall']){
				$goods['has_fanli'] = 1;
				$goods['is_tmall'] = 1;
			}
			else{
				$goods['has_fanli']=$a['data']['items'][0]['ds_taoke'];
			}
			$goods['item_click_url']=$a['data']['items'][0]['ds_item_click'];
			$goods['shop_click_url']=$a['data']['items'][0]['ds_shop_click'];
			$goods['shop_id']=$a['data']['items'][0]['ds_user_id'];
		}

		$api_ret[$iid] = $goods;
		return $goods;
	}

	//访问淘宝suggest接口
	function getSuggest($keyword, $limit = 10){

		if(!$keyword)return;
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

	//搜索淘宝店铺
	function searchShop($keyword){

		if(!$keyword)return;
		I('api/taobao/top/TopClient');
		I('api/taobao/top/request/TbkShopsGetRequest.php');
		//实例化TopClient类
		$client = new \TopClient;
		$client->format = 'json';
		$keys = C('keys', 'taobao_api_appkey_main');
		$client->appkey = $keys[0]['key'];
		$client->secretKey = $keys[0]['secret'];

		$req = new \TbkShopsGetRequest;
		$req->setKeyword($keyword);
		$req->setFields('user_id,seller_nick,shop_title,pic_url,shop_url');
		$resp = $client->execute($req);
		if($resp && isset($resp->tbk_shops)){
			var_dump($resp->tbk_shops);
		}
		return;
	}

	//获取商品全部详情
	function getItemAllDetail($num_iid=''){

		if(!$num_iid)return;
		$server_detail = $this->getItemDetailByServer($num_iid);
		if(!$server_detail)return;

		$server_detail['intro']='';
		$server_detail['comment']='';

		I('curl');
		$curl = new \CURL;
		//读取商品详情
		$goods_detail = $curl->get("http://hws.m.taobao.com/cache/mtop.wdetail.getItemDescx/4.1/?data=%7B%22item_num_id%22%3A%22{$num_iid}%22%7D", "http://h5.m.taobao.com/awp/core/detail.htm?id={$num_iid}");
		if($goods_detail){
			$goods_detail = json_decode($goods_detail, true);
			if($goods_detail['data']['images']){
				$server_detail['intro'] = serialize(array('images'=>$goods_detail['data']['images']));
			}
		}

		//读取商品评论
		$goods_comment_detail = $curl->get("http://rate.taobao.com/feedRateList.htm?callback=jsonp_reviews_list&userNumId={$server_detail['shop_id']}&auctionNumId={$num_iid}&siteID=7&currentPageNum=1&rateType=1&orderType=sort_weight&showContent=1&attribute=", "http://h5.m.taobao.com/awp/core/detail.htm?id={$num_iid}");

		if($goods_comment_detail){
			$goods_comment_detail = \g2u($goods_comment_detail, false);
			$goods_comment_detail = r('jsonp_reviews_list(', '', $goods_comment_detail);
			$goods_comment_detail = trim(trim($goods_comment_detail), ')');
			$goods_comment_detail = json_decode($goods_comment_detail, true);
			if($goods_comment_detail['comments']){
				foreach ($goods_comment_detail['comments'] as $comment) {
					$date = r(array('年','月','日'), '-', $comment['date']);
					$tmp[strtotime($date).rand(10,99)] = array('date'=>$date, 'content'=>$comment['content'], 'photos'=>$comment['photos'], 'user'=>$comment['user']['nick']);
				}
				krsort($tmp);
				if($tmp)$server_detail['comment'] = serialize(array_values($tmp));
			}
		}
		return $server_detail;
	}


	/*
	Array
	(
	    [cid] => 50019691
	    [istk] => true
	    [location] => Array
	        (
	            [city] => 重庆
	            [state] => 重庆
	        )

	    [mall] => true
	    [nick] => 邱旺食品专营店
	    [num] => 11047
	    [open_auction_iid] => AAH3SpHGABsL3tZwP3-lDh-Q
	    [open_id] => 40382614257
	    [pic_url] => http://img04.taobaocdn.com/bao/uploaded/i4/TB1WdLqFVXXXXcXXXXXXXXXXXXX_!!2-item_pic.png
	    [post_fee] => 0.00
	    [price] => 10.80
	    [price_end_time] => 1439082789000
	    [price_start_time] => 1406941989000
	    [price_wap] => 10.80
	    [price_wap_end_time] => 1439082789000
	    [price_wap_start_time] => 1406941989000
	    [promoted_service] => 2,4
	    [reserve_price] => 19.80
	    [title] => 渝美人豆干散装豆干500g重庆特产豆腐干独立小包装 零食品好巴适
	    [tk_rate] => 250
	)
	 */
	//使用百川接口获取商品详情，用于判断商品下架
	function getItemTaeDetail($iid){

		if(!$iid)return;
		$key = 'taobao:tae:item:detail:'.$iid;
		$cache = D('cache')->get($key);
		if($cache)return D('cache')->ret($cache);

		I('api/taobao/top/TopClient');
		I('api/taobao/top/request/TaeItemConvertRequest.php');

		//应用APP的appkey和appsecret
		$appkey="23057292";
		$appsecret="1aac28735976d1797259f9b828ba6813";
		//实例化TopClient类，注意，这里的new TopClient没有括号()。
		$topC = new \TopClient;
		$topC->appkey = $appkey;
		$topC->secretKey = $appsecret;

		$req = new \TaeItemConvertRequest;
		$req->setFields("num,title,price,promoted_service,nick,location,post_fee,cid,pic_url");
		$req->setNumIid($iid);

		//执行API请求并打印结果
		$resp = $topC->execute($req);
		if(!@$resp->code && @$resp->items){
			$item_info = object2array($resp->items->x_item[0]);
			D('cache')->set($key, $item_info, DAY, true);
			return $item_info;
		}
	}

	//商品是否淘客
	function isTbk($iid){
		$info = $this->getItemTaeDetail($iid);
		if($info && $info['istk']=='true'){
			return true;
		}
	}
}
?>
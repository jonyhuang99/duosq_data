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

	//TODO用户搜索时，使用daemon模式获取淘点金跳转链接，默认渲染到taobao跳转页面，提高兼容性
	function getTdjInfo($iid){
		$rf=urlencode(u(MOD,ACT,array('iid'=>$iid)));
		if(!preg_match('/^http/',$rf)){
			$rf=urlencode(SITEURL.'/').$rf;
		}
		$pid=$this->ApiConfig->taodianjin_pid;

		$md5_cache_path=md5($iid.$pid);
		$md5_cache_path=substr($md5_cache_path,0,2).'/'.$md5_cache_path.'.json';
		$cache_path=DDROOT.'/data/temp/taoapi/taobao.taobaoke.tdj.get/'.$md5_cache_path;

		if(file_exists($cache_path) && $this->ApiConfig->Cache>0){
			$json=file_get_contents($cache_path);
			$is_cache=1;
		}
		else{
			$json=dd_json_encode(array());
			$pgid=md5($iid);
			$url='http://g.click.taobao.com/load?rf='.$rf.'&pid='.$pid.'&pgid='.$pgid.'&cbh=261&cbw=1436&re=1440x900&cah=870&caw=1440&ccd=32&ctz=8&chl=2&cja=1&cpl=0&cmm=0&cf=10.0&cb=jsonp_callback_004967557514815568';
			$a = dd_get($url);
			preg_match('/jsonp_callback_\d+\(\{"code":"(\d+)"\}\)/',$a,$b);
			if($b[1]!=''){
				$url='http://g.click.taobao.com/display?cb=jsonp_callback_03655084007659234&pid='.$pid.'&wt=0&ti=7&tl=628x100&rd=1&ct=itemid%3D'.$iid.'&st=2&rf='.$rf.'&et='.$b[1].'&pgid='.$pgid.'&v=2.0';
				$a = dd_get($url);
				$a=preg_replace('/jsonp_callback_\d+\(/','',$a);
				$json=preg_replace('/\)$/','',$a);
			}
			$is_cache=0;
		}

		$a=dd_json_decode($json,1);
		if(is_array($a)){
			$goods['price']=$a['data']['items'][0]['ds_reserve_price'];
			$goods['promotion_price']=$a['data']['items'][0]['ds_discount_price'];
			if($goods['price']<=$goods['promotion_price']){
				$goods['promotion_price']=0;
			}
			$goods['click_url']=$a['data']['items'][0]['ds_item_click'];
			$goods['shop_click_url']=$a['data']['items'][0]['ds_shop_click'];
			if($this->ApiConfig->Cache>0 && $is_cache==1){
				create_file($cache_path,$json);
			}
			return $goods;
		}
		else{
			return array();
		}
	}
}
?>
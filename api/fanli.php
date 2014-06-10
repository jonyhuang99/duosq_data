<?PHP
//返利网接口访问底层
namespace API;

class Fanli extends _Api {

	//获取商品历史价格
	function goodsPrice($url){

		if(!$url)return;

		$key = 'api:fanli:goodsPrice:day:'.date('d').':'.md5($url);
		$cache = D('cache')->get($key);
		if($cache)return D('cache')->ret($cache);

		$api = 'http://www.budou.com/priceflash/flash?callback=jQuery183005654067010618746_1401779'.rand(100000, 999999);
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
			$ret = $this->trimJQuery($ret);
			if($ret['data']['url']){
				$ret = parse_url($ret['data']['url']);
				$query = $ret['query'];
				$data = str_replace('data=', '', $query);
				$info = parse_url(urldecode($data));
				$query = $info['query'];
				parse_str($query, $result);

				D('cache')->set($key, $result, WEEK);
				return $result;
			}
		}else{
			//TODO 出错报警
		}
		return $ret;
	}

	//获取商品详情
	function goodsDetail($url, &$on_sale=''){

		if(!$url)return;

		$key = 'api:fanli:goodsDetail:day:'.date('d').':'.md5($url);
		$cache = D('cache')->get($key);
		if($cache)return D('cache')->ret($cache);
		$api = 'http://www.budou.com/index.php?m=Ajax&a=checkurl&canginput=1&code=F913422A-3853-245F-9BFF-D27E30'.rand(100000,999999).'&r=0.901604669'.rand(1000,9999).'&url='.urlencode($url).'&callback=jQuery18309776429124176502_140204'.rand(100000, 999999);

		I('curl');
		$curl = new \CURL();
		$ret = $curl->post($api, array());

		$detail = array();
		if(stripos($ret, 'success')!==false){
			$ret = $this->trimJQuery($ret);
			I('html_dom');
			$html = new \simple_html_dom();
			$html->load($ret['data']);
			$dom = $html->find('input[name=cang_topic_title]',0);
			$detail['name'] = $dom->value;

			$dom = $html->find('input[name=source_link_pic]',0);
			$detail['pic_url'] = $dom->value;

			$dom = $html->find('span[id=J_fc_price_span]',0);
			$detail['price_now'] = $dom->innertext;

			if($detail['name'] && $detail['pic_url'] && $detail['price_now'] > 0){
				D('cache')->set($key, $detail, WEEK);

				//TODO 标识商品状态正常
				return $detail;
			}else{
				//该商品无详情|已下架，缓存一天
				D('cache')->set($key, '', DAY, true);
				$on_sale = false;
			}
		}else{
			//TODO 出错报警
		}
	}

	//格式化JQuery
	private function trimJQuery($json){

		$ret = preg_replace('/jQuery[0-9\_]+\(/', '', $json);
		$ret = trim($ret, ')');
		$ret = json_decode($ret, true);
		return $ret;
	}
}

?>
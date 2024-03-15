<?php
/*
* SPEEDYCACHE
* https://speedycache.com/
* (c) SpeedyCache Team
*/

namespace SpeedyCache;

if(!defined('SPEEDYCACHE_VERSION')){
	die('HACKING ATTEMPT!');
}

class CDN{

	static function check_url(){

		if(!current_user_can('manage_options')){
			wp_die('Must be admin');
		}

		if(speedycache_optget('type') === 'cloudflare'){
			self::cloudflare_change_settings();
		}
		
		$url = speedycache_optget('url');

		if(preg_match('/wp\.com/', $url) || $url === 'random'){
			wp_send_json(array('success' => true));
		}

		$host = str_replace('www.', '', sanitize_text_field($_SERVER['HTTP_HOST']));
		$url = esc_url($url);

		if(!preg_match('/^http/', $url)){
			$url = 'http://' . $url;
		}

		if(preg_match('/^https/i', site_url()) && preg_match('/^https/i', home_url())){
			$url = preg_replace('/http\:\/\//i', 'https://', $url);
		}

		$res = wp_remote_get($url, array('timeout' => 20, 'user-agent' => "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.11; rv:64.0) Gecko/20100101 Firefox/64.0"));

		if(empty($res) || is_wp_error($res)){
			$res = array('success' => false, 'error_message' => $res->get_error_message());

			if($res->get_error_code() !== 'http_request_failed'){
				wp_send_json($res);
			}
			
			if($res->get_error_message() == 'Failure when receiving data from the peer'){
				$res = array('success' => true);
			} else if(preg_match("/cURL\serror\s60/i", $res->get_error_message())){
				//cURL error 60: SSL: no alternative certificate subject name matches target host name
				$res = array('success' => false, 'error_message' => 'cURL error 60: SSL: no alternative certificate subject name matches target host name');
			} else if(preg_match("/cURL\serror\s6/i", $res->get_error_message())){
				//cURL error 6: Couldn't resolve host
				if(preg_match('/' . preg_quote($host, '/') . '/i', $url)){
					$res = array('success' => true);
				}
			}
			
			wp_send_json($res);
		}

		$res_code = wp_remote_retrieve_response_code($res);
		
		if($res_code == 200){
			$res = array('success' => true);
			
			wp_send_json($res);
		}
		
		if(method_exists($res, 'get_error_message')){
			$res = array('success' => false, 'error_message' => $res->get_error_message());
		} else {
			$res = array('success' => false, 'error_message' => wp_remote_retrieve_response_message($res));
		}

		$header = wp_remote_retrieve_headers($res);
		if(isset($header['server']) && preg_match('/squid/i', $header['server'])){
			$res = array('success' => true);
		}

		if(($res_code == 401) && (preg_match("/res\.cloudinary\.com/i", $url))){
			$res = array('success' => true);
		}

		if(($res_code == 403) && (preg_match("/stackpathdns\.com/i", $url))){
			$res = array('success' => true);
		}

		if(($res_code == 403) && (preg_match("/cloudfront\.net/i", $url))){
			$res = array('success' => false, 'error_message' => '403: AWS Cloudfront access denied');
		}

		wp_send_json($res);
	}

	static function options(){
		global $speedycache;
		
		if(!current_user_can('manage_options')){
			wp_die('Must be admin');
		}
		
		// Output
		if(!empty($speedycache->settings['cdn'])){
			wp_send_json($speedycache->settings['cdn']);
		}
		
		wp_send_json(array('success' => false));
	}

	static function remove(){
		global $speedycache;
		
		if(!current_user_can('manage_options')){
			wp_die('Must be admin');
		}
		
		$cdn_values = $speedycache->settings['cdn'];

		if(empty($cdn_values)){
			wp_send_json(array('success' => true));
		}

		$cdn_values_arr = array();

		if(is_array($cdn_values)){
			$cdn_values_arr = $cdn_values;
		} else {
			array_push($cdn_values_arr, $cdn_values);
		}

		foreach($cdn_values_arr as $cdn_key => $cdn_value){
			if($cdn_value['id'] == 'amazonaws' || $cdn_value['id'] == 'keycdn' || $cdn_value['id'] == 'cdn77'){
				$cdn_value['id'] = 'other';
			}

			if($cdn_value['id'] == speedycache_optpost('id')){
				unset($cdn_values_arr[$cdn_key]);
			}
		}

		$cdn_values_arr = array_values($cdn_values_arr);

		if(empty($cdn_values_arr)){
			delete_option('speedycache_cdn');
			$speedycache->settings['cdn'] = array();
			
			wp_send_json(array('success' => true));
		}
		
		update_option('speedycache_cdn', $cdn_values_arr);
		$speedycache->settings['cdn'] = $cdn_values_arr;
		
		wp_send_json(array('success' => true));
	}

	static function start(){
		global $speedycache;

		if(!current_user_can('manage_options')){
			wp_die('Must be admin');
		}
		
		$cdn_values = $speedycache->settings['cdn'];

		if(empty($cdn_values)){
			wp_send_json(array('success' => false, 'message' => __('No CDN found', 'speedycache')));
		}

		$cdn_values_arr = array();

		if(is_array($cdn_values)){
			$cdn_values_arr = $cdn_values;
		}else{
			$cdn_values_arr[$cdn_values['id']] = $cdn_values;
		}

		foreach($cdn_values_arr as $cdn_key => $cdn_value){
			if($cdn_key == 'amazonaws' || $cdn_key == 'keycdn' || $cdn_key == 'cdn77'){
				$cdn_key = 'other';
				$cdn_value['id'] = 'other';
			}

			if($cdn_key == speedycache_optpost('id')){
				unset($cdn_values_arr[$cdn_key]['status']);
			}
		}

		//$cdn_values_arr = array_values($cdn_values_arr);
		update_option('speedycache_cdn', $cdn_values_arr);
		$speedycache->settings['cdn'] = $cdn_values_arr;

		wp_send_json(array('success' => true));
		
	}

	static function pause(){
		global $speedycache;
		
		if(!current_user_can('manage_options')){
			wp_die('Must be admin');
		}
		
		$cdn_values = $speedycache->settings['cdn'];

		if(empty($cdn_values)){
			wp_send_json(array('success' => true, 'message' => __('No CDN found to pause', 'speedycache')));
		}
		
		$cdn_values_arr = array();

		if(is_array($cdn_values)){
			$cdn_values_arr = $cdn_values;
		}else{
			$cdn_values_arr[$cdn_values['id']] = $cdn_values;
		}

		foreach($cdn_values_arr as $cdn_key => $cdn_value){
			if($cdn_value['id'] == 'amazonaws' || $cdn_value['id'] == 'keycdn' || $cdn_value['id'] == 'cdn77'){
				$cdn_value['id'] = 'other';
				$cdn_key = 'other';
			}

			if($cdn_key == speedycache_optpost('id')){
				$cdn_values_arr[$cdn_key]['status'] = 'pause';
			}
		}

		//$cdn_values_arr = array_values($cdn_values_arr);
		update_option('speedycache_cdn', $cdn_values_arr);
		$speedycache->settings['cdn'] = $cdn_values_arr;

		wp_send_json(array('success' => true));
	}

	static function save(){
		global $speedycache;

		if(!current_user_can('manage_options')){
			wp_die('Must be admin');
		}

		if(empty(speedycache_optpost('values'))){
			wp_send_json(array('success' => true, 'message' => __('No data passed to save', 'speedycache')));
		}
		
		$post_values = speedycache_optpost('values');

		if(empty($speedycache->settings['cdn'])){
			$cdn = array();
			$cdn[$post_values['id']] = $post_values;
			if($post_values['id'] == 'bunny'){
				$bunny_pull_id = self::bunny_get_pull_id($cdn[$post_values['id']]);
				
				if(!empty($bunny_pull_id) && !is_array($bunny_pull_id)){
					$cdn[$post_values['id']]['bunny_pull_id'] = $bunny_pull_id;
					
				}
			}

			update_option('speedycache_cdn', $cdn, null, 'yes');
			$speedycache->settings['cdn'] = $cdn;
			
			wp_send_json(array('success' => true));
		}

		$cdn_exist = false;

		if(is_array($speedycache->settings['cdn'])){
			foreach($speedycache->settings['cdn'] as $cdn_key => &$cdn_value){
				if(!empty($post_values['id']) && $cdn_key == $post_values['id']){
					$cdn_value = $post_values;
					$cdn_exist = true;
					break;
				}
			}

			if(empty($cdn_exist)){
				$speedycache->settings['cdn'][$post_values['id']] = $post_values;
			}

			update_option('speedycache_cdn', $speedycache->settings['cdn']);
			wp_send_json(array('success' => true));
			
		}

		$tmp_arr = array();

		if($speedycache->settings['cdn']['id'] == $post_values['id']){
			$tmp_arr[$post_values['id']] = $post_values;
		}else{
			array_push($tmp_arr, $speedycache->settings['cdn']);
			$tmp_arr[$post_values['id']] =  $post_values;
		}
		
		update_option('speedycache_cdn', $tmp_arr);
		$speedycache->settings['cdn'] = $tmp_arr;
		
		wp_send_json(array('success' => true));

	}

	// Get unique pull ID to purge cache on CDN 
	static function bunny_get_pull_id(&$cdn){
		global $speedycache;

		$pull_zone = $cdn['cdn_url']; // bunny cdn calls it cdn url as pull zone
		$access_key = $cdn['bunny_access_key'];
		
		if(empty($access_key)){
			return array('success' => false, 'message' => __('Bunny CDN Access Key not found', 'speedycache'));
		}
		
		$options = array(
			'headers' => array(
				'AccessKey' => $access_key,
				'accept' => 'application/json'
			)
		);

		$res = wp_remote_get('https://api.bunny.net/pullzone', $options);

		if(is_wp_error($res) || empty($res)){
			if(empty($res)){
				return array('success' => false, 'message' => __('Bunny CDN retuned an empty response', 'speedycache'));
			}
			
			return array('success' => false, 'message' => 'Something Went Wrong: ' . $res->get_error_message());
		}
		
		$res_code = wp_remote_retrieve_response_code($res);
		
		if(substr($res_code, 0, 1) != 2){
			return array('success' => false, 'message' => __('Something Went Wrong: Getting Pull ID was unsuccessful ', 'speedycache') . $res_code);
		}
		
		$res_body = wp_remote_retrieve_body($res);
		
		if(empty($res_body)){
			return array('success' => false, 'message' => __('Bunny CDN pull ID response body is empty', 'speedycache'));
		}

		$res_body = json_decode($res_body, true);
		
		foreach($res_body as $pull_zones){
			if($pull_zones['OriginUrl'] == $cdn['origin_url']){
				return $pull_zones['Id'];
			}
		}
		
		return array('success' => false, 'message' => __('Bunny Pull Zone not found', 'speedycache'));
	}

	static function replace_urls($matches){
		global $speedycache;

		if(count($speedycache->settings['cdn']) < 1){
			return $matches[0];
		}
		foreach($speedycache->settings['cdn'] as $key => $cdn){
			
			if(isset($cdn['status']) && $cdn['status'] == 'pause'){
				continue;
			}
			
			if($cdn['id'] == 'cloudflare'){
				continue;
			}

			if(preg_match('/manifest\.json\.php/i', $matches[0])){
				return $matches[0];
			}

			// https://site.com?brizy_media=AttachmentName.jpg&brizy_crop=CropSizes&brizy_post=TheCurrentPost
			if(preg_match('/brizy_media\=/i', $matches[0])){
				return $matches[0];
			}

			// https://cdn.shortpixel.ai/client/q_glossy,ret_img,w_736/http://speedycache.com/stories.png
			if(preg_match('/cdn\.shortpixel\.ai\/client/i', $matches[0])){
				return $matches[0];
			}

			// https://i0.wp.com/i0.wp.com/speedycache.com/stories.png
			if(preg_match('/i\d\.wp\.com/i', $matches[0])){
				return $matches[0];
			}

			if(preg_match('/^\/\/random/', $cdn['cdn_url']) || preg_match('/\/\/i\d\.wp\.com/', $cdn['cdn_url'])){
				if(preg_match("/^\/\/random/", $cdn['cdn_url'])){
					$cdn_url = '//i'.rand(0,3).'.wp.com/'.str_replace('www.', '', sanitize_text_field($_SERVER['HTTP_HOST']));
					$cdn_url = preg_replace('/\/\/i\d\.wp\.com/', '//i'.rand(0,3).'.wp.com', $cdn_url);
				}else{
					$cdn_url = $cdn['cdn_url'];
				}

				//to add www. if exists
				if(preg_match('/\/\/www\./', $matches[0])){
					$cdn_url = preg_replace("/(\/\/i\d\.wp\.com\/)(www\.)?/", "$1www.", $cdn_url);
				}
			}else{
				$cdn_url = $cdn['cdn_url'];
			}
			
			$cdn['file_types'] = str_replace(',', '|', $cdn['file_types']);

			if(preg_match("/\.(".$cdn['file_types'].")(\"|\'|\?|\)|\s|\&quot\;)/i", $matches[0])){
				//nothing
			}else{
				if(preg_match('/js/', $cdn['file_types'])){
					if(!preg_match('/\/revslider\/public\/assets\/js/', $matches[0])){
						continue;
					}
				}else{
					continue;
				}
			}

			if(!empty($cdn['keywords'])){
				$cdn['keywords'] = str_replace(',', '|', $cdn['keywords']);

				if(!preg_match('/'.preg_quote($cdn['keywords'], '/').'/i', $matches[0])){
					continue;
				}
			}

			if(!empty($cdn['excludekeywords'])){
				$cdn['excludekeywords'] = str_replace(',', '|', $cdn['excludekeywords']);

				if(preg_match('/'.preg_quote($cdn['excludekeywords'], '/').'/i', $matches[0])){
					continue;
				}
			}

			if(preg_match("/(data-product_variations|data-siteorigin-parallax)\=[\"\'][^\"\']+[\"\']/i", $matches[0])){
				$cdn_url = preg_replace("/(https?\:)?(\/\/)(www\.)?/", '', $cdn_url);
				
				$matches[0] = preg_replace("/(quot\;|\s)(https?\:)?(\\\\\/\\\\\/|\/\/)(www\.)?".$cdn['origin_url'].'/i', '${1}${2}${3}'.$cdn_url, $matches[0]);

			}else if(preg_match("/\{\"concatemoji\"\:\"[^\"]+\"\}/i", $matches[0])){
				$matches[0] = preg_replace("/(http(s?)\:)?".preg_quote("\/\/", '/')."(www\.)?/i", '', $matches[0]);
				$matches[0] = preg_replace("/".preg_quote($cdn['origin_url'], "/").'/i', $cdn_url, $matches[0]);

			}else if(isset($matches[2]) && preg_match('/'.preg_quote($cdn['origin_url'], '/').'/', $matches[2])){
				$matches[0] = preg_replace("/(http(s?)\:)?\/\/(www\.)?".preg_quote($cdn['origin_url'], '/').'/i', $cdn_url, $matches[0]);

			}else if(isset($matches[2]) && preg_match("/^(\/?)(".WPINC."|".SPEEDYCACHE_WP_CONTENT_DIR.")/", $matches[2])){
				$matches[0] = preg_replace("/(\/?)(".WPINC."|".SPEEDYCACHE_WP_CONTENT_DIR.")/i", $cdn_url."/"."$2", $matches[0]);

			}else if(preg_match("/[\"\']https?\:\\\\\/\\\\\/[^\"\']+[\"\']/i", $matches[0])){

				if(preg_match("/^(logo|url|image)$/i", $matches[1])){
					//If the url is called with "//", it causes an error on https://search.google.com/structured-data/testing-tool/u/0/
					//<script type="application/ld+json">"logo":{"@type":"ImageObject","url":"\/\/cdn.site.com\/image.png"}</script>
					//<script type="application/ld+json">{"logo":"\/\/cdn.site.com\/image.png"}</script>
					//<script type="application/ld+json">{"image":"\/\/cdn.site.com\/image.jpg"}</script>
				}else{
					//<script>var loaderRandomImages=["https:\/\/www.site.com\/site-data\/uploads\/2016\/12\/image.jpg"];</script>
					$matches[0] = preg_replace("/\\\\\//", '/', $matches[0]);

					if(preg_match('/'.preg_quote($cdn['origin_url'], '/').'/', $matches[0])){
						$matches[0] = preg_replace("/(http(s?)\:)?\/\/(www\.)?".preg_quote($cdn['origin_url'], '/').'/i', $cdn_url, $matches[0]);
						$matches[0] = preg_replace("/\//", "\/", $matches[0]);
					}
				}
			}
		}
		
		return $matches[0];
	}

	static function purge($email = false, $key = false, $zoneid = false){
		global $speedycache;
		
		if(empty($speedycache->settings['cdn'])){
			return;
		}
		
		$current_cdn = array(); // initializing

		foreach($speedycache->settings['cdn'] as $cdn){
			if(empty($cdn['status'])){
				$current_cdn = $cdn;
				break;
			}
		}
		
		if(empty($current_cdn['id'])){
			return;
		}

		switch($current_cdn['id']){
			case 'bunny':
				self::bunny_purge($cdn);
				break;
				
			case 'cloudflare':
				self::cloudflare_purge($email, $key, $zoneid);
				break;
		}

	}

	static function bunny_purge($cdn){

		$pull_zone = $cdn['cdn_url']; // bunny cdn calls it cdn url as pull zone
		$origin_url = $cdn['origin_url'];
		$access_key = $cdn['bunny_access_key'];
		$pull_id = !empty($cdn['bunny_pull_id']) ? $cdn['bunny_pull_id'] : '';

		if(empty($access_key) || empty($pull_id)){
			return false;
		}

		$options = array(
			'headers' => array(
				'AccessKey' => $access_key,
				'content-type' => 'application/json'
			)
		);

		$res = wp_remote_post('https://api.bunny.net/pullzone/'.$pull_id.'/purgeCache', $options);
		
		if(is_wp_error($res) || empty($res)){
			if(empty($res)){
				return __('Bunny CDN retuned an empty response', 'speedycache');
			}
			
			return 'Something Went Wrong: ' . $res->get_error_message();
		}

		$res_code = wp_remote_retrieve_response_code($res);
		
		if($res_code != 204){
			return __('Something Went Wrong: Purge was unsuccessful with response code of ') . $res_code;
		}

		return __('Success: Bunny CDN purged successfully', 'speedycache');

	}

	static function cloudflare_purge($email = false, $key = false, $zoneid = false){
		global $speedycache;
		
		if(isset($speedycache->settings['cloudflare_purge_cache_executed'])){
			return;
		}

		$cdn_values = $speedycache->settings['cdn'];

		if(empty($key) && empty($zoneid) && !empty($cdn_values)){
			foreach($cdn_values as $key => $value){
				if($value['id'] === 'cloudflare'){
					$email = $value['cdn_url'];
					$key = $value['origin_url'];
					break;
				}
			}

			if(!empty($key)){
				$zone = self::cloudflare_zone_id($email, $key, false);

				if(!empty($zone['success'])){
					$zone_id = $zone['zoneid'];
				}
			}
		}

		if(!empty($key) && !empty($zoneid)){
			$header = array(
				'method' => 'DELETE',
				'headers' => self::cloudflare_generate_header($email, $key),
				'body' => '{"purge_everything":true}'
			);

			$res = wp_remote_request('https://api.cloudflare.com/client/v4/zones/' . $zoneid . '/purge_cache', $header);

			if(empty($res) || is_wp_error($res)){
				return array('success' => false, 'error_message' => __('Unable to disable rocket loader option', 'speedycache'));
			}

			$body = json_decode(wp_remote_retrieve_body($res));

			if(empty($body->success)){
				self::cloudflare_unset_zone_id();
			} else {
				$speedycache->settings['cloudflare_purge_cache_executed'] = true;
			}
		}
	}

	static function cloudflare_generate_header($email, $key){
		
		if('speedycache' === $email){
			$header = array(
				'Authorization' => 'Bearer ' . $key,
				'Content-Type' => 'application/json'
			);
			
			return $header;
		}
		
		$header = array(
			'X-Auth-Email' => $email,
			'X-Auth-Key' => $key,
			'Content-Type' => 'application/json'
		);

		return $header;
	}

	static function cloudflare_disable_rocket_loader($email = false, $key = false, $zoneid = false){

		if(empty($key) || empty($zoneid)){
			wp_die('bad request');
		}

		$header = array(
			'method' => 'PATCH',
			'timeout' => 10,
			'headers' => self::cloudflare_generate_header($email, $key),
			'body' => '{"value":"off"}'
		);

		$res = wp_remote_request('https://api.cloudflare.com/client/v4/zones/' . $zoneid . '/settings/rocket_loader', $header);

		if(empty($res) || is_wp_error($res)){
			return array('success' => false, 'error_message' => __('Unable to disable rocket loader option', 'speedycache'));
		}
		
		$res_content = json_decode(wp_remote_retrieve_body($res));

		if(!empty($res_content->success)){
			return array('success' => true);
		}
		
		if(!empty($res_content->errors) && !empty($res_content->errors[0])){
			return array('success' => false, 'error_message' => $res_content->errors[0]->message);
		}

		return array('success' => false, 'error_message' => __('Unknown error', 'speedycache'));

	}

	static function cloudflare_set_browser_caching($email = false, $key = false, $zoneid = false){

		if(empty($key) || empty($zoneid)){
			wp_die('bad request');
		}

		$header = array(
			'method' => 'PATCH',
			'timeout' => 10,
			'headers' => self::cloudflare_generate_header($email, $key),
			'body' => '{"value":16070400}'
		);

		$res = wp_remote_request('https://api.cloudflare.com/client/v4/zones/' . $zoneid . '/settings/browser_cache_ttl', $header);

		if(empty($res) || is_wp_error($res)){
			return array('success' => false, 'error_message' => __('Unable to set the browser caching option', 'speedycache'));
		}
		
		$body = json_decode(wp_remote_retrieve_body($res));

		if(!empty($body->success)){
			return array('success' => true);
		}
		
		if(!empty($body->errors) && !empty($body->errors[0])){
			return array('success' => false, 'error_message' => $body->errors[0]->message);
		}
		
		return array('success' => false, 'error_message' => __('Unknown error', 'speedycache'));
		
	}

	static function cloudflare_disable_minify($email = false, $key = false, $zoneid = false){

		if(empty($key) || empty($zoneid)){
			wp_die('bad request');
		}
		
		$header = array(
			'method' => 'PATCH',
			'timeout' => 10,
			'headers' => self::cloudflare_generate_header($email, $key),
			'body' => '{"value":{"css":"off","html":"off","js":"off"}}'
		);

		$res = wp_remote_request('https://api.cloudflare.com/client/v4/zones/' . $zoneid . '/settings/minify', $header);

		if(empty($res) || is_wp_error($res)){
			return array('success' => false, 'error_message' => __('Unable to disable minify options', 'speedycache'));
		}
		
		$body = json_decode(wp_remote_retrieve_body($res));

		if(!empty($body->success)){
			return array('success' => true);
		}
		
		if(!empty($body->errors) && !empty($body->errors[0])){
			return array('success' => false, 'error_message' => $body->errors[0]->message);
		}
		
		return array('success' => false, 'error_message' => __('Unknown error', 'speedycache'));
	}

	static function cloudflare_zone_id($email = false, $key = false){

		$cache_zone_id = self::cloudflare_zone_id_value();

		if(!empty($cache_zone_id)){
			return $cache_zone_id;
		}

		$hostname = preg_replace("/^(https?\:\/\/)?(www\d*\.)?/", '', sanitize_text_field($_SERVER['HTTP_HOST']));

		if(function_exists('idn_to_utf8')){
			$hostname = idn_to_utf8($hostname);
		}

		$header = array(
			'method' => 'GET',
			'headers' => self::cloudflare_generate_header($email, $key)
		);

		/*
		status=active has been removed because status may be "pending"
		*/
		$res = wp_remote_request('https://api.cloudflare.com/client/v4/zones/?page=1&per_page=1000', $header);
		if(empty($res) || is_wp_error($res)){
			$res = array('success' => false, 'error_message' => $res->get_error_message());
			
			return $res;
		}

		$zone = json_decode(wp_remote_retrieve_body($res));
		
		if(isset($zone->errors) && isset($zone->errors[0])){
			$res = array('success' => false, 'error_message' => $zone->errors[0]->message);

			if(isset($zone->errors[0]->error_chain) && isset($zone->errors[0]->error_chain[0])){
				$res = array('success' => false, 'error_message' => $zone->errors[0]->error_chain[0]->message);
			}
			
			return $res;
		}

		if(empty($zone->result) || empty($zone->result[0])){
			return array('success' => false, 'error_message' => __('There is no zone', 'speedycache'));
		}
		
		foreach($zone->result as $zone_key => $zone_value){
			if(preg_match('/' . $zone_value->name . '/', $hostname)){
				$res = array(
					'success' => true,
					'zoneid' => $zone_value->id,
					'plan' => $zone_value->plan->legacy_id
				);

				self::cloudflare_save_zone_id($res);
			}
		}

		if(empty($res['success'])){
			$res = array('success' => false, 'error_message' => __('No zone name ', 'speedycache') . $hostname);
		}

		return $res;
	}

	static function cloudflare_zone_id_value(){
		global $speedycache;

		$cdn = $speedycache->settings['cdn'];
		
		if(empty($cdn) || !is_array($cdn)){
			return false;
		}

		foreach($cdn as $cdn_key => $cdn_value){
			if($cdn_value['id'] === 'cloudflare'){
				return unserialize($cdn_value['zone_id']);
			}
		}
		
		return false;
	}

	static function cloudflare_unset_zone_id(){
		global $speedycache;
		
		$cdn = $speedycache->settings['cdn'];
		if(empty($cdn) || !is_array($cdn)){
			return;
		}

		foreach($cdn as $cdn_key => $cdn_value){
			if('cloudflare' === $cdn_value['id'] && isset($cdn_value['zone_id'])){
				unset($cdn_value['zone_id']);
			}
		}

		update_option('speedycache_cdn', $cdn);
		$speedycache->settings['cdn'] = $cdn;
	}

	static function cloudflare_save_zone_id($value){
		global $speedycache;
		
		if(empty($speedycache->settings['cdn']) || !is_array($speedycache->settings['cdn'])){
			return;
		}
		
		foreach($speedycache->settings['cdn'] as $cdn_key => &$cdn_value){
			if($cdn_value['id'] === 'cloudflare'){
				$value['time'] = time();
				$cdn_value['zone_id'] = serialize($value);
			}
		}

		update_option('speedycache_cdn', $arr);
		$speedycache->settings['cdn'] = $arr;
	}

	static function cloudflare_remove_webp(){
		$path = ABSPATH . '.htaccess';

		if(file_exists($path) && is_writable($path)){
			$htaccess = file_get_contents($path);
			$htaccess = preg_replace('/#\s?BEGIN\s?WEBPspeedycache.*?#\s?END\s?WEBPspeedycache/s', '', $htaccess);

			file_put_contents($path, $htaccess);
		}
	}

	static function cloudflare_change_settings(){
		// Admin OR Author OR Editor
		if(!current_user_can('manage_options') || !current_user_can('delete_published_posts') || !current_user_can('edit_published_posts')){
			wp_die('Must be admin');
		}
		
		if(isset($_GET['url']) && isset($_GET['origin_url'])){
			$email = speedycache_optget('url');
			$key = speedycache_optget('origin_url');
		}

		$zone = self::cloudflare_zone_id($email, $key);

		if(empty($zone['success'])){
			wp_send_json($zone);
		}

		$minify = self::cloudflare_disable_minify($email, $key, $zone['zoneid']);
		$rocket_loader = self::cloudflare_disable_rocket_loader($email, $key, $zone['zoneid']);
		$purge_cache = self::cloudflare_purge($email, $key, $zone['zoneid']);
		$browser_caching = self::cloudflare_set_browser_caching($email, $key, $zone['zoneid']);
		
		if('free' === $zone['plan']){
			self::cloudflare_remove_webp();
		}

		if(empty($minify['success'])){
			wp_send_json(array('success' => false, 'error_message' => $minify['error_message']));
		}
		
		if(empty($rocket_loader['success'])){
			wp_send_json(array('success' => false, 'error_message' => $rocket_loader['error_message']));
		}
		
		if(!empty($browser_caching['success'])){
			$res = array('success' => true);
		}else{
			$res = array('success' => false, 'error_message' => $browser_caching['error_message']);
		}

		wp_send_json($res);
	}

}

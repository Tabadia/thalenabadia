<?php
/*
* SPEEDYCACHE
* https://speedycache.com/
* (c) SpeedyCache Team
*/

namespace SpeedyCache;

if( !defined('SPEEDYCACHE_VERSION') ){
	die('HACKING ATTEMPT!');
}

class JS{

	static function init($html, $minify = false, $extract_link = true){
		global $speedycache;
		
		$speedycache->js = array();
		$speedycache->js['js_links_except'] = '';
		$speedycache->js['url'] = '';
		$speedycache->js['minify'] = $minify;
		$speedycache->js['js_links'] = array();
		$speedycache->js['html'] = $html;

		if(!empty($extract_link)){
			self::extract_links();
		}
	}

	static function extract_links(){
		global $speedycache;

		self::extract_links_except();
		
		$data = $speedycache->js['html'];
		$script_list = array();
		$script_start_index = false;

		for($i = 0; $i < strlen($data); $i++){
			if(isset($data[$i - 6]) && substr($data, $i - 6, 7) == '<script'){
				$script_start_index = $i - 6;
			}

			if(isset($data[$i - 8]) && !empty($script_start_index) && substr($data, $i - 8, 9) == '</script>'){
				array_push($script_list, array('start' => $script_start_index, 'end' => $i));
				$script_start_index = false;
			}
		}

		if(count($script_list) > 0){
			$speedycache->js['js_links'] = array_reverse($script_list);
		}

		// to update js_links_except
		foreach($speedycache->js['js_links'] as $key => $value){
			$script_tag = substr($speedycache->js['html'], $value['start'], ($value['end'] - $value['start'] + 1));

			if(preg_match('/wp-spamshield\/js\/jscripts\.php/i', $script_tag)){
				$speedycache->js['js_links_except'] = $speedycache->js['js_links_except'] . $script_tag;
			}

			//amazonjs/components/js/jquery-tmpl/jquery.tmpl.min.js?ver=1.0.0pre
			if(preg_match('/jquery-tmpl\/jquery\.tmpl\.min\.js/i', $script_tag)){
				$speedycache->js['js_links_except'] = $speedycache->js['js_links_except'] . $script_tag;
			}
		}
	}

	static function find_tags($start_string, $end_string){
		global $speedycache;
		
		$data = $speedycache->js['html'];

		$list = array();
		$start_index = false;
		$end_index = false;

		for($i = 0; $i < strlen($data); $i++){
			if(substr($data, $i, strlen($start_string)) == $start_string){
				$start_index = $i;
			}

			if($start_index && $i > $start_index && substr($data, $i, strlen($end_string)) == $end_string){
				$end_index = $i + strlen($end_string) - 1;
				$text = substr($data, $start_index, ($end_index - $start_index + 1));

				array_push($list, array('start' => $start_index, 'end' => $end_index, 'text' => $text));

				$start_index = false;
				$end_index = false;
			}
		}

		return $list;
	}

	static function extract_links_except(){
		global $speedycache;
		
		$comment_tags = self::find_tags('<!--', '-->');
		$document_write = self::find_tags('document.write(', ')');

		foreach($comment_tags as $key => $value){
			if(preg_match('/<script/i', $value['text']) && preg_match('/<\/script/i', $value['text'])){
				$speedycache->js['js_links_except'] = $value['text'] . $speedycache->js['js_links_except'];
			}
		}

		foreach($document_write as $key => $value){
			$speedycache->js['js_links_except'] = $value['text'] . $speedycache->js['js_links_except'];
		}
	}

	static function merge($js_content, $value, $last = false){
		global $speedycache;
		
		$name = md5($js_content);
		$name = base_convert(crc32($name), 20, 36);

		$cach_file_path = speedycache_cache_path('assets') . '/' . $name;
		$js_link = self::path_to_link($cach_file_path);
		
		if(!is_dir($cach_file_path)){
			\SpeedyCache\Cache::create_dir($cach_file_path, $js_content, 'js');
		}

		$js_files = @scandir($cach_file_path, 1);
		
		if(!is_dir($cach_file_path) || empty($js_files)){
			return;
		}

		$new_link = '<script src="' . $js_link . '/' . $js_files[0] . '" type="text/javascript"></script>';

		$script_tag = substr($speedycache->js['html'], $value['start'], ($value['end'] - $value['start'] + 1));

		if(!empty($last)){
			$script_tag = $new_link . "\n<!-- " . $script_tag . " -->\n";
		}else{
			$script_tag = $new_link . "\n" . $script_tag;
		}

		$speedycache->js['html'] = substr_replace($speedycache->js['html'], $script_tag, $value['start'], ($value['end'] - $value['start'] + 1));
	}

	static function path_to_link($path){
		
		preg_match('/\/cache\/speedycache\/.+/', $path, $out);
		
		$prefix_link = str_replace(array('http:', 'https:'), '', SPEEDYCACHE_WP_CONTENT_URL);

		return $prefix_link . $out[0];
	}

	static function get_url_content($url){
		
		$data = speedycache_read_file($url);
		
		if(!empty($data)){
			return $data;
		}

		if(!preg_match('/\.php$/', $url)){
			$url = $url . "?v=" . time();
		}

		if(preg_match("/^\/[^\/]/", $url)){
			$url = home_url() . $url;
		}

		$url = preg_replace('/^\/\//', 'http://', $url);

		$response = wp_remote_get($url, array('timeout' => 10));

		if(empty($response) || is_wp_error($response)){
			return false;
		}

		if(wp_remote_retrieve_response_code($response) == 200){
			$data = wp_remote_retrieve_body($response);

			if(preg_match("/<\/\s*html\s*>\s*$/i", $data)){
				return false;
			}
			
			return $data;
		}
	}

	static function check_internal($link){
		
		$http_host = str_replace('www.', '', sanitize_text_field($_SERVER['HTTP_HOST']));

		if(!preg_match('/^<script[^\>]+\>/i', $link, $script) || !preg_match('/src=[\"\'](.*?)[\"\']/', $script[0], $src)){
			return false;
		}
			
		if(preg_match('/alexa\.com\/site\_stats/i', $src[1])){
			return false;
		}

		if(preg_match('/^\/[^\/]/', $src[1])){
			return $src[1];
		}

		if(preg_match('/' . preg_quote($http_host, '/') . '/i', $src[1])){
			//<script src="https://server1.opentracker.net/?site=www.site.com"></script>
			if(preg_match('/[\?\=].*' . preg_quote($http_host, '/') . '/i', $src[1])){
				return false;
			}

			return $src[1];
		}

		return false;
	}

	static function check_exclude($js_url = false){
		global $speedycache;
		
		if(empty($js_url)){
			return;
		}

		foreach((array)$speedycache->settings['exclude_rules'] as $key => $value){

			if(!isset($value['prefix']) || $value['prefix'] && $value['type'] !== 'js'){
				continue;
			}

			if($value['prefix'] == 'contain'){
				$preg_match_rule = preg_quote($value['content'], '/');
			}

			if(preg_match('/' . $preg_match_rule . '/i', $js_url)){
				return true;
			}
		}
	}

	static function minify($url){
		global $speedycache;

		// If the file is already minified we don't need to minify it again.
		if(strpos($url, '.min.') !== FALSE){
			return false;
		}

		$speedycache->js['url'] = $url;

		$md5 = \SpeedyCache\Cache::create_name($url);
		$cach_file_path = speedycache_cache_path('assets/') . $md5;
		$js_link = self::path_to_link($cach_file_path);

		if(is_dir($cach_file_path)){
			return array('cach_file_path' => $cach_file_path, 'js_content' => '', 'url' => $js_link);
		}

		$js = self::get_url_content($url);
		
		if(empty($js)){
			return false;
		}

		if(empty($speedycache->js['minify'])){
			$js = "\n// source --> " . $url . " \n" . $js;
			return array('cach_file_path' => $cach_file_path, 'js_content' => $js, 'url' => $js_link);
		}

		if(!is_callable('\SpeedyCache\Enhanced::init')){
			$js = "\n// source --> " . $url . " \n" . $js;
			return array('cach_file_path' => $cach_file_path, 'js_content' => $js, 'url' => $js_link);
		}

		if(empty($speedycache->enhanced)){
			\SpeedyCache\Enhanced::init();
		}

		$js = \SpeedyCache\Enhanced::minify_js($js);
		return array('cach_file_path' => $cach_file_path, 'js_content' => $js, 'url' => $js_link);

	}

	static function combine(){
		global $speedycache;

		if(count($speedycache->js['js_links']) <= 0){
			return $speedycache->js['html'];
		}

		$prev_content = '';
		
		foreach($speedycache->js['js_links'] as $key => $value){
			
			$script_tag = substr($speedycache->js['html'], $value['start'], ($value['end'] - $value['start'] + 1));

			if(preg_match('/<script[^>]+json[^>]+>.+/', $script_tag) || preg_match('/<script[^>]+text\/template[^>]+>.+/', $script_tag)){
				if($key > 0 && $prev_content){
					self::merge($prev_content, $speedycache->js['js_links'][$key - 1]);
					$prev_content = '';
				}
				
				continue;
			}
			
			$href = self::check_internal($script_tag);
			if(empty($href)){
				if($key > 0 && $prev_content){
					self::merge($prev_content, $speedycache->js['js_links'][$key - 1]);
					$prev_content = '';
				}
				
				continue;
			}
			if(strpos($speedycache->js['js_links_except'], $href) !== false){
				if($key > 0 && $prev_content){
					self::merge($prev_content, $speedycache->js['js_links'][$key - 1]);
					$prev_content = '';
				}

				continue;
			}
			if(($key + 1) && self::check_exclude($href)){
				self::merge($prev_content, $speedycache->js['js_links'][$key - 1]);
				$prev_content = '';
				continue;
			}
			
			$minified_js = self::minify($href);
			
			if(empty($minified_js)){
				if($key > 0 && $prev_content){
					self::merge($prev_content, $speedycache->js['js_links'][$key - 1]);
					$prev_content = '';
				}
				
				continue;
			}

			if(!is_dir($minified_js['cach_file_path'])){
				\SpeedyCache\Cache::create_dir($minified_js['cach_file_path'], $minified_js['js_content'], 'js');
			}
			
			$js_files = @scandir($minified_js['cach_file_path'], 1);
			if(empty($js_files)){
				continue;
			}

			$js_files[0] = preg_replace("/\.gz$/", '', $js_files[0]);
			$js_content = self::get_url_content($minified_js['url'] . '/' . $js_files[0] . '?v=' . time());
			if(empty($js_content)){
				continue;
			}

			if(preg_match('/^[\"\']use strict[\"\']/i', $js_content)){
				self::merge($prev_content, $speedycache->js['js_links'][$key - 1]);
				$prev_content = '';
			}else{
				$prev_content = $js_content . "\n" . $prev_content;

				$script_tag = '<!-- ' . $script_tag . ' -->';

				if(($key + 1) == count($speedycache->js['js_links'])){
					self::merge($prev_content, $value, true);
					$prev_content = '';
				} else {
					$speedycache->js['html'] = substr_replace($speedycache->js['html'], $script_tag, $value['start'], ($value['end'] - $value['start'] + 1));
				}
			}
		}
		
		return $speedycache->js['html'];
	}

}

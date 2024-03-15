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

class CSS{

	static function extract($html){
		global $speedycache;
		
		$speedycache->css_util = array();
		$speedycache->css_util['except'] = '';
		$speedycache->css_util['in_hack'] = false;
		$speedycache->css_util['tags']= array();
		$speedycache->css_util['html'] = $html;
		$speedycache->css_util['url_for_fix'] = '';
		
		self::set_except_tags();
		self::set_tags();
		self::tags_reorder();
	}

	static function apply_filter($content){
		return apply_filters('speedycache_css_content', $content, null, null);
	}

	static function to_inline($link, $css_content){
		global $speedycache;
		
		if(empty($speedycache->options['render_blocking'])){
			return $link;
		}

		if(!preg_match("/\smedia\=[\'\"]all[\'\"]/i", $link)){
			return $link;
		}

		if(isset($css_content['11000'])){
			return $link;
		}

		$link = '<style>'.$css_content.'</style>';

		return $link;
	}

	static function minify($url){
		global $speedycache;
		
		$speedycache->css_util['url'] = $url;
		$md5 = \SpeedyCache\Cache::create_name($url);

		$cach_file_path = speedycache_cache_path('assets').'/'.$md5;
		$css_link = self::convert_path_to_link($cach_file_path);

		if(is_dir($cach_file_path)){
			$css_files = @scandir($cach_file_path, 1);
			
			if(empty($css_files)){
				return false;
			}

			$css_content = self::file_get_contents_curl($css_link.'/'.$css_files[0]);
			if(empty($css_content)){
				return false;
			}

			$css_content = self::apply_filter($css_content);

			return array('cach_file_path' => $cach_file_path, 'css_content' => $css_content, 'url' => $css_link.'/'.$css_files[0], 'realUrl' => $url);
		}

		$css_content = self::file_get_contents_curl($url, '?v='.time());

		if(empty($css_content)){
			return false;
		}

		$original_content_length = strlen($css_content);

		if(!empty($speedycache->options['minify_css'])){
			$css_content = self::process($css_content);
		}

		$css_content = self::fix_paths_in_css_content($css_content, $url);

		$css_content = self::apply_filter($css_content);

		if(!empty($speedycache->options['minify_css_enhanced']) && defined('SPEEDYCACHE_PRO')){
			\SpeedyCache\Enhanced::init();
			$css_content = \SpeedyCache\Enhanced::minify_css($css_content);
		}

		$css_content = str_replace('\xEF\xBB\xBF', '', $css_content);

		// If the content is empty, the file is not created. This breaks "combine css" feature 
		if(strlen($css_content) == 0 && $original_content_length > 0){
			return array('css_content' => '', 'url' => $url);
		}

		if(!is_dir($cach_file_path)){
			if($speedycache->settings['cdn']){
				$css_content = preg_replace_callback('/(url)\(([^\)]+)\)/i', '\SpeedyCache\CDN::replace_urls', $css_content);
			}

			\SpeedyCache\Cache::create_dir($cach_file_path, $css_content, 'css');
		}

		if($css_files = @scandir($cach_file_path, 1)){
			return array('cach_file_path' => $cach_file_path, 'css_content' => $css_content, 'url' => $css_link.'/'.$css_files[0], 'realUrl' => $url);
		}

		return false;
	}

	static function check_internal($link){
		$http_host = str_replace('www.', '', sanitize_text_field($_SERVER['HTTP_HOST']));
		
		if(preg_match("/href=[\"\'](.*?)[\"\']/", $link, $href)){

			if(preg_match("/^\/[^\/]/", $href[1])){
				return $href[1];
			}

			if(@strpos($href[1], $http_host)){
				return $href[1];
			}
		}

		return false;
	}

	static function minify_css(){
		global $speedycache;
		
		$data = $speedycache->css_util['html'];

		if(count($speedycache->css_util['tags']) <= 0){
			return $speedycache->css_util['html'];
		}
		
		foreach(array_reverse($speedycache->css_util['tags']) as $key => $value){
			$text = substr($data, $value['start'], ($value['end'] - $value['start'] + 1));

			if(!preg_match('/<link/i', $text)){
				continue;
			}
				
			$href = self::check_internal($text);
			if(empty($href)){
				continue;
			}

			// If the file is already a minified file then we dont need to process it.
			if(strpos($href, '.min.') !== FALSE){
				continue;
			}

			if(self::check_exclude($href)){
				continue;
			}

			$minified_css = self::minify($href);
			self::update_links($minified_css, $value, $text); // updates links in the minified css
		}

		return $speedycache->css_util['html'];
	}

	static function update_links($minified_css, $value, $text){
		global $speedycache;
		
		if(empty($minified_css)){
			return false;
		}
		
		$prefix_link = str_replace(array('http:', 'https:'), '', $minified_css['url']);
		$text = preg_replace("/href\=[\"\'][^\"\']+[\"\']/", 'href="'.$prefix_link.'"', $text);

		$minified_css['css_content'] = self::apply_filter($minified_css['css_content']);

		$text = self::to_inline($text, $minified_css['css_content']);

		$speedycache->css_util['html'] = substr_replace($speedycache->css_util['html'], $text, $value['start'], ($value['end'] - $value['start'] + 1));
	}

	static function tags_reorder(){
		global $speedycache;
		
		$sorter = array();
		$ret = array();

		foreach($speedycache->css_util['tags'] as $ii => $va){
			$sorter[$ii] = $va['start'];
		}

		asort($sorter);

		foreach($sorter as $ii => $va){
			$ret[$ii] = $speedycache->css_util['tags'][$ii];
		}

		$speedycache->css_util['tags'] = $ret;
	}

	static function find_tags($start_string, $end_string, $source = false){
		global $speedycache;
		
		$data = $speedycache->css_util['html'];
		if(!empty($source)){
			$data = $source;
		}

		$list = array();
		$start_index = false;
		$end_index = false;

		for($i = 0; $i < strlen($data); $i++){
			if(substr($data, $i, strlen($start_string)) == $start_string){
				$start_index = $i;
			}

			if(empty($start_index) || $i <= $start_index){
				continue;
			}

			if(substr($data, $i, strlen($end_string)) == $end_string){
				$end_index = $i + strlen($end_string)-1;
				$text = substr($data, $start_index, ($end_index - $start_index + 1));

				array_push($list, array('start' => $start_index, 'end' => $end_index, 'text' => $text));

				$start_index = false;
				$end_index = false;
			}
		}

		return $list;
	}

	static function set_except_tags(){
		global $speedycache;
		
		$comment_tags = self::find_tags('<!--', '-->');

		foreach($comment_tags as $key => $value){
			$speedycache->css_util['except'] = $value['text'].$speedycache->css_util['except'];
		}

		// to execute if html contains <noscript> tag
		if(!preg_match('/<noscript/i', $speedycache->css_util['html'])){
			$noscript_tags = self::find_tags('<noscript', '</noscript>');

			foreach($noscript_tags as $key => $value){
				$speedycache->css_util['except'] = $value['text'].$speedycache->css_util['except'];

				if(!empty($speedycache->options['lazy_load'])){
					// to set noscript for lazy load
					// <noscript><img src="http://google.com/image.jpg"></noscript>
					$speedycache->settings['noscript'] = $value['text'].$speedycache->settings['noscript'];
				}
			}
		}

		$script_tags = self::find_tags('<script', '</script>');

		foreach($script_tags as $key => $value){
			$link_tags = self::find_tags('<link', '>', $value['text']);

			if(count($link_tags) > 0){
				$speedycache->css_util['except'] = $value['text'].$speedycache->css_util['except'];
			}
		}
	}

	static function set_tags(){
		global $speedycache;
		
		$style_tags = self::find_tags('<style', '</style>');
		$speedycache->css_util['tags'] = array_merge($speedycache->css_util['tags'], $style_tags);
		
		$link_tags = self::find_tags('<link', '>');

		foreach($link_tags as $key => $value){
			//<link rel='stylesheet' id='avada-dynamic-css-css'  href='/site-data/uploads/avada-styles/avada-9.css?timestamp=1485306359&#038;ver=4.7.2' type='text/css' media='all' />
			if(preg_match('/avada-dynamic-css-css/', $value['text'])){
				continue;
			}

			preg_match('/media\=[\'\"]([^\'\"]+)[\'\"]/', $value['text'], $media);
			preg_match('/href\=[\'\"]([^\'\"]+)[\'\"]/', $value['text'], $href);
			
			/* here index "1" is being used bcoz preg_match returns a array with the match and the group so we need the group which is at index [1]
			*/
			$media[1] = (isset($media[1]) && $media[1]) ? trim($media[1]) : '';
			$value['media'] = (isset($media[1]) && $media[1]) ? $media[1] : 'all';

			if(!isset($href[1])){
				continue;
			}

			$href[1] = trim($href[1]);
			$value['href'] = (isset($href[1]) && $href[1]) ? $href[1] : '';

			if(preg_match('/href\s*\=/i', $value['text']) && preg_match("/rel\s*\=\s*[\'\"]\s*stylesheet\s*[\'\"]/i", $value['text'])){
				array_push($speedycache->css_util['tags'], $value);
			}
		}
	}

	static function file_get_contents_curl($url, $version = ''){
		if($data = speedycache_read_file($url)){
			return $data;
		}

		$url = str_replace('&#038;', '&', $url);
		
		if(preg_match('/\.php\?/i', $url)){
			$version = '';
		}

		if(preg_match('/(fonts\.googleapis\.com|iire-social-icons)/i', $url)){
			$version = '';
			$url = str_replace(array("'","'"), '', $url);
		}

		$url = $url.$version;

		if(preg_match('/^\/[^\/]/', $url)){
			$url = get_option('home').$url;
		}

		if(preg_match('/http\:\/\//i', home_url())){
			$url = preg_replace('/^\/\//', 'http://', $url);
		}else if(preg_match('/https\:\/\//i', home_url())){
			$url = preg_replace('/^\/\//', 'https://', $url);
		}

		//$response = wp_remote_get($url, array('timeout' => 10, 'headers' => array("cache-control" => array("no-store, no-cache, must-revalidate", "post-check=0, pre-check=0"))));
		$response = wp_remote_get($url, array('timeout' => 10, 'user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.110 Safari/537.36'));
		
		if(empty($response) || is_wp_error($response)){
			return false;
		}

		if(wp_remote_retrieve_response_code($response) == 200){
			$data = wp_remote_retrieve_body( $response );

			if(preg_match('/\<\!DOCTYPE/i', $data) || preg_match('/<\/\s*html\s*>/i', $data)){
				return false;
			}else if(empty($data)){
				return '/* empty */';
			}
			
			return $data;
		}else if(wp_remote_retrieve_response_code($response) == 404){
			if(preg_match('/\.css/', $url)){
				return '/*404*/';
			}
			
			return '<!-- 404 -->';
		}
	}

	static function fix_charset($css){
		preg_match_all('/@charset[^\;]+\;/i', $css, $charsets);
		if(count($charsets[0]) > 0){
			$css = preg_replace('/@charset[^\;]+\;/i', '', $css);
			foreach($charsets[0] as $charset){
				$css = $charset."\n".$css;
			}
		}
		return $css;
	}

	static function fix_paths_in_css_content($css, $url){
		global $speedycache;
		
		$speedycache->css_util['url_for_fix'] = $url;
		
		$css = preg_replace('/@import\s+[\"\']([^\;\"\'\)]+)[\"\'];/', '@import url($1);', $css);
		$css = preg_replace_callback('/url\(([^\)\n]*)\)/', '\SpeedyCache\CSS::new_img_path', $css);
		$css = preg_replace_callback('/@import\s+url\(([^\)]+)\);/i', '\SpeedyCache\CSS::fix_import_rules', $css);
		$css = self::fix_charset($css);

		return $css; 
	}

	static function woff_to_file($source){
		
		// url("data:application/x-font-woff;charset=utf-8;base64,d09GRgABAAAA")
		$is_base64 = false;
		if(preg_match('/base64\,/', $source)){
			$is_base64 = true;
		}

		if(empty($is_base64)){
			return $source;
		}
		
		// not to use preg_match() for the speed
		$source = strstr($source, 'base64,');
		$source = str_replace('base64,', '', $source);
		$source = trim($source);
		$source = str_replace(array("'", "'"), '', $source);

		$md5 = \SpeedyCache\Cache::create_name($source);
		$cach_file_path = speedycache_cache_path('assets').'/woff-'.$md5;

		\SpeedyCache\Cache::create_dir($cach_file_path, $source, 'woff');
		$css_files = @scandir($cach_file_path, 1);

		if(is_dir($cach_file_path) && !empty($css_files)){
			$link = self::convert_path_to_link($cach_file_path.'/'.$css_files[0]);

			return $link;
		}

		return $source;
	}

	static function svg_to_file($source){
		return $source;
		
		$is_base64 = false;
		if(preg_match('/base64\,/', $source)){
			$is_base64 = true;
		}
		
		if(!preg_match('/\,(%3Csvg|<svg)/', $source) || empty($is_base64)){
			return $source;
		}
		
		$source = preg_replace('/\"|\'/', '', $source);
		$source = preg_replace('/data[^\,]+\,/', '', $source);

		if(!empty($is_base64)){
			$source = base64_decode($source);
		}else{
			$source = rawurldecode($source);
		}

		$md5 = \SpeedyCache\Cache::create_name($source);
		$cach_file_path = speedycache_cache_path('assets').'/svg-'.$md5;

		\SpeedyCache\Cache::create_dir($cach_file_path, $source, 'svg');

		if(is_dir($cach_file_path) && $css_files = @scandir($cach_file_path, 1)){
			$source = self::convert_path_to_link($cach_file_path.'/'.$css_files[0]);
		}

		return $source;
	}

	static function new_img_path($matches){
		global $speedycache;
		
		$matches[1] = trim($matches[1]);
		
		if(preg_match('/data\:font\/opentype/i', $matches[1])){
			$matches[1] = $matches[1];
			return 'url('.$matches[1].')';
		}
		
		if(preg_match('/data\:application\/x-font-woff/i', $matches[1])){
			$matches[1] = self::woff_to_file($matches[1]);
			return 'url('.$matches[1].')';
		}
		
		if(preg_match('/data\:image\/svg\+xml/i', $matches[1])){
			$matches[1] = self::svg_to_file($matches[1]);
			return 'url('.$matches[1].')';
		}
		
		$matches[1] = str_replace(array("\"","'"), '', $matches[1]);
		$matches[1] = trim($matches[1]);
		
		if(empty($matches[1])){
			$matches[1] = '';
		}
		
		if(preg_match('/^\#/', $matches[1])){
			$matches[1] = $matches[1];
		}
		
		if(preg_match('/^(\/\/|http|\/\/fonts|data:image|data:application)/', $matches[1])){
			if(preg_match('/fonts\.googleapis\.com/', $matches[1])){ // for safari browser
				$matches[1] = "'".$matches[1]."'";
			}else{
				$matches[1] = $matches[1];
			}
		}else if(preg_match("/^\//", $matches[1])){
			$homeUrl = str_replace(array('http:', 'https:'), '', home_url());
			$matches[1] = $homeUrl.$matches[1];
		}else if(preg_match("/^\.\/.+/i", $matches[1])){
			//$matches[1] = str_replace("./", get_template_directory_uri()."/", $matches[1]);
			$matches[1] = str_replace('./', dirname($speedycache->css_util['url_for_fix'])."/", $matches[1]);
		}else if(preg_match("/^(?P<up>(\.\.\/)+)(?P<name>.+)/", $matches[1], $out)){
			$count = strlen($out['up'])/3;
			$url = dirname($speedycache->css_util['url']);
			for($i = 1; $i <= $count; $i++){
				$url = substr($url, 0, strrpos($url, '/'));
			}
			$url = str_replace(array('http:', 'https:'), '', $url);
			$matches[1] = $url.'/'.$out['name'];
		}else{
			$url = str_replace(array('http:', 'https:'), '', dirname($speedycache->css_util['url']));
			$matches[1] = $url.'/'.$matches[1];
		}

		return 'url('.$matches[1].')';
	}

	static function is_internal_css($url){
		$http_host = trim(sanitize_text_field($_SERVER['HTTP_HOST']), 'www.');

		$url = trim($url);
		$url = trim($url, "'");
		$url = trim($url, "'");

		$url = str_replace(array('http://', 'https://'), '', $url);

		$url = trim($url, '//');
		$url = trim($url, 'www.');

		if(empty($url)){
			return false;
		}

		if(preg_match('/'.$http_host.'/i', $url)){
			return true;
		}

		return false;
	}

	static function fix_import_rules($matches){
		global $speedycache;
		
		if(!self::is_internal_css($matches[1])){
			return $matches[0];
		}
		
		$css_content = self::file_get_contents_curl($matches[1], '?v='.time());
		
		if(empty($css_content)){
			return $matches[0];
		}

		$tmp_url = $speedycache->css_util['url'];
		$speedycache->css_util['url'] = $matches[1];
		$css_content = self::fix_paths_in_css_content($css_content, $matches[1]);
		$speedycache->css_util['url'] = $tmp_url;

		// to minify again because of the @import css sources
		if(!empty($speedycache->options['minify_css']) && $speedycache->options['minify_css']){
			$css_content = self::process($css_content);
		}
		
		return $css_content;
	}

	static function comment_cb($m){
		global $speedycache;
		
		$has_surrounding_ws = (trim($m[0]) !== $m[1]);
		$m = $m[1]; 
		// $m is the comment content w/o the surrounding tokens, 
		// but the return value will replace the entire comment.
		if($m === 'keep'){
			return '/**/';
		}
		if($m === '" "'){
			// component of http://tantek.com/CSS/Examples/midpass.html
			return '/*" "*/';
		}
		if(preg_match('@";\\}\\s*\\}/\\*\\s+@', $m)){
			// component of http://tantek.com/CSS/Examples/midpass.html
			return '/*";}}/* */';
		}
		if($speedycache->css_util['in_hack']){
			// inversion: feeding only to one browser
			if(preg_match('@
					^/               # comment started like /*/
					\\s*
					(\\S[\\s\\S]+?)  # has at least some non-ws content
					\\s*
					/\\*             # ends like /*/ or /**/
				@x', $m, $n)){
				// end hack mode after this comment, but preserve the hack and comment content
				$speedycache->css_util['in_hack'] = false;
				return "/*/{$n[1]}/**/";
			}
		}
		if(substr($m, -1) === '\\'){ // comment ends like \*/
			// begin hack mode and preserve hack
			$speedycache->css_util['in_hack'] = true;
			return '/*\\*/';
		}
		if($m !== '' && $m[0] === '/'){ // comment looks like /*/ foo */
			// begin hack mode and preserve hack
			$speedycache->css_util['in_hack'] = true;
			return '/*/*/';
		}
		if($speedycache->css_util['in_hack']){
			// a regular comment ends hack mode but should be preserved
			$speedycache->css_util['in_hack'] = false;
			return '/**/';
		}
		// Issue 107: if there's any surrounding whitespace, it may be important, so 
		// replace the comment with a single space
		return $has_surrounding_ws ? ' ' : ''; // remove all other comments
	}

	static function process($css){
		$css = preg_replace("/^\s+/m", '', ((string) $css));
		$css = str_replace("\r", '', $css);
		
		$css = preg_replace_callback('@\\s*/\\*([\\s\\S]*?)\\*/\\s*@'
			, '\SpeedyCache\CSS::comment_cb', $css);

		//to remove empty chars from url()
		$css = preg_replace('/url\((\s+)([^\)]+)(\s+)\)/', 'url($2)', $css);

		return trim($css);
	}

	static function convert_path_to_link($path){
		preg_match('/\/cache\/speedycache\/.+/', $path, $out);
		$prefix_link = str_replace(array('http:', 'https:'), '', SPEEDYCACHE_WP_CONTENT_URL);

		return $prefix_link . $out[0];
	}

	static function check_exclude($css_url = false){
		global $speedycache;
		
		if(empty($css_url)){
			return;
		}
		// to exclude the css source of elementor which is /elementor/css/post-[number].css to avoid increasing the size of minified sources
		if(preg_match('/\/elementor\/css\/post-\d+\.css/i', $css_url)){
			return true;
		}

		foreach((array)$speedycache->settings['exclude_rules'] as $key => $value){
			
			if(empty($value['prefix']) || $value['type'] !== 'css'){
				continue;
			}

			if($value['prefix'] == 'contain'){
				$preg_match_rule = preg_quote($value['content'], '/');
			}

			if(preg_match('/'.$preg_match_rule.'/i', $css_url)){
				return true;
			}
		}
	}

	static function combine_css(){
		global $speedycache;
		
		$all = array();
		$group = array();

		foreach($speedycache->css_util['tags'] as $key => $value){
			if(preg_match('/<link/i', $value['text'])){

				if(!empty($speedycache->css_util['except']) && strpos($speedycache->css_util['except'], $value['text']) !== false){
					array_push($all, $group);
					$group = array();
					continue;
				}

				if(empty(self::check_internal($value['text']))){
					array_push($all, $group);
					$group = array();
					continue;
				}

				if(!empty(self::check_exclude($value['text']))){
					array_push($all, $group);
					$group = array();
					continue;
				}

				if(count($group) > 0){
					if($group[0]['media'] == $value['media']){
						array_push($group, $value);
					}else{
						array_push($all, $group);
						$group = array();
						array_push($group, $value);
					}
				}else{
					array_push($group, $value);
				}

				if($value === end($speedycache->css_util['tags'])){
					array_push($all, $group);
				}
			}

			if(preg_match('/<style/i', $value['text'])){
				if(count($group) > 0){
					array_push($all, $group);
					$group = array();
				}
			}
		}

		if(count($all) <= 0){
			return $speedycache->css_util['html'];
		}
		
		$all = array_reverse($all);

		foreach($all as $group_key => $group_value){
			if(count($group_value) <= 0){
				continue;
			}

			$combined_css = '';
			$combined_name = \SpeedyCache\Cache::create_name($group_value);
			$combined_link = '';

			$cach_file_path = speedycache_cache_path('assets').'/'.$combined_name;
			$css_link = self::convert_path_to_link($cach_file_path);

			$combined_link = self::prepare_for_combine($cach_file_path, $css_link, $group_value);

			if(empty($combined_link)){
				continue;
			}

			foreach(array_reverse($group_value) as $tag_key => $tag_value){
				$text = substr($speedycache->css_util['html'], $tag_value['start'], ($tag_value['end'] - $tag_value['start'] + 1));

				if($tag_key <= 0){
					$speedycache->css_util['html'] = substr_replace($speedycache->css_util['html'], '<!-- '.$text.' -->'."\n".$combined_link, $tag_value['start'], ($tag_value['end'] - $tag_value['start'] + 1));
				}else{
					$speedycache->css_util['html'] = substr_replace($speedycache->css_util['html'], '<!-- '.$text.' -->', $tag_value['start'], ($tag_value['end'] - $tag_value['start'] + 1));
				}
			}
		}

		return $speedycache->css_util['html'];
	}

	static function prepare_for_combine($cach_file_path, $css_link, $group_value){
		global $speedycache;
		
		if(is_dir($cach_file_path) && $css_files = @scandir($cach_file_path, 1)){
			$combined_link = '<link rel="stylesheet" type="text/css" href="'.$css_link.'/'.$css_files[0].'" media="'.$group_value[0]['media'].'"/>';

			if($css_content = speedycache_read_file($css_link.'/'.$css_files[0])){

				$css_content = self::apply_filter($css_content);

				$combined_link = self::to_inline($combined_link, $css_content);
			}
			
			return $combined_link;
		}

		$combined_css = self::create_content(array_reverse($group_value));
		$combined_css = self::fix_charset($combined_css);

		if(empty($combined_css)){
			return $combined_link;
		}

		if($speedycache->settings['cdn']){
			$combined_css = preg_replace_callback('/(url)\(([^\)]+)\)/i', '\SpeedyCache\CDN::replace_urls', $combined_css);
		}

		\SpeedyCache\Cache::create_dir($cach_file_path, $combined_css, 'css');

		if(is_dir($cach_file_path) && $css_files = @scandir($cach_file_path, 1)){
			$combined_link = '<link rel="stylesheet" type="text/css" href="'.$css_link.'/'.$css_files[0].'" media="'.$group_value[0]['media'].'"/>';

			$combined_css = self::apply_filter($combined_css);
			$combined_link = self::to_inline($combined_link, $combined_css);
		}

		return $combined_link;
	}

	static function create_content($gvalue){
		$comb_css = '';
		
		if(empty($gvalue)){
			return false;
		}

		foreach($gvalue as $tag_key => $tag_value){
			$min_css = self::minify($tag_value['href']);

			if(empty($min_css)){
				return false;
			}
			
			$comb_css = $min_css['css_content'] . $comb_css;
		}

		return $comb_css;
	}

}
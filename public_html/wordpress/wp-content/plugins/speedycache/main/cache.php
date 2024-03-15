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

class Cache{

	static function init(){
		global $speedycache;
		
		if(!empty($speedycache->options['disable_emojis'])){
			add_action('init', '\SpeedyCache\Cache::disable_emojis');
		}
		
		if(!empty($speedycache->options['dns_prefetch']) && !empty($speedycache->options['dns_urls'])){
			add_filter('wp_resource_hints', '\SpeedyCache\Cache::dns_prefetch_hint', 10, 2);
		}
		
		// Adds preconnect
		if(!empty($speedycache->options['pre_connect']) && !empty($speedycache->options['pre_connect_list'])){
			add_filter('wp_resource_hints', '\SpeedyCache\Enhanced::pre_connect_hint', 10, 2);
		}
		
		// Adds Preload link tag to the head
		if(defined('SPEEDYCACHE_PRO') && !empty($speedycache->options['preload_resources'])){
			add_action('wp_head', '\SpeedyCache\Enhanced::preload_resource', 1);
		}

		$speedycache->settings['cdn'] = array();
		$speedycache->settings['err'] = '';
		$speedycache->settings['cache_file_path'] = '';
		$speedycache->settings['exclude_rules'] = false;
		$speedycache->settings['preload_user_agent'] = false;
		$speedycache->settings['current_page_type'] = false;
		$speedycache->settings['cur_content_type'] = false;
		$speedycache->settings['exclude_current_page_text'] = false;

		$user_agent = !empty($_SERVER['HTTP_USER_AGENT']) ? speedycache_optserver('HTTP_USER_AGENT') : 'Empty User Agent';

		if(preg_match("/(speedycache\sCache\sPreload(\siPhone\sMobile)?\s*Bot)/", $user_agent)){
			$speedycache->settings['preload_user_agent'] = true;
		}else{
			$speedycache->settings['preload_user_agent'] = false;
		}

		self::set_cdn();
		self::set_file_path();
		self::set_exclude_rules();
	}

	static function remove_url_params(){
		global $speedycache;
		
		$action = false;
		
		$regex = [
			'/gclid\=/i', //google click identifier
			'/fbclid\=/i', // facebook parameters
			'/utm_(source|medium|campaign|content|term)/i' //google analytics parameters
		];
		
		foreach($regex as $r){
			if(preg_match($r, $speedycache->settings['cache_file_path'])){
				$action = true;
				break;
			}
		}

		if(!empty($action) && !empty($_SERVER['REQUEST_URI']) && strlen($_SERVER['REQUEST_URI']) > 1){ 
			$speedycache->settings['cache_file_path'] = preg_replace("/\/*\?.+/", '', $speedycache->settings['cache_file_path']);
			$speedycache->settings['cache_file_path'] = $speedycache->settings['cache_file_path'].'/';

			define('SPEEDYCACHE_CACHE_QUERYSTRING', true);
		}
	}

	static function set_exclude_rules(){
		global $speedycache;
		
		$exclude_rules = get_option('speedycache_exclude');
		
		if(!empty($exclude_rules)){
			$speedycache->settings['exclude_rules'] = $exclude_rules;
		}
	}
	
	// Adds DNS prefetch
	static function dns_prefetch_hint($urls, $relation_type){
		global $speedycache;

		if($relation_type !== 'dns-prefetch'){
			return $urls;
		}

		foreach($speedycache->options['dns_urls'] as $url) {
			if(!empty($url)){
				$urls[] = $url;
			}
		}

		return $urls;
	}
	
	/*
	* Sets the path were we want to save the cache
	* @param $uri string It [is the Request URI, it should not contain the host 
	* and the protocol in it]
	*/
	static function set_file_path($uri = ''){
		global $speedycache;
		
		if(empty($uri)){
			$uri = speedycache_sanitize_url($_SERVER['REQUEST_URI']);
		}
		
		$type = 'all';

		if(speedycache_is_mobile() && !empty($speedycache->options['mobile'])){
			if(defined('SPEEDYCACHE_PRO') && file_exists(SPEEDYCACHE_PRO_DIR . '/main/mobile.php') && !empty($speedycache->options['mobile_theme'])){
				$type = 'mobile-cache';
			}
		}

		if(speedycache_is_plugin_active('gtranslate/gtranslate.php')){
			if(isset($_SERVER['HTTP_X_GT_LANG'])){
				$speedycache->settings['cache_file_path'] = speedycache_cache_path($type.'/').$_SERVER['HTTP_X_GT_LANG']. $uri;
			}else if(isset($uri) && $uri !== '/index.php'){
				$speedycache->settings['cache_file_path'] = speedycache_cache_path($type).$uri;
			}else if(isset($uri)){
				$speedycache->settings['cache_file_path'] = speedycache_cache_path($type).$uri;
			}
		}else{
			$speedycache->settings['cache_file_path'] = speedycache_cache_path($type).$uri;

			// for /?s=
			$speedycache->settings['cache_file_path'] = preg_replace("/(\/\?s\=)/", "$1/", $speedycache->settings['cache_file_path']);
		}

		$speedycache->settings['cache_file_path'] = $speedycache->settings['cache_file_path'] ? rtrim($speedycache->settings['cache_file_path'], '/').'/' : '';
		$speedycache->settings['cache_file_path'] = preg_replace('/\/speedycache\/('.SPEEDYCACHE_SERVER_HOST.')\/\//', '/speedycache/$1/', $speedycache->settings['cache_file_path']);

		if(strlen($uri) > 1 && !preg_match('/\.html/i', $uri) && speedycache_is_trailing_slash() && !preg_match('/\/$/', $uri)){ // for the sub-pages

			if((defined('SPEEDYCACHE_CACHE_QUERYSTRING') && SPEEDYCACHE_CACHE_QUERYSTRING) ||
				(preg_match('/utm_(source|medium|campaign|content|term)/i', $speedycache->settings['cache_file_path'])) ||
				(preg_match('/fbclid\=/i', $speedycache->settings['cache_file_path'])) ||
				(preg_match('/gclid\=/i', $speedycache->settings['cache_file_path']))){
				//do nothing
			}else if(isset($_GET['test_speedycache'])){
				// Updates the string for test folder
				$speedycache->settings['cache_file_path'] = speedycache_cache_path('test').str_replace('?test_speedycache=1', '', $uri);
			}else{
				$speedycache->settings['cache_file_path'] = false;
			}
		}

		self::remove_url_params();


		// to decode path if it is not utf-8
		if(!empty($speedycache->settings['cache_file_path'])){
			$speedycache->settings['cache_file_path'] = urldecode($speedycache->settings['cache_file_path']);
		}

		// for security
		if(preg_match("/\.{2,}/", $speedycache->settings['cache_file_path'])){
			$speedycache->settings['cache_file_path'] = false;
		}

		if(!speedycache_is_mobile() || empty($speedycache->options['mobile'])){
			return;
		}
		
		if(!defined('SPEEDYCACHE_PRO') || empty($speedycache->options['mobile_theme'])){
			$speedycache->settings['cache_file_path'] = false;
		}

	}

	static function set_cdn(){
		global $speedycache;
		
		$cdn_values = get_option('speedycache_cdn');
		
		if(empty($cdn_values)){
			$speedycache->settings['cdn'] = array();
			return;
		}

		$arr = array();

		if(is_array($cdn_values)){
			$arr = $cdn_values;
		}else{
			array_push($arr, $cdn_values);
		}

		foreach($arr as $key => &$std){
			$std['origin_url'] = trim($std['origin_url']);
			$std['origin_url'] = trim($std['origin_url'], '/');
			$std['origin_url'] = preg_replace('/http(s?)\:\/\/(www\.)?/i', '', $std['origin_url']);

			$std['cdn_url'] = trim($std['cdn_url']);
			$std['cdn_url'] = trim($std['cdn_url'], '/');

			if(!preg_match('/https\:\/\//', $std['cdn_url'])){
				$std['cdn_url'] = "//".preg_replace('/http(s?)\:\/\/(www\.)?/i', '', $std['cdn_url']);
			}
		}

		$speedycache->settings['cdn'] = $arr;
	}


	static function create(){
		global $speedycache;
		
		if(empty($speedycache->options['status'])){
			return;
		}

		$can_cache = self::allowed();
		
		if(empty($can_cache)){
			return false;
		}

		//to show cache version via php if htaccess rewrite rule does not work
		if(empty($speedycache->settings['preload_user_agent']) && !empty($speedycache->settings['cache_file_path']) && (@file_exists($speedycache->settings['cache_file_path'].'index.html') || @file_exists($speedycache->settings['cache_file_path'].'index.json') || @file_exists($speedycache->settings['cache_file_path'].'index.xml'))){

			$via_php = '';
			if(@file_exists($speedycache->settings['cache_file_path'].'index.json')){
				$file_extension = 'json';

				header('Content-type: application/json');
			}else if(@file_exists($speedycache->settings['cache_file_path'].'index.xml')){
				$file_extension = 'xml';

				header('Content-type: text/xml');
			
			}else if(!empty($speedycache->options['gzip']) && @file_exists($speedycache->settings['cache_file_path'].'index.html.gz')){
				$file_extension = 'html.gz';

				header('Content-Encoding: gzip');
			
			}else{
				$file_extension = 'html';
				$via_php = '<!-- via php -->';
			}
			
			$content = @file_get_contents($speedycache->settings['cache_file_path'] . 'index.' . $file_extension);
			
			if(empty($content)){
				return;
			}

			if(defined('SPEEDYCACHE_SHOW_VIA_COMMENT') && !empty(SPEEDYCACHE_SHOW_VIA_COMMENT) && $file_extension === 'html'){
				$via_php = '';
			}

			$content = $content . $via_php;
			$content = apply_filters('speedycache_content_via_php', $content);
			
			die($content);
		}

		$can_create_cache = self::can_create();

		if(!empty($can_create_cache)){
			$speedycache->start_time = microtime(true);

			add_action('wp','\SpeedyCache\Cache::detect_current_page_type');
			add_action('get_footer', '\SpeedyCache\Cache::detect_current_page_type');
			add_action('get_footer', '\SpeedyCache\Cache::print_scripts_action');

			ob_start('\SpeedyCache\Cache::callback');
		}
	}

	static function allowed(){
		global $speedycache;

		// Exclude static pdf files
		if(preg_match('/\.pdf$/i', speedycache_sanitize_url($_SERVER['REQUEST_URI']))){
			return false;
		}

		// Logged-in user ?
		if(!empty($speedycache->options['logged_in_user']) && $speedycache->options['logged_in_user'] == 'on'){
			foreach((array)$_COOKIE as $cookie_key => $cookie_value){
				if(preg_match('/wordpress_logged_in/i', $cookie_key)){
					ob_start('\SpeedyCache\Cache::cdn_rewrite');

					return false;
				}
			}
		}

		// to exclude admin users
		if(!empty(self::is_admin())){
			return false;
		}

		// To check comment author
		foreach((array)$_COOKIE as $cookie_key => $cookie_value){
			if(preg_match('/comment_author_/i', $cookie_key)){
				ob_start('\SpeedyCache\Cache::cdn_rewrite');

				return false;
			}
		}

		if(isset($_COOKIE) && isset($_COOKIE['safirmobilswitcher'])){
			ob_start('\SpeedyCache\Cache::cdn_rewrite');

			return false;
		}

		if(isset($_COOKIE) && isset($_COOKIE['wptouch-pro-view']) && self::is_wptouch_smartphone() && $_COOKIE['wptouch-pro-view'] == 'desktop'){
			ob_start( '\SpeedyCache\Cache::cdn_rewrite' );

			return false;
		}

		if(!isset($_GET['test_speedycache']) && preg_match('/\?/', $_SERVER['REQUEST_URI']) && !preg_match('/\/\?fdx\_switcher\=true/', $_SERVER['REQUEST_URI'])){ // for WP Mobile Edition
			if(preg_match('/\?amp(\=1)?/i', $_SERVER['REQUEST_URI'])){
				//
			}else if(defined('SPEEDYCACHE_CACHE_QUERYSTRING') && SPEEDYCACHE_CACHE_QUERYSTRING){
				//
			}else if(!empty($_GET['wc-api'])){
				//
			}else{
				ob_start('\SpeedyCache\Cache::cdn_rewrite');
				
				return false;
			}
		}
		
		if(!empty($_SERVER['HTTP_USER_AGENT']) && preg_match('/('.speedycache_get_excluded_useragent().')/', $_SERVER['HTTP_USER_AGENT'])){
			return false;
		}

		if(isset($_SERVER['REQUEST_URI']) && preg_match('/(\/){2}$/', speedycache_sanitize_url($_SERVER['REQUEST_URI']))){
			return false;
		}

		// to check permalink if it does not end with slash
		if(isset($_SERVER['REQUEST_URI']) && preg_match('/[^\/]+\/$/', speedycache_sanitize_url($_SERVER['REQUEST_URI'])) && !preg_match('/\/$/', get_option('permalink_structure'))){
			return false;
		}

		if(isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
			return false;
		}

		if(preg_match('/^https/i', get_option('home')) && !is_ssl()){
			//Must be secure connection
			return false;
		}

		// must be normal connection
		if(!preg_match('/^https/i', get_option('home')) && is_ssl() &&
			!speedycache_is_plugin_active('really-simple-ssl/rlrsssl-really-simple-ssl.php') &&
			!speedycache_is_plugin_active('really-simple-ssl-pro/really-simple-ssl-pro.php') &&
			!speedycache_is_plugin_active('really-simple-ssl-on-specific-pages/really-simple-ssl-on-specific-pages.php') &&
			!speedycache_is_plugin_active('ssl-insecure-content-fixer/ssl-insecure-content-fixer.php') &&
			!speedycache_is_plugin_active('https-redirection/https-redirection.php') &&
			!speedycache_is_plugin_active('better-wp-security/better-wp-security.php')){
			return false;
		}

		if(isset($_SERVER['DOCUMENT_ROOT']) && preg_match('/bitnami/', $_SERVER['DOCUMENT_ROOT'])){
			// to disable cache for the IP based urls on the bitnami servers
			// /opt/bitnami/apps/wordpress/htdocs
			if(preg_match('/(?:[0-9]{1,3}\.){3}[0-9]{1,3}/', get_option('home'))){
				return false;
			}
		}

		if(preg_match('/www\./i', get_option('home')) && !preg_match('/www\./i', sanitize_text_field($_SERVER['HTTP_HOST']))){
			return false;
		}

		if(!preg_match('/www\./i', get_option('home')) && preg_match('/www\./i', sanitize_text_field($_SERVER['HTTP_HOST']))){
			return false;
		}

		if(self::exclude_page()){
			//echo "<!-- speedycache: Exclude Page -->"."\n";
			return false;
		}

		// http://mobiledetect.net/ does not contain the following user-agents
		if(preg_match('/Nokia309|Casper_VIA/i', speedycache_optserver('HTTP_USER_AGENT'))){
			return false;
		}

		if(preg_match('/Empty\sUser\sAgent/i', speedycache_optserver('HTTP_USER_AGENT'))){ // not to show the cache for command line
			return false;
		}
		
		return true;
	}

	static function can_create(){
		if(!speedycache_is_mobile()){
			return true;
		}
		
		if(defined('SPEEDYCACHE_PRO') && !empty($speedycache->options['mobile_theme'])){
			if(!empty($speedycache->options['mobile_theme_name']) && $speedycache->options['mobile_theme_name']){
				$create_cache = true;
			}else if(speedycache_is_plugin_active('wptouch/wptouch.php') || speedycache_is_plugin_active('wptouch-pro/wptouch-pro.php')){
				//to check that user-agent exists in wp-touch's list or not
				if(self::is_wptouch_smartphone()){
					$create_cache = true;
				}else{
					$create_cache = false;
				}
			}else{
				if((preg_match('/iPhone/', speedycache_optserver('HTTP_USER_AGENT')) && preg_match('/Mobile/', speedycache_optserver('HTTP_USER_AGENT'))) || (preg_match('/Android/', speedycache_optserver('HTTP_USER_AGENT')) && preg_match('/Mobile/', speedycache_optserver('HTTP_USER_AGENT')))){
					$create_cache = true;
				}else{
					$create_cache = false;
				}
			}

			return $create_cache;
		}
		
		if(empty($speedycache->options['mobile']) && empty($speedycache->options['mobile_theme'])){
			return true;
		}

		return false;
	}
	
	static function is_admin(){
		global $wpdb;

		foreach((array)$_COOKIE as $cookie_key => $cookie_value){
			if(preg_match('/wordpress_logged_in/i', $cookie_key)){
				preg_match('/^([^\|]+)\|.+/', $cookie_value, $username);

				if(!empty($username[1])){
					break;
				}
			}
		}

		if(empty($username[1])){
			return false;
		}

		$usr = esc_sql($username[1]);
		
		$result = $wpdb->get_var("SELECT `$wpdb->users`.`ID`, `$wpdb->users`.`user_login`, `$wpdb->usermeta`.`meta_key`, `$wpdb->usermeta`.`meta_value` FROM `$wpdb->users` INNER JOIN `$wpdb->usermeta` ON `$wpdb->users`.`user_login` = \"$usr\" AND 
			`$wpdb->usermeta`.`meta_key` LIKE \"%_user_level\" AND 
		   `$wpdb->usermeta`.`meta_value` = \"10\" AND 
		   `$wpdb->users`.`ID` = `$wpdb->usermeta`.user_id ;");

		return $result;
	}

	static function print_scripts_action(){
		echo '<!--SPEEDYCACHE_FOOTER_START-->';
	}

	static function ignored($buffer){
		global $speedycache;
		
		$list = array(
			"\/wp\-comments\-post\.php",
			"\/wp\-login\.php",
			"\/robots\.txt",
			"\/wp\-cron\.php",
			"\/wp\-content",
			"\/wp\-admin",
			"\/wp\-includes",
			"\/index\.php",
			"\/xmlrpc\.php",
			"\/wp\-api\/",
			"leaflet\-geojson\.php",
			"\/clientarea\.php"
		);

		if(speedycache_is_plugin_active('woocommerce/woocommerce.php')){
			if($speedycache->settings['current_page_type'] != 'homepage'){
				global $post;

				if(isset($post->ID) && $post->ID){
					if(function_exists('wc_get_page_id')){
						$woocommerce_ids = array();

						//wc_get_page_id('product')
						//wc_get_page_id('product-category')
						
						array_push($woocommerce_ids, wc_get_page_id('cart'), wc_get_page_id('checkout'), wc_get_page_id('receipt'), wc_get_page_id('confirmation'), wc_get_page_id('myaccount'));

						if(in_array($post->ID, $woocommerce_ids)){
							return true;
						}
					}
				}

				//"\/product"
				//"\/product-category"

				array_push($list, '\/cart\/?$', '\/checkout', '\/receipt', '\/confirmation', '\/wc-api\/');
			}
		}

		if(speedycache_is_plugin_active('wp-easycart/wpeasycart.php')){
			array_push($list, '\/cart');
		}

		if(speedycache_is_plugin_active('easy-digital-downloads/easy-digital-downloads.php')){
			array_push($list, '\/cart', '\/checkout');
		}

		if(preg_match('/'.implode('|', $list).'/i', speedycache_sanitize_url($_SERVER['REQUEST_URI']))){
			return true;
		}

		return false;
	}

	static function exclude_page($buffer = false){
		global $speedycache;
		
		$preg_match_rule = '';
		$request_url = !empty($_SERVER['REQUEST_URI']) ? speedycache_sanitize_url(urldecode(trim($_SERVER['REQUEST_URI'], '/'))) : '';

		if(empty($speedycache->settings['exclude_rules'])){
			return false;
		}

		foreach((array)$speedycache->settings['exclude_rules'] as $key => $value){
			$value['type'] = isset($value['type']) ? $value['type'] : 'page';

			if($value['prefix'] == 'googleanalytics'){
				if(preg_match('/utm_(source|medium|campaign|content|term)/i', $request_url)){
					return true;
				}
			}else if(isset($value['prefix']) && $value['prefix'] && ($value['type'] == 'page')){
				$value['content'] = trim($value['content']);
				$value['content'] = trim($value['content'], '/');

				if($buffer && preg_match('/^(homepage|category|tag|post|page|archive|attachment)$/', $value['prefix'])){
					if(preg_match('/<\!--SPEEDYCACHE_PAGE_TYPE_'.$value['prefix'].'-->/i', $buffer)){
						return true;
					}
				}else if($value['prefix'] == 'exact'){
					if(strtolower($value['content']) == strtolower($request_url)){
						return true;
					}
				}else{
					if($value['prefix'] == 'startwith'){
						$preg_match_rule = '^'.preg_quote($value['content'], '/');
					}else if($value['prefix'] == 'contain'){
						$preg_match_rule = preg_quote($value['content'], '/');
					}

					if($preg_match_rule){
						if(preg_match('/'.$preg_match_rule.'/i', $request_url)){
							return true;
						}
					}
				}
			}else if($value['type'] == 'useragent'){
				if(preg_match('/'.preg_quote($value['content'], '/').'/i', speedycache_optserver('HTTP_USER_AGENT'))){
					return true;
				}
			}else if($value['type'] == 'cookie'){
				if(isset($_SERVER['HTTP_COOKIE'])){
					if(preg_match('/'.preg_quote($value['content'], '/').'/i', sanitize_key($_SERVER['HTTP_COOKIE']))){
						return true;
					}
				}
			}
		}
		
		return false;
	}

	static function set_content_type($buffer){
		global $speedycache;
		
		$content_type = false;
		if(function_exists('headers_list')){
			$headers = headers_list();
			foreach($headers as $header){
				if(preg_match('/Content-Type\:/i', $header)){
					$content_type = preg_replace("/Content-Type\:\s(.+)/i", "$1", $header);
				}
			}
		}

		if(preg_match('/xml/i', $content_type)){
			$speedycache->settings['cur_content_type'] = 'xml';
		}else if(preg_match('/json/i', $content_type)){
			$speedycache->settings['cur_content_type'] = 'json';
		}else{
			$speedycache->settings['cur_content_type'] = 'html';
		}
	}

	static function last_error($buffer = false){
		if(function_exists('http_response_code') && (http_response_code() === 404)){
			return true;
		}

		if(is_404()){
			return true;
		}

		if(preg_match("/<body id\=\"error-page\">\s*<p>[^\>]+<\/p>\s*<\/body>/i", $buffer)){
			return true;
		}
	}

	static function callback($buffer){
		global $speedycache;

		preg_match('/<\!--SPEEDYCACHE_PAGE_TYPE_([a-z]+)-->/i', $buffer, $out);
		$speedycache->settings['current_page_type'] = isset($out[1]) ? $out[1] : false;
		
		self::set_content_type($buffer);

		// for Wordfence: not to cache 503 pages
		if(defined('DONOTCACHEPAGE') && speedycache_is_plugin_active('wordfence/wordfence.php')){
			if(function_exists('http_response_code') && http_response_code() == 503){
				return $buffer.'<!-- DONOTCACHEPAGE is defined as TRUE -->';
			}
		}

		// for iThemes Security: not to cache 403 pages
		if(defined('DONOTCACHEPAGE') && speedycache_is_plugin_active('better-wp-security/better-wp-security.php')){
			if(function_exists('http_response_code') && http_response_code() == 403){
				return $buffer.'<!-- DONOTCACHEPAGE is defined as TRUE -->';
			}
		}

		// for Divi Theme
		if(defined('DONOTCACHEPAGE') && (get_template() == 'Divi')){
			return $buffer.'<!-- DONOTCACHEPAGE is defined as TRUE -->';
		}

		if(self::exclude_page($buffer)){
			$buffer = preg_replace('/<\!--SPEEDYCACHE_PAGE_TYPE_[a-z]+-->/i', '', $buffer);	
			return $buffer;
		}

		$buffer = preg_replace('/<\!--SPEEDYCACHE_PAGE_TYPE_[a-z]+-->/i', '', $buffer);
		
		$res = self::is_cacheable($buffer);

		if($res !== FALSE){
			return $res; 
		}
		
		// Copying just so we can output a clean HTML, in case there is an error while caching
		$content = $buffer;
		
		// Removing Google Fonts
		if(!empty($speedycache->bloat['remove_gfonts'])){
			$content = preg_replace('/<link[^<>]*\/\/fonts\.(googleapis|google|gstatic)\.com[^<>]*>/i', '', $content);
		}
		
		// Adds Image dimensions to the Image which does not have height or width
		if(!empty($speedycache->options['image_dimensions'])){
			$content = \SpeedyCache\Enhanced::image_dimensions($content);
		}

		// Google Fonts
		if(defined('SPEEDYCACHE_PRO') && !empty($speedycache->options['local_gfonts'])){
			\SpeedyCache\GoogleFonts::get($content);
			$content = \SpeedyCache\GoogleFonts::replace($content);
		}
		
		if(!empty($speedycache->options['font_rendering'])){
			$content = str_replace('</head>', '<style>body{text-rendering: optimizeSpeed;}</style></head>', $content);
		}

		$post_meta = get_post_meta(get_the_ID(), 'speedycache_post_meta', true);

		// Critical CSS
		if(!empty($speedycache->options['critical_css']) && !empty($_SERVER['REQUEST_URI']) && !isset($post_meta['disable_critical_css']) && (!empty($_GET['test_speedycache']) && strpos($content, 'speedycache-generated-criticalcss') === FALSE)){
			$page_url = home_url(speedycache_optserver('REQUEST_URI'));
			
			$ccss_path = speedycache_cache_path('critical_css/');
			$ccss_file = $ccss_path . md5($page_url) . '.php';
			
			
			if(file_exists($ccss_file)){
				$css = file_get_contents($ccss_file);
				$css = str_replace("<?php exit();?>\n", '', $css);
				$css = '<style id="speedycache-generated-criticalcss">'. "\n". wp_strip_all_tags($css) . '</style>';

				$content = \SpeedyCache\CriticalCss::update_content($content, $css);
			}
		}
		
		// Render Blocking JS
		if(!empty($speedycache->options['render_blocking']) && defined('SPEEDYCACHE_PRO') && !self::is_amp($content)){
			\SpeedyCache\Enhanced::init();
			
			$render_blocking = false;
			if(!empty($speedycache->options['render_blocking_css'])){
				$render_blocking = true;
			}
		
			$content = \SpeedyCache\Enhanced::render_blocking($content, $render_blocking);
		}

		if(!empty($speedycache->options['combine_css'])){		
			\SpeedyCache\CSS::extract($content);
			$content = \SpeedyCache\CSS::combine_css();
			//unset($css);
		}else if(!empty($speedycache->options['minify_css'])){		
			\SpeedyCache\CSS::extract($content);
			$content = \SpeedyCache\CSS::minify_css();
			//unset($css);
		}

		if(!empty($speedycache->options['combine_js'])){
			
			$head_first_index = strpos($content, '<head');
			$head_last_index = strpos($content, '</head>');
			$head_new = substr($content, $head_first_index, ($head_last_index - $head_first_index + 1)); // Getting the content of <head>

			if(!empty($head_new)){
				if(!empty($speedycache->options['minify_js'])){
					\SpeedyCache\JS::init($head_new, true);
				}else{
					\SpeedyCache\JS::init($head_new);
				}

				$tmp_head = \SpeedyCache\JS::combine();

				$content = str_replace($head_new, $tmp_head, $content);
				
				//unset($r);
				//unset($js);
				unset($tmp_head);
				unset($head_new);
			}
		}

		if(defined('SPEEDYCACHE_PRO')){
			
			if(!empty($speedycache->options['combine_js_enhanced']) || !empty($speedycache->options['remove_comments']) || !empty($speedycache->options['minify_html']) || !empty($speedycache->options['minify_js']) || !empty($speedycache->options['delay_js'])){
				\SpeedyCache\Enhanced::init();
				\SpeedyCache\Enhanced::set_html($content);
			}

			if(!empty($speedycache->options['combine_js_enhanced'])){
				if(!empty($speedycache->options['minify_js'])){
					$content = \SpeedyCache\Enhanced::combine_js_in_footer(true);
				}else{
					$content = \SpeedyCache\Enhanced::combine_js_in_footer();
				}
			}

			if(!empty($speedycache->options['remove_comments'])){
				$content = \SpeedyCache\Enhanced::remove_head_comments();
			}

			if(!empty($speedycache->options['minify_html'])){
				$content = \SpeedyCache\Enhanced::minify_html();
			}

			if(!empty($speedycache->options['minify_js'])){
				$content = \SpeedyCache\Enhanced::minify_js_in_body($speedycache->settings['exclude_rules']);
			}

			// Delay JS
			if(!empty($speedycache->options['delay_js'])){
				if(empty($speedycache->enhanced)){
					$speedycache->enhanced['html'] = $content;
				}

				$content = \SpeedyCache\Enhanced::delay_js($content);
			}

			// Preload Critical Images
			if(!empty($speedycache->options['critical_images'])){
				$content = \SpeedyCache\Enhanced::preload_critical_images($content);
			}

			// Lazy Load HTML elements
			if(!empty($speedycache->options['lazy_load_html']) && !empty($speedycache->options['lazy_load_html_elements'])){
				$content = \SpeedyCache\Enhanced::lazy_load_html($content);
			}
		}

		if(!empty($speedycache->settings['err'])){
			return $buffer.'<!-- '.$speedycache->settings['err'].' -->';
		}
		
		$content = self::cache_date($content);
		$content = str_replace('<!--SPEEDYCACHE_FOOTER_START-->', '', $content);

		if(defined('SPEEDYCACHE_PRO_DIR') && !empty($speedycache->options['lazy_load']) && file_exists(SPEEDYCACHE_PRO_DIR . '/main/enhanced.php')){
			$execute_lazy_load = true;
			
			// to disable Lazy Load if the page is amp
			if(self::is_amp($content)){
				$execute_lazy_load = false;
			}
			
			// to disable for Ajax Load More on the pages
			if(speedycache_is_plugin_active('ajax-load-more/ajax-load-more.php') && preg_match("/\/page\/\d+\//", speedycache_sanitize_url($_SERVER['REQUEST_URI']))){
				$execute_lazy_load = false;
			}

			if(!empty($execute_lazy_load)){

				$content = \SpeedyCache\Enhanced::lazy_load($content);
				$lazy_load_js = '';
				
				if(file_exists(SPEEDYCACHE_PRO_DIR . '/main/lazyload.php')){
					$lazy_load_js = \SpeedyCache\LazyLoad::get_js_source();
				}
				
				$content = preg_replace("/\s*<\/head\s*>/i", $lazy_load_js.'</head>', $content, 1);
			}
		}

		$content = self::cdn_rewrite($content);
		$content = self::fix_pre_tag($content, $buffer);

		if(defined('SPEEDYCACHE_PRO') && !empty($speedycache->options['display_swap'])){
			$content = \SpeedyCache\GoogleFonts::add_swap($content);
		}

		if(empty($speedycache->settings['cache_file_path'])){
			return $content.'<!-- refresh to see cached version -->';
		}
		
		if($speedycache->settings['cur_content_type'] == 'html'){
			\SpeedyCache\Cache::create_dir($speedycache->settings['cache_file_path'], $content);
			
			if(!empty($speedycache->options['gzip']) && function_exists('gzencode')){
				\SpeedyCache\Cache::create_dir($speedycache->settings['cache_file_path'], gzencode($content, 6), 'html.gz');
			}
			
			$post_meta = get_post_meta(get_the_ID(), 'speedycache_post_meta', true);

			// Scheduling Unused CSS
			if(empty($post_meta['disable_unused_css']) && !empty($speedycache->options['unused_css']) && !empty($_SERVER['HTTP_USER_AGENT']) && speedycache_optserver('HTTP_USER_AGENT') !== 'SpeedyCacheCCSS' && !empty($_SERVER['REQUEST_URI']) && !empty($_SERVER['HTTP_HOST'])){
				$url = esc_url(speedycache_optserver('HTTP_HOST'). speedycache_optserver('REQUEST_URI'));

				if(strpos($url, '?test_speedycache') !== FALSE){
					\SpeedyCache\UnusedCss::generate(array($url));
				} else {
					\SpeedyCache\UnusedCss::schedule('speedycache_unused_css', array($url));
				}
			}
			
			// Scheduling Critical CSS
			if(empty($post_meta['disable_critical_css']) && !empty($speedycache->options['critical_css']) && !empty($_SERVER['HTTP_USER_AGENT']) && speedycache_optserver('HTTP_USER_AGENT') !== 'SpeedyCacheCCSS' && !empty($_SERVER['REQUEST_URI']) && !empty($_SERVER['HTTP_HOST'])){
				$url = esc_url(speedycache_optserver('HTTP_HOST'). speedycache_optserver('REQUEST_URI'));

				if(strpos($url, '?test_speedycache') !== FALSE){
					\SpeedyCache\CriticalCss::generate(array($url));
				} else {
					\SpeedyCache\CriticalCss::schedule('speedycache_generate_ccss', array($url));
				}
			}

			do_action('speedycache_is_cacheable_action');
		}else if($speedycache->settings['cur_content_type'] == 'xml'){
			if(preg_match('/<link><\/link>/', $buffer)){
				if(preg_match('/\/feed$/', speedycache_sanitize_url($_SERVER['REQUEST_URI']))){
					return $buffer.time();
				}
			}

			\SpeedyCache\Cache::create_dir($speedycache->settings['cache_file_path'], $buffer, 'xml');
			do_action('speedycache_is_cacheable_action');

			return $buffer;
		}else if($speedycache->settings['cur_content_type'] == 'json'){
			\SpeedyCache\Cache::create_dir($speedycache->settings['cache_file_path'], $buffer, 'json');
			do_action('speedycache_is_cacheable_action');

			return $buffer;
		}

		return $content.'<!-- refresh to see cached version -->';
	}

	static function fix_pre_tag($content, $buffer){
		if(!preg_match('/<pre[^\>]*>/i', $buffer)){
			return $content;
		}
		
		preg_match_all('/<pre[^\>]*>((?!<\/pre>).)+<\/pre>/is', $buffer, $pre_buffer);
		preg_match_all('/<pre[^\>]*>((?!<\/pre>).)+<\/pre>/is', $content, $pre_content);

		if(empty($pre_content[0]) || empty($pre_content[0][0])){
			return $content;
		}
		
		foreach($pre_content[0] as $key => $value){
			if(empty($pre_buffer[0][$key])){
				continue;
			}

			/*
			location ~ / {
				set $path /path/$1/index.html;
			}
			*/
			$pre_buffer[0][$key] = preg_replace('/\$(\d)/', '\\\$$1', $pre_buffer[0][$key]);

			/*
			\\\
			*/
			$pre_buffer[0][$key] = preg_replace('/\\\\\\\\\\\/', '\\\\\\\\\\\\\\', $pre_buffer[0][$key]);

			/*
			\\
			*/
			$pre_buffer[0][$key] = preg_replace('/\\\\\\\\/', '\\\\\\\\\\', $pre_buffer[0][$key]);

			$content = preg_replace('/'.preg_quote($value, '/').'/', $pre_buffer[0][$key], $content);
		}
		
		return $content;
	}

	static function cdn_rewrite($content){
		global $speedycache;
		
		
		
		$cdn_pregs = array(
			'/(srcset|src|href|data-vc-parallax-image|data-bg|data-bg-webp|data-fullurl|data-mobileurl|data-img-url|data-cvpsrc|data-cvpset|data-thumb|data-bg-url|data-large_image|data-lazyload|data-lazy|data-source-url|data-srcsmall|data-srclarge|data-srcfull|data-slide-img|data-lazy-original)\s{0,2}\=\s{0,2}[\'\"]([^\'\"]+)[\'\"]/i',
			
			//regex for url() used in css
			'/(url)\(([^\)\>]+)\)/i',
			
			//{"concatemoji":"http:\/\/your_url.com\/".WPINC."\/js\/wp-emoji-release.min.js?ver=4.7"}
			'/\{\"concatemoji\"\:\"[^\"]+\"\}/i',
			
			//<script>var loaderRandomImages=["https:\/\/www.site.com\/site-data\/uploads\/2016\/12\/image.jpg"];</script>
			'/[\"\']([^\'\"]+)[\"\']\s*\:\s*[\"\']https?\:\\\\\/\\\\\/[^\"\']+[\"\']/i',
			
			// <script>
			// jsFileLocation:"//domain.com/site-data/plugins/revslider/public/assets/js/"
			// </script>
			'/(jsFileLocation)\s*\:[\"\']([^\"\']+)[\"\']/i',
			
			
			// <form data-product_variations="[{&quot;src&quot;:&quot;//domain.com\/img.jpg&quot;}]">
			// <div data-siteorigin-parallax="{&quot;backgroundUrl&quot;:&quot;https:\/\/domain.com\/site-data\/TOR.jpg&quot;,&quot;backgroundSize&quot;:[830,467],&quot;}" data-stretch-type="full">
			'/(data-product_variations|data-siteorigin-parallax)\=[\"\'][^\"\']+[\"\']/i',
			
			// <object data="https://site.com/source.swf" type="application/x-shockwave-flash"></object>
			'/<object[^\>]+(data)\s{0,2}\=[\'\"]([^\'\"]+)[\'\"][^\>]+>/i'
		);
		
		if(!empty($speedycache->settings['cdn'])){
			foreach($cdn_pregs as $preg){
				$content = preg_replace_callback($preg, '\SpeedyCache\CDN::replace_urls', $content);
			}
		}
		
		foreach($speedycache->settings['cdn'] as $key => $cdn){
			
			if(isset($cdn['status']) && $cdn['status'] == 'pause'){
				continue;
			}
			
			if($cdn['id'] == 'cloudflare'){
				continue;
			}

			$url = esc_url_raw($cdn['cdn_url']);

			if(empty($url)){
				break;
			}

			$preconnect_cdn = '<link rel="preconnect" href="'.$url.'"/>';
			
			$content = str_replace('<title>', $preconnect_cdn . '<title>', $content);
			break;
		}

		return $content;
	}

	static function check_html($buffer){
		global $speedycache;

		if($speedycache->settings['cur_content_type'] != 'html'){
			return false;
		}

		if(preg_match('/<html[^\>]*>/si', $buffer) && preg_match('/<body[^\>]*>/si', $buffer) && preg_match('/<\/body>/si', $buffer)){
			return false;
		}

		return true;
	}

	static function cache_date($buffer){
		global $speedycache;
		
		$comment = '<!-- Page Cached by SpeedyCache, took '.(microtime(true) - $speedycache->start_time).' seconds to cache, on '.date('d-m-y G:i:s', current_time('timestamp')).' -->';
		if(speedycache_is_mobile() && defined('SPEEDYCACHE_PRO_DIR') && !empty($speedycache->options['mobile']) && !empty($speedycache->options['mobile_theme'])){
			$comment = '<!-- Mobile: Page Cached by SpeedyCache, took '.(microtime(true) - $speedycache->start_time).' seconds to cache, on '.date('d-m-y G:i:s', current_time('timestamp')).' -->';
		}

		if(defined('SPEEDYCACHE_REMOVE_FOOTER_COMMENT') && SPEEDYCACHE_REMOVE_FOOTER_COMMENT){
			return $buffer;
		}
		
		return $buffer.$comment;
	}

	static function is_commenter(){
		$commenter = wp_get_current_commenter();
		return isset($commenter['comment_author_email']) && $commenter['comment_author_email'] ? true : false;
	}

	static function is_password_protected($buffer){
		if(preg_match("/action\=[\'\"].+postpass.*[\'\"]/", $buffer)){
			return true;
		}

		foreach($_COOKIE as $key => $value){
			if(preg_match("/wp\-postpass\_/", $key)){
				return true;
			}
		}

		return false;
	}

	static function create_name($list){
		$arr = is_array($list) ? $list : array(array('href' => $list));
		$name = '';
		
		foreach($arr as $tag_key => $tag_value){
			$tmp = preg_replace("/(\.css|\.js)\?.*/", "$1", $tag_value['href']); //to remove version number
			$name = $name.$tmp;
		}
		
		return base_convert(crc32($name), 20, 36);
	}

	static function create_dir($cach_file_path, $buffer, $extension = 'html', $prefix = false){
		global $speedycache;

		$create = false;
		$file_name = 'index.';
		$update_db_statistic = true;
		
		if($buffer && strlen($buffer) > 100 && preg_match('/html\.gz|html|xml|json/i', $extension)){
			if(!preg_match("/^\<\!\-\-\sMobile\:\sSpeedyCache/i", $buffer)){
				if(!preg_match("/^\<\!\-\-\sSpeedyCache/i", $buffer)){
					$create = true;
				}
			}

			if($speedycache->settings['preload_user_agent']){
				if(file_exists($cach_file_path.'/'.'index.'.$extension)){
					$update_db_statistic = false;
					@unlink($cach_file_path.'/'.'index.'.$extension);
				}
			}
		}

		if(($extension == 'svg' || $extension == 'woff' || $extension == 'css' || $extension == 'js') && $buffer && strlen($buffer) > 5){
			$create = true;
			$file_name = base_convert(substr(time(), -6), 20, 36).'.';
			$buffer = trim($buffer);

			if($extension == 'js'){
				if(substr($buffer, -1) != ';'){
					$buffer .= ';';
				}
			}
		}
		
		if(empty($create)){
			if($extension == 'html' || $extension == 'html.gz'){
				$speedycache->settings['err'] = esc_html__('Buffer is empty so the cache cannot be created', 'speedycache');
				return;
			}
			
			return;
		}

		if(is_user_logged_in() || self::is_commenter()){
			return;
		}

		if(is_dir($cach_file_path)){

			if(!file_exists($cach_file_path.'/'.$file_name.$extension)){

				$buffer = (string) apply_filters('speedycache_buffer_callback_filter', $buffer, $extension);

				file_put_contents($cach_file_path.'/'.$file_name.$extension, $buffer);
				
				if(defined('SPEEDYCACHE_PRO') && file_exists(SPEEDYCACHE_PRO_DIR . '/main/statistics.php')){

					if(!empty($update_db_statistic) && !preg_match('/After\sCache\sTimeout/i', speedycache_optserver('HTTP_USER_AGENT'))){
						$type = $extension;
						if(preg_match('/speedycache\/'.preg_quote(SPEEDYCACHE_SERVER_HOST).'\/mobile-cache/', $cach_file_path)){
							$type = 'mobile';
						}

						\SpeedyCache\Statistics::init($type, strlen($buffer));
						\SpeedyCache\Statistics::update_db();
					}
				}
			}
		}
		
		if(!is_writable(speedycache_get_wp_content_dir())){
			return;
		}
		
		if(!is_dir(speedycache_cache_path()) || !is_writable(speedycache_cache_path())){
			return;
		}
		
		if(file_exists($cach_file_path) || @mkdir($cach_file_path, 0755, true)){
			$buffer = (string) apply_filters('speedycache_buffer_callback_filter', $buffer, $extension);

			file_put_contents($cach_file_path . '/' . $file_name . $extension, $buffer);

			if(defined('SPEEDYCACHE_PRO') && file_exists(SPEEDYCACHE_PRO_DIR . '/main/statistics.php')){
				if($update_db_statistic && !preg_match('/After\sCache\sTimeout/i', speedycache_optserver('HTTP_USER_AGENT'))){
					$type = $extension;
					if(preg_match('/speedycache\/'.preg_quote(SPEEDYCACHE_SERVER_HOST).'\/mobile-cache/', $cach_file_path)){
						$type = 'mobile';
					}
					
				   \SpeedyCache\Statistics::init($type, strlen($buffer));
				   \SpeedyCache\Statistics::update_db();
				}
			}

			if('html' === $extension && !file_exists(speedycache_cache_path('index.html'))){
				@file_put_contents(speedycache_cache_path('index.html'), '');
				return;
			}

			if(!file_exists(speedycache_cache_path('assets/'))){
				@mkdir(speedycache_cache_path('assets'));
			}

			if(!file_exists(speedycache_cache_path('assets/index.html'))){
				@file_put_contents(speedycache_cache_path('assets/index.html'), '');
			}
		}
		
	}

	static function is_amp($content){
		global $redux_builder_amp;
		
		$action = false;
		$request_uri = speedycache_sanitize_url(trim($_SERVER['REQUEST_URI'], '/'));

		if(preg_match('/^amp/', $request_uri)){
			$action = true;
		}

		if(preg_match('/amp$/', $request_uri)){
			$action = true;
		}

		if(preg_match("/\/amp\//", $request_uri)){
			$action = true;
		}

		if(isset($redux_builder_amp) && isset($redux_builder_amp['ampforwp-amp-takeover']) && ($redux_builder_amp['ampforwp-amp-takeover'] == true)){
			$action = true;
		}

		if(!empty($action)){
			if(preg_match("/<html[^\>]+amp[^\>]*>/i", $content)){
				return true;
			}

			if(preg_match("/<html[^\>]+\⚡[^\>]*>/i", $content)){
				return true;
			}
		}

		return false;
	}

	static function is_wp_login($buffer){
		$login_page = defined('SITEPAD') ?  'login.php' : 'wp-login.php';
		
		$login_page = apply_filters('speedycache_is_wp_login', $login_page);
		
		if(!empty($GLOBALS['pagenow']) && !empty($login_page) && $GLOBALS['pagenow'] == $login_page){
			return true;
		}

		return false;
	}

	static function is_wptouch_smartphone(){
		// wptouch: ipad is accepted as a desktop so no need to create cache if user agent is ipad
		if(preg_match('/ipad/i', speedycache_optserver('HTTP_USER_AGENT'))){
			return false;
		}

		$smartphone_list = array(
			'iPhone', 'Android:Mobile', 'BB:Mobile Safari', 'BlackBerry:Mobile Safari', 'Firefox:Mobile', 'IEMobile/11:Touch', 'IEMobile/10:Touch', 'IEMobile/9.0', 'IEMobile/8.0', 'IEMobile/7.0', 'OPiOS:Mobile', 'Coast:Mobile');

		foreach($smartphone_list as $value){
			if(strpos($value, ':') !== FALSE){
				$value = explode($value, ':');
			}

			if(isset($value[0]) && isset($value[1])){
				if(preg_match('/'.preg_quote($value[0], '/').'/i', speedycache_optserver('HTTP_USER_AGENT'))){
					if(preg_match('/'.preg_quote($value[1], '/').'/i', speedycache_optserver('HTTP_USER_AGENT'))){
						return true;
					}
				}
			}else if(isset($value[0])){
				if(preg_match('/'.preg_quote($value[0], '/').'/i', speedycache_optserver('HTTP_USER_AGENT'))){
					return true;
				}
			}
		}

		return false;
	}
	
	static function disable_emojis(){
		
		// remove the DNS prefetch
		add_filter('emoji_svg_url', '__return_false');
		remove_action('wp_head', 'print_emoji_detection_script', 7);
		
		$filters = array('the_content_feed' => 'wp_staticize_emoji', 'comment_text_rss' => 'wp_staticize_emoji', 'wp_mail' => 'wp_staticize_emoji_for_email');
		
		foreach($filters as $hook => $filter){
			remove_filter($hook, $filter);
		}
		
		$actions = array('admin_print_scripts' => 'print_emoji_detection_script', 'wp_print_styles' => 'print_emoji_styles', 'admin_print_styles' => 'print_emoji_styles');
		
		foreach($actions as $hook => $action){
			remove_action($hook, $action);
		}
	}

	static function detect_current_page_type(){
		if(!empty($_SERVER['REQUEST_URI']) && preg_match('/^\/wp-json|\?/', speedycache_sanitize_url($_SERVER['REQUEST_URI']))){
			return true;
		}

		if(is_front_page()){
			echo '<!--SPEEDYCACHE_PAGE_TYPE_homepage-->';
		}
		
		if(is_category()){
			echo '<!--SPEEDYCACHE_PAGE_TYPE_category-->';
		}
		
		if(is_tag()){
			echo '<!--SPEEDYCACHE_PAGE_TYPE_tag-->';
		}
		
		if(is_singular('post')){
			echo '<!--SPEEDYCACHE_PAGE_TYPE_post-->';
		}
		
		if(is_page()){
			echo '<!--SPEEDYCACHE_PAGE_TYPE_page-->';
		}
		
		if(is_attachment()){
			echo '<!--SPEEDYCACHE_PAGE_TYPE_attachment-->';
		}
		
		if(is_archive()){
			echo '<!--SPEEDYCACHE_PAGE_TYPE_archive-->';
		}
	}

	// Checks if we can proceed too create a cache
	static function is_cacheable($buffer){
		global $speedycache;
		
		$post_type = get_post_type();
		if(!in_array($post_type, $speedycache->options['post_types'])){
			return $buffer;
		}

		if($speedycache->settings['exclude_current_page_text']){
			return $buffer.$speedycache->settings['exclude_current_page_text'];
		}
		
		if($speedycache->settings['cur_content_type'] == 'json'){
			return $buffer;
		}
		
		if(preg_match('/Mediapartners-Google|Google\sWireless\sTranscoder/i', speedycache_optserver('HTTP_USER_AGENT'))){
			return $buffer;
		}
		
		if(is_user_logged_in() || self::is_commenter()){
			return $buffer;
		}
		
		if(self::is_password_protected($buffer)){
			return $buffer.'<!-- Password protected content has been detected -->';
		}
		
		if(self::is_wp_login($buffer)){
			return $buffer.'<!-- '.(defined('SITEPAD') ? 'login.php' : 'wp-login.php'). '-->';
		}
		
		// Contact form 7 captcha
		if(is_single() && is_page() && preg_match("/<input[^\>]+_wpcf7_captcha[^\>]+>/i", $buffer)){
			return $buffer.'<!-- This page was not cached because ContactForm7\'s captcha -->';
		}
		
		if(self::check_html($buffer)){
			return $buffer.'<!-- html is corrupted -->';
		}
		
		if(self::last_error($buffer)){
			return $buffer;
		}
		
		if(self::ignored($buffer)){
			return $buffer;
		}

		$post_id = get_the_ID();
		if(!empty($post_id)){
			$post_meta = get_post_meta($post_id, 'speedycache_post_meta', true);
			
			if(!empty($post_meta['disable_cache'])){
				return $buffer . '<!-- SpeedyCache: Cache has been disabled -->';
			}
		}

		// Check if it's a preview page of popular page builders
		$preview_queries = [
			'pagelayer-live',
			'preview', // WordPress
			'elementor-preview',
			'et_fb', // Divi
			'fl_builder', // Beaver Builder
			'ff_preview', // Beaver Themer
			'brizy_edit',
			'bricks',
			'vc_inline', // Visual Composer
			'vc_editable', // Visual Composer
			'tve', // Thrive Architect
			'fusion_builder', // Fusion Builder
			'ct_builder', // Oxygen Builder
			'stackable', // Stackable
			'cornerstone', // Cornerstone
			'breakdance', // Breakdance
			'generate_page_builder', // GeneratePress
			'siteorigin_panels_live_editor',
		];

		foreach($preview_queries as $pq){
			if(isset($_GET[$pq])){
				return $buffer.'<!-- not cached -->';
			}
		}
		
		if((function_exists('http_response_code')) && (http_response_code() == 301 || http_response_code() == 302)){
			return $buffer;
		}

		if(!$speedycache->settings['cache_file_path']){
			return $buffer.'<!-- permalink_structure ends with slash (/) but REQUEST_URI does not end with slash (/) -->';
		}
		
		return false;
	}

}

<?php
/*
* SPEEDYCACHE
* https://speedycache.com/
* (c) SpeedyCache Team
*/

namespace SpeedyCache;

// HTACCESS modification
class htaccess{

	static function modify(){
		$path = speedycache_get_abspath();
		
		if(!file_exists($path . '.htaccess')){
			if(!empty(speedycache_optserver('SERVER_SOFTWARE')) && (preg_match('/iis/i', speedycache_optserver('SERVER_SOFTWARE')) || preg_match('/nginx/i', speedycache_optserver('SERVER_SOFTWARE')))){
				//
			} else {
				return array('<label>.htaccess was not found</label>', 'error');
			}
		}

		if(speedycache_is_plugin_active('wp-postviews/wp-postviews.php')){
			$wp_postviews_options = get_option('views_options');
			$wp_postviews_options['use_ajax'] = true;
			update_option('views_options', $wp_postviews_options);

			if(!WP_CACHE){
				$wp_config = @file_get_contents(ABSPATH . 'wp-config.php');
				
				if(!empty($wp_config)){
					$wp_config = str_replace('\$table_prefix', "define('WP_CACHE', true);\n\$table_prefix", $wp_config);

					if(!@file_put_contents(ABSPATH . 'wp-config.php', $wp_config)){
						return array("define('WP_CACHE', true); is needed to be added into wp-config.php", 'error');
					}
				}
				
				return array("define('WP_CACHE', true); is needed to be added into wp-config.php", 'error');
			}
		}

		$htaccess = @file_get_contents($path . '.htaccess');

		if(!get_option('permalink_structure')){
			return array('You have to set <strong><u><a target="_blank" href="https://wordpress.org/support/article/settings-permalinks-screen/">permalinks</a></u></strong>', 'error');
		}elseif($res = self::check_super_cache($path, $htaccess)){
			return $res;
		}
		
		if((speedycache_is_plugin_active('adrotate/adrotate.php') || speedycache_is_plugin_active('adrotate-pro/adrotate.php'))){
			return self::warning_incompatible('AdRotate');
		}

		if(speedycache_is_plugin_active('mobilepress/mobilepress.php')){
			return self::warning_incompatible('MobilePress', array('name' => 'WPtouch Mobile', 'url' => 'https://wordpress.org/plugins/wptouch/'));
		}
		
		$plugins = array(
			'wp-fastest-cache/wpFastestCache.php'=> __('WP Fastest Cache Plugin needs to be deactivated', 'speedycache'),
			'sg-cachepress/sg-cachepress.php' => __('SG Optimizer needs to be deactived', 'speedycache'),
			'gzip-ninja-speed-compression/gzip-ninja-speed.php' => __('GZip Ninja Speed Compression needs to be deactivated<br>This plugin has aldready Gzip feature', 'speedycache'),
			'fast-velocity-minify/fvm.php' => __('Fast Velocity Minify needs to be deactivated', 'speedycache'),
			'filosofo-gzip-compression/filosofo-gzip-compression.php' => __('GZIP Output needs to be deactivated<br>This plugin has aldready Gzip feature', 'speedycache'),
			'far-future-expiration/far-future-expiration.php' => __('Far Future Expiration Plugin needs to be deactivated', 'speedycache'),
			'wordpress-gzip-compression/ezgz.php' => __('WordPress Gzip Compression needs to be deactivated<br>This plugin has aldready Gzip feature', 'speedycache'),
			'wp-performance-score-booster/wp-performance-score-booster.php' => __('WP Performance Score Booster needs to be deactivated<br>This plugin has aldready Gzip, Leverage Browser Caching features', 'speedycache'),
			'far-future-expiry-header/far-future-expiration.php' => __('Far Future Expiration Plugin needs to be deactivated', 'speedycache'),
			'bwp-minify/bwp-minify.php' => __('Better WordPress Minify needs to be deactivated<br>This plugin has aldready Minify feature', 'speedycache'),
			'check-and-enable-gzip-compression/richards-toolbox.php' => __('Check and Enable GZIP compression needs to be deactivated<br>This plugin has aldready Gzip feature', 'speedycache'),
			'gzippy/gzippy.php' => __('GZippy needs to be deactivated<br>This plugin has aldready Gzip feature', 'speedycache'),
			'speed-booster-pack/speed-booster-pack.php' => __('Speed Booster Pack needs to be deactivated', 'speedycache'),
			'cdn-enabler/cdn-enabler.php' => __('CDN Enabler needs to be deactivated<br>This plugin has aldready CDN feature', 'speedycache'),
			'head-cleaner/head-cleaner.php' => __('Head Cleaner needs to be deactivated', 'speedycache'),
		);

		foreach($plugins as $_path => $error_str){
			if(speedycache_is_plugin_active($_path)){
				return array($error_str, 'error');
			}
		}
		
		if(is_writable($path . '.htaccess')){
			$htaccess = self::webp($htaccess);
			$htaccess = self::browser_cache($htaccess);
			$htaccess = self::gzip($htaccess);
			$htaccess = self::rewrite_rule($htaccess);
			$htaccess = self::gtranslate_rules($htaccess);
			file_put_contents($path . '.htaccess', $htaccess);
		}
		
		return array(__('Options have been saved', 'speedycache'), 'updated');
	}

	static function gtranslate_rules($htaccess){
		
		preg_match("/\#\#\#\s+BEGIN\sGTranslate\sconfig\s\#\#\#[^\#]+\#\#\#\s+END\sGTranslate\sconfig\s\#\#\#/i", $htaccess, $gtranslate);

		if(isset($gtranslate[0])){
			$htaccess = preg_replace("/\#\#\#\s+BEGIN\sGTranslate\sconfig\s\#\#\#[^\#]+\#\#\#\s+END\sGTranslate\sconfig\s\#\#\#/i", '', $htaccess);
			$htaccess = $gtranslate[0] . "\n" . $htaccess;
		}

		return $htaccess;
	}
	
	static function get(){
		
		global $speedycache;

		$mobile = '';
		$logged_in_user = '';
		$is_not_secure = '';
		$trailing_slash_rule = '';
		$consent_cookie = '';
		$language_negotiation_type = apply_filters('wpml_setting', false, 'language_negotiation_type');
		
		$cache_path = speedycache_cache_path('all/');
		$cache_path = preg_replace('/.*'.preg_quote(SPEEDYCACHE_WP_CONTENT_DIR).'/', '', $cache_path);
		
		if(($language_negotiation_type == 2) && speedycache_is_plugin_active('sitepress-multilingual-cms/sitepress.php')){
			$disable_condition = true;
		} else {
			$disable_condition = false;
		}

		if(!empty($speedycache->options['mobile'])){
			$mobile = "RewriteCond %{HTTP_USER_AGENT} !^.*(" . speedycache_get_mobile_user_agents() . ").*$ [NC]" . "\n";

			if(isset($_SERVER['HTTP_CLOUDFRONT_IS_MOBILE_VIEWER'])){
				$mobile = $mobile . 'RewriteCond %{HTTP_CLOUDFRONT_IS_MOBILE_VIEWER} false [NC]' . "\n";
				$mobile = $mobile . 'RewriteCond %{HTTP_CLOUDFRONT_IS_TABLET_VIEWER} false [NC]' . "\n";
			}
		}

		if(!empty($speedycache->options['logged_in_user'])){
			$logged_in_user = 'RewriteCond %{HTTP:Cookie} !wordpress_logged_in';
		}

		if(!preg_match('/^https/i', get_option('home'))){
			$is_not_secure = 'RewriteCond %{HTTPS} !=on';
		}

		if(speedycache_is_trailing_slash()){
			$trailing_slash_rule = 'RewriteCond %{REQUEST_URI} \/$';
		} else {
			$trailing_slash_rule = 'RewriteCond %{REQUEST_URI} ![^\/]+\/$';
		}

		$data = '# BEGIN speedycache
# Modified Time: ' . date("d-m-y G:i:s", current_time('timestamp')) .'
<IfModule mod_rewrite.c>
	RewriteEngine On
	RewriteBase /
	'.self::wp_content().'
	'.self::prefix_redirect().'
	'.self::exclude_rules().'
	'.self::admin_cookie().'
	'.self::http_rule().'
	RewriteCond %{HTTP_USER_AGENT} !('.speedycache_get_excluded_useragent().')
	RewriteCond %{HTTP_USER_AGENT} !(SpeedyCache\sPreload(\siPhone\sMobile)?\s*Bot)
	RewriteCond %{REQUEST_METHOD} !POST
	'.$is_not_secure.'
	RewriteCond %{REQUEST_URI} !(\/){2}$
	'.$trailing_slash_rule.'
	RewriteCond %{QUERY_STRING} !.+
	'.$logged_in_user.'
	'.$consent_cookie.'
	RewriteCond %{HTTP:Cookie} !comment_author_
	RewriteCond %{HTTP:Cookie} !safirmobilswitcher=mobil
	RewriteCond %{HTTP:Profile} !^[a-z0-9\"]+ [NC]
	'.$mobile;

		if(ABSPATH == '//'){
			$data .= '
	RewriteCond %{DOCUMENT_ROOT}/' . SPEEDYCACHE_WP_CONTENT_DIR . $cache_path . '$1/index.html -f';
	
		} else {
			//WARNING: If you change the following lines, you need to update webp as well
			$data .= '
	RewriteCond %{DOCUMENT_ROOT}/' . SPEEDYCACHE_WP_CONTENT_DIR . $cache_path . '$1/index.html -f [or]';
	
			// To escape spaces
			$tmp_SPEEDYCACHE_WP_CONTENT_DIR = str_replace(' ', '\ ', WP_CONTENT_DIR);

			$data .= '
	RewriteCond ' . $tmp_SPEEDYCACHE_WP_CONTENT_DIR . $cache_path . self::get_rewrite_base(true) . '$1/index.html -f';
		}

		$data .= '
	RewriteRule ^(.*) "/' . self::get_rewrite_base() . SPEEDYCACHE_WP_CONTENT_DIR . $cache_path . self::get_rewrite_base(true) . '$1/index.html" [L]';

		//RewriteRule !/  "/site-data/cache/speedycache/all/index.html" [L]

		if(defined('SPEEDYCACHE_PRO') && !empty($speedycache->options['mobile_theme']) && $speedycache->options['mobile_theme']){
			\SpeedyCache\Mobile::cache();

			if(speedycache_is_plugin_active('wptouch/wptouch.php') || speedycache_is_plugin_active('wptouch-pro/wptouch-pro.php')){
				$speedycache->mobile_cache['wptouch'] = true;
			} else {
				$speedycache->mobile_cache['wptouch'] = false;
			}

			$data .= "\n\n\n" . \SpeedyCache\Mobile::update_htaccess($data);
		}

		$data .= '
</IfModule>
<FilesMatch "index\.(html|htm)$">
	AddDefaultCharset UTF-8
	<ifModule mod_headers.c>
		FileETag None
		Header unset ETag
		Header set Cache-Control "max-age=0, no-cache, no-store, must-revalidate"
		Header set Pragma "no-cache"
		Header set Expires "Mon, 29 Oct 1923 20:30:00 GMT"
	</ifModule>
</FilesMatch>
# END speedycache
';

		if(is_multisite()){
			return '';
		}
		
		return preg_replace("/\n+/", "\n", $data);
	}

	static function new_user($user_id){
		
		$path = speedycache_get_abspath();

		$htaccess = @file_get_contents($path.'.htaccess');

		if(preg_match("/\#\s?Start_SPEEDYCACHE_Exclude_Admin_Cookie/", $htaccess)){
			$rules = self::admin_cookie();

			$htaccess = preg_replace("/\#\s?Start_SPEEDYCACHE_Exclude_Admin_Cookie[^\#]*\#\s?End_SPEEDYCACHE_Exclude_Admin_Cookie\s+/", $rules, $htaccess);
		}

		@file_put_contents($path.'.htaccess', $htaccess);
	}

	// Adds HTACCESS Rules for WEBP
	static function webp($htaccess){
		
		$webp = false;
		
		if(!defined('SPEEDYCACHE_DISABLE_WEBP') || (defined('SPEEDYCACHE_DISABLE_WEBP') && empty('SPEEDYCACHE_DISABLE_WEBP'))){
			$webp = true;
			$cdn_values = get_option('speedycache_cdn');

			if(!empty($cdn_values)){
				foreach($cdn_values as $key => $value){
					if(self::cloudflare_web_opt($value, $webp) === true){
						break;
					}
				}
			}
		}
		
		$htaccess = preg_replace("/#\s?BEGIN\s?WEBPspeedycache.*?#\s?END\s?WEBPspeedycache/s", '', $htaccess);
		
		if(empty($webp)){
			return $htaccess;
		}

		$basename = "$1.webp";

		/* 
			This part for sub-directory installation
			WordPress Address (URL): site_url() 
			Site Address (URL): home_url()
		*/
		if(preg_match("/https?\:\/\/[^\/]+\/(.+)/", site_url(), $siteurl_base_name)){
			if(preg_match("/https?\:\/\/[^\/]+\/(.+)/", home_url(), $homeurl_base_name)){
				/*
					site_url() return http://example.com/sub-directory
					home_url() returns http://example.com/sub-directory
				*/

				$homeurl_base_name[1] = trim($homeurl_base_name[1], '/');
				$siteurl_base_name[1] = trim($siteurl_base_name[1], '/');

				if($homeurl_base_name[1] == $siteurl_base_name[1]){
					if(preg_match('/' . preg_quote($homeurl_base_name[1], '/') . "$/", trim(ABSPATH, '/'))){
						$basename = $homeurl_base_name[1] . '/' . $basename;
					}
				} else {
					if(!preg_match("/\//", $homeurl_base_name[1]) && !preg_match("/\//", $siteurl_base_name[1])){
						/*
							site_url() return http://example.com/wordpress
							home_url() returns http://example.com/blog
						*/

						$basename = $homeurl_base_name[1] . '/' . $basename;
						$tmp_ABSPATH = str_replace(' ', '\ ', ABSPATH);

						if(preg_match("/\/$/", $tmp_ABSPATH)){
							$tmp_ABSPATH = rtrim($tmp_ABSPATH, '/');
							$tmp_ABSPATH = dirname($tmp_ABSPATH) . '/' . $homeurl_base_name[1] . '/';
						}
					}
				}
			} else {
				/*
					site_url() return http://example.com/sub-directory
					home_url() returns http://example.com/
				*/
				$siteurl_base_name[1] = trim($siteurl_base_name[1], '/');
				$basename = $siteurl_base_name[1] . '/' . $basename;
			}
		}

		if(ABSPATH == '//'){
			$RewriteCond = 'RewriteCond %{DOCUMENT_ROOT}/' . $basename . ' -f';
		} else {
			// to escape spaces
			if(!isset($tmp_ABSPATH)){
				$tmp_ABSPATH = str_replace(' ', '\ ', ABSPATH);
			}

			$RewriteCond = 'RewriteCond %{DOCUMENT_ROOT}/' . $basename . ' -f [or]
	RewriteCond ' . $tmp_ABSPATH . '$1.webp -f';
		}

		$data = '
# BEGIN WEBPspeedycache
<IfModule mod_rewrite.c>
	RewriteEngine On
	RewriteCond %{HTTP_ACCEPT} image/webp
	RewriteCond %{REQUEST_URI} \.(jpe?g|png|gif)
	'.$RewriteCond.'
	RewriteRule (?i)(.*)(\.jpe?g|\.png|\.gif)$ /' .$basename.' [T=image/webp,E=EXISTING:1,L]
</IfModule>
<IfModule mod_headers.c>
	Header append Vary Accept env=REDIRECT_accept
</IfModule>
AddType image/webp .webp
# END WEBPspeedycache

';

		if(!preg_match('/BEGIN\s*WEBPspeedycache/', $htaccess)){
			$htaccess = $data . $htaccess;
		}

		return $htaccess;
	}

	static function browser_cache($htaccess){
		
		global $speedycache;
		
		// Delete levere browser caching
		$htaccess = preg_replace("/#\s?BEGIN\s?LBCspeedycache.*?#\s?END\s?LBCspeedycache/s", '', $htaccess);
		
		if(empty($speedycache->options['lbc'])){			
			return $htaccess;
		}

		$data = '
# BEGIN LBCspeedycache
<FilesMatch "\.(webm|ogg|mp4|ico|pdf|flv|jpg|jpeg|png|gif|webp|js|css|swf|x-html|css|xml|js|woff|woff2|otf|ttf|svg|eot)(\.gz)?$">
	<IfModule mod_expires.c>
		AddType application/font-woff2 .woff2
		AddType application/x-font-opentype .otf
		ExpiresActive On
		ExpiresDefault A0
		ExpiresByType video/webm A10368000
		ExpiresByType video/ogg A10368000
		ExpiresByType video/mp4 A10368000
		ExpiresByType image/webp A10368000
		ExpiresByType image/gif A10368000
		ExpiresByType image/png A10368000
		ExpiresByType image/jpg A10368000
		ExpiresByType image/jpeg A10368000
		ExpiresByType image/ico A10368000
		ExpiresByType image/svg+xml A10368000
		ExpiresByType text/css A10368000
		ExpiresByType text/javascript A10368000
		ExpiresByType application/javascript A10368000
		ExpiresByType application/x-javascript A10368000
		ExpiresByType application/font-woff2 A10368000
		ExpiresByType application/x-font-opentype A10368000
		ExpiresByType application/x-font-truetype A10368000
	</IfModule>
	<IfModule mod_headers.c>
		Header set Expires "max-age=A10368000, "
		Header unset ETag
		Header set Connection keep-alive
		FileETag None
	</IfModule>
</FilesMatch>
# END LBCspeedycache
';

		if(!preg_match('/BEGIN\s*LBCspeedycache/', $htaccess)){
			return $data . $htaccess;
		}
		
		return $htaccess;

	}

	static function gzip($htaccess){
		
		global $speedycache;
		
		// Delete gzip rules
		$htaccess = preg_replace("/\s*\#\s?BEGIN\s?Gzipspeedycache.*?#\s?END\s?Gzipspeedycache\s*/s", '', $htaccess);
		
		if(empty($speedycache->options['gzip'])){	
			return $htaccess;
		}

		$data = '
# BEGIN Gzipspeedycache
<IfModule mod_deflate.c>
	AddType x-font/woff .woff
	AddType x-font/ttf .ttf
	AddOutputFilterByType DEFLATE text/js
	AddOutputFilterByType DEFLATE x-font/ttf
	AddOutputFilterByType DEFLATE application/javascript
	AddOutputFilterByType DEFLATE application/rss+xml
	AddOutputFilterByType DEFLATE application/vnd.ms-fontobject
	AddOutputFilterByType DEFLATE application/x-font
	AddOutputFilterByType DEFLATE application/x-font-opentype
	AddOutputFilterByType DEFLATE application/x-font-otf
	AddOutputFilterByType DEFLATE application/x-font-truetype
	AddOutputFilterByType DEFLATE application/x-font-ttf
	AddOutputFilterByType DEFLATE application/x-javascript
	AddOutputFilterByType DEFLATE application/font-woff2
	AddOutputFilterByType DEFLATE application/xhtml+xml
	AddOutputFilterByType DEFLATE application/xml
	AddOutputFilterByType DEFLATE font/opentype
	AddOutputFilterByType DEFLATE font/otf
	AddOutputFilterByType DEFLATE font/ttf
	AddOutputFilterByType DEFLATE font/woff
	AddOutputFilterByType DEFLATE font/woff2
	AddOutputFilterByType DEFLATE image/svg+xml
	AddOutputFilterByType DEFLATE image/x-icon
	AddOutputFilterByType DEFLATE text/css
	AddOutputFilterByType DEFLATE text/html
	AddOutputFilterByType DEFLATE text/javascript
	AddOutputFilterByType DEFLATE text/plain
	AddOutputFilterByType DEFLATE text/xml
</IfModule>
# END Gzipspeedycache
';
		return $data . $htaccess;
	}

	static function rewrite_rule($htaccess){
		
		global $speedycache;

		if(!empty($speedycache->options['status'])){
			$htaccess = preg_replace('/#\s?BEGIN\s?speedycache.*?#\s?END\s?speedycache\s*/s', '', $htaccess);
			$htaccess = self::get() . $htaccess;

			return $htaccess;
		}
		
		$htaccess = preg_replace("/#\s?BEGIN\s?speedycache.*?#\s?END\s?speedycache\s*/s", '', $htaccess);
		speedycache_delete_cache();

		return $htaccess;
	}

	static function prefix_redirect(){
		$force_to = '';

		if(defined('SPEEDYCACHE_DISABLE_REDIRECTION') && SPEEDYCACHE_DISABLE_REDIRECTION){
			return $force_to;
		}
		
		$server_host = !empty($_SERVER['HTTP_HOST']) ? sanitize_text_field($_SERVER['HTTP_HOST']) : '';

		if(preg_match("/^https:\/\//", home_url())){
			if(preg_match("/^https:\/\/www\./", home_url())){
				$force_to = 'RewriteCond %{HTTPS} =on
	RewriteCond %{HTTP_HOST} ^www.' . str_replace('www.', '', $server_host);
			} else {
				$force_to = 'RewriteCond %{HTTPS} =on
	RewriteCond %{HTTP_HOST} ^' . str_replace('www.', '', $server_host);
			}
			
			return $force_to;
		}

		if(preg_match("/^http:\/\/www\./", home_url())){
			$force_to = 'RewriteCond %{HTTP_HOST} ^'.str_replace('www.', '', $server_host).'
	RewriteRule ^(.*)$ '.preg_quote(home_url(), '/') . '\/$1 [R=301,L]';
		} else {
			$force_to = 'RewriteCond %{HTTP_HOST} ^www.'. str_replace('www.', '', $server_host) . ' [NC]
	RewriteRule ^(.*)$ ' . preg_quote(home_url(), "/") . '\/$1 [R=301,L]';
		}

		return $force_to;
	}

	static function http_rule(){
		$http_host = preg_replace('/(http(s?)\:)?\/\/(www\d*\.)?/i', '', trim(home_url(), '/'));

		if(preg_match('/\//', $http_host)){
			$http_host = strstr($http_host, '/', true);
		}

		if(preg_match('/www\./', home_url())){
			$http_host = 'www.' . $http_host;
		}

		return 'RewriteCond %{HTTP_HOST} ^' . $http_host;
	}

	static function wp_content(){
		$newContentPath = str_replace(home_url(), '', content_url());

		if(!preg_match('/'.SPEEDYCACHE_WP_CONTENT_DIR.'/', $newContentPath)){
			$newContentPath = trim($newContentPath, '/');
			return 'RewriteRule ^' . $newContentPath . '/speedycache/(.*) ' . WP_CONTENT_DIR . '/speedycache/$1 [L]' . "\n";
		}
		
		return '';
	}

	static function add_exclude(){
		$path = speedycache_get_abspath();

		$htaccess = @file_get_contents($path.'.htaccess');

		if(preg_match("/\#\s?Start\sSPEEDYCACHE\sExclude/", $htaccess)){
			$exclude_rules = self::exclude_rules();

			$htaccess = preg_replace("/\#\s?Start\sSPEEDYCACHE\sExclude[^\#]*\#\s?End\sSPEEDYCACHE\sExclude\s+/", $exclude_rules, $htaccess);
		}

		@file_put_contents($path.'.htaccess', $htaccess);
	}

	static function exclude_rules(){
		$htaccess_page_rules = '';
		$htaccess_page_useragent = '';
		$htaccess_page_cookie = '';

		$rules = get_option('speedycache_exclude');
		if(empty($rules)){
			return false;
		}
			
		foreach($rules as $key => $value){
			$value['type'] = isset($value['type']) ? $value['type'] : 'page';

			// escape the chars
			$value['content'] = str_replace('?', '\?', $value['content']);

			switch($value['type']){
				case 'cookie':
					$htaccess_page_cookie = $htaccess_page_cookie."RewriteCond %{HTTP:Cookie} !".$value['content']." [NC]\n";
					break;
				
				case 'useragent':
					$htaccess_page_useragent = $htaccess_page_useragent."RewriteCond %{HTTP_USER_AGENT} !".$value['content']." [NC]\n";
					break;
				
				case 'page':
					if($value['prefix'] == 'startwith'){
						$htaccess_page_rules = $htaccess_page_rules."RewriteCond %{REQUEST_URI} !^/".$value['content']." [NC]\n";
					}

					if($value['prefix'] == 'contain'){
						$htaccess_page_rules = $htaccess_page_rules."RewriteCond %{REQUEST_URI} !".$value['content']." [NC]\n";
					}

					if($value['prefix'] == 'exact'){
						$htaccess_page_rules = $htaccess_page_rules."RewriteCond %{REQUEST_URI} !\/".$value['content']." [NC]\n";
					}

					break;
			}
		}

		return "# Start SPEEDYCACHE Exclude\n".$htaccess_page_rules.$htaccess_page_useragent.$htaccess_page_cookie."# End SPEEDYCACHE Exclude\n";
	}

	static function admin_cookie(){
		$rules = '';
		$users_groups = array_chunk(get_users(array('role' => 'administrator', 'fields' => array('user_login'))), 5);

		foreach($users_groups as $group_key => $group){
			$tmp_users = '';
			$tmp_rule = '';

			foreach($group as $key => $value){
				if(!empty($tmp_users)){
					$tmp_users = $tmp_users.'|'.sanitize_user(wp_unslash($value->user_login), true);
				}else{
					$tmp_users = sanitize_user(wp_unslash($value->user_login), true);
				}

				// to replace spaces with \s
				$tmp_users = preg_replace("/\s/", "\s", $tmp_users);

				if(!next($group)){
					$tmp_rule = 'RewriteCond %{HTTP:Cookie} !wordpress_logged_in_[^\=]+\='.$tmp_users;
				}
			}

			if(!empty($rules)){
				$rules = $rules."\n".$tmp_rule;
			}else{
				$rules = $tmp_rule;
			}
		}

		return '
	# Start_SPEEDYCACHE_Exclude_Admin_Cookie
	'.$rules.'
	# End_SPEEDYCACHE_Exclude_Admin_Cookie
';
	
	}

	static function warning_incompatible($incompatible, $alternative = false){
		
		if(empty($alternative)){
			return array($incompatible . ' <label>needs to be deactivated</label>', 'error');
		}
		
		return array($incompatible . " <label>needs to be deactivated</label><br><label>We advise</label> <a id='alternative-plugin' target='_blank' href='" . $alternative["url"] . "'>" . $alternative["name"] . "</a>", "error");
	}

	static function cloudflare_web_opt($cdn, &$webp){

		if($cdn['id'] !== 'cloudflare'){
			return false;
		}

		\SpeedyCache\CDN::purge();
		$res = \SpeedyCache\CDN::cloudflare_zone_id($cdn['cdn_url'], $cdn['origin_url']);

		if($res['success'] && ($res['plan'] == 'free')){
			$webp = false;
		}

		return true;
	}

	static function get_rewrite_base($sub = ''){
		
		if(!empty($sub) && speedycache_is_dir_install()){
			$trimedProtocol = preg_replace('/http:\/\/|https:\/\//', '', trim(home_url(), '/'));
			$path = strstr($trimedProtocol, '/');

			if(!empty($path)){
				return trim($path, '/') . '/';
			}

			return '';
		}

		$url = rtrim(site_url(), '/');
		preg_match('/https?:\/\/[^\/]+(.*)/', $url, $out);

		if(isset($out[1]) && $out[1]){
			$out[1] = trim($out[1], '/');

			if(preg_match('/\/' . preg_quote($out[1], '/') . '\//', WP_CONTENT_DIR)){
				return $out[1] . '/';
			}
			
			return '';
		}
		
		return '';

	}

	static function check_super_cache($path, $htaccess){
		if(speedycache_is_plugin_active('wp-super-cache/wp-cache.php')){
			return array('WP Super Cache needs to be deactive', 'error');
		}

		if(file_exists($path . SPEEDYCACHE_WP_CONTENT_DIR . '/wp-cache-config.php')){
			@unlink($path . SPEEDYCACHE_WP_CONTENT_DIR . '/wp-cache-config.php');
		}
		
		$message = '';

		if(is_file($path . SPEEDYCACHE_WP_CONTENT_DIR . '/wp-cache-config.php')){
			$message .= '<br>- be sure that you removed /'.SPEEDYCACHE_WP_CONTENT_DIR.'/wp-cache-config.php';
		}

		if(preg_match('/supercache/', $htaccess)){
			$message .= '<br>- be sure that you removed the rules of super cache from the .htaccess';
		}

		if(!empty($message)){
			return array('WP Super Cache cannot remove its own remnants so please follow the steps below' . $message, 'error');
		}

		return '';
	}

}
<?php
/*
* SPEEDYCACHE
* https://speedycache.com/
* (c) SpeedyCache Team
*/

if( !defined('ABSPATH') ){
	die('HACKING ATTEMPT!');
}

function speedycache_print($printable){
	echo '<pre>';
	print_r($printable);
	echo '</pre>';
}

function speedycache_exclude_urls(){
	
	// to exclude wishlist url of YITH WooCommerce Wishlist
	if(get_option('yith-woocommerce-wishlist/init.php')){
		$wishlist_page_id = get_option('yith_wcwl_wishlist_page_id');
		$permalink = urldecode(get_permalink($wishlist_page_id));

		if(preg_match("/https?:\/\/[^\/]+\/(.+)/", $permalink, $out)){
			$url = trim($out[1], "/");
		}
	}

	if(empty($url)){
		return;
	}
	
	$rules_arr = get_option('speedycache_exclude');

	$new_rule = [];
	$new_rule['prefix'] = 'exact';
	$new_rule['content'] = $url;
	$new_rule['type'] = 'page';

	if($rules_json === false){
		array_push($rules_arr, $new_rule);
		update_option('speedycache_exclude', $rules_arr, null, 'yes');
		
		return;
	}
	
	if(!is_array($rules_arr)){
		$rules_arr = array();
	}

	if(!in_array($new_rule, $rules_arr)){
		array_push($rules_arr, $new_rule);
		update_option('speedycache_exclude', $rules_arr);
	}
}

//NOTE:: Remove this if not usefull
function speedycache_create_auto_cache_timeout($recurrance, $interval){
	
	$exist_cronjob = false;
	$speedycache_timeout_number = 0;

	$crons = _get_cron_array();

	foreach((array)$crons as $cron_key => $cron_value){
		foreach((array) $cron_value as $hook => $events){
			if(!preg_match('/^speedycache(.*)/', $hook, $id)){
				continue;
			}

			if($id[1] && !preg_match("/^\_(\d+)$/", $id[1])){
				continue;
			}

			$speedycache_timeout_number++;

			foreach((array) $events as $event_key => $event){
				$schedules = wp_get_schedules();

				if(!isset($event['args']) || !isset($event['args'][0])){
					continue;
				}

				if($event['args'][0]['prefix'] !== 'all' || $event['args'][0]['content'] !== 'all'){
					continue;
				}

				if($schedules[$event['schedule']]['interval'] <= $interval){
					$exist_cronjob = true;
				}
			}
		}
	}

	if(!$exist_cronjob){
		$args = array('prefix' => 'all', 'content' => 'all');
		wp_schedule_event(time(), $recurrance, 'speedycache_' . $speedycache_timeout_number, array($args));
	}
}

function speedycache_clean($var){
	if(is_array($var) || is_object($var)){
		return map_deep($var, 'sanitize_text_field');
	}
	
	if(is_scalar($var)){
		return sanitize_text_field($var);
	}

	return '';

}

// Checks if the given plugin active
function speedycache_is_plugin_active($plugin){

	return in_array( $plugin, (array) get_option( 'active_plugins', array() ), true ) || speedycache_is_plugin_active_for_network( $plugin );

}

// Checks if the given plugin active for network
function speedycache_is_plugin_active_for_network($plugin){
	if ( ! is_multisite() ) {
		return false;
	}

	$plugins = get_site_option( 'active_sitewide_plugins' );
	if ( isset( $plugins[ $plugin ] ) ) {
		return true;
	}

	return false;
}

function speedycache_verify_nonce($nonce, $nonce_name){
	if(!wp_verify_nonce($nonce, $nonce_name)){
		wp_send_json(array('success' => false, 'message' => 'Security check failed'));
	}
}

// Deletes binaries
function speedycache_delete_binaries(){
	
	$binary_dir = wp_upload_dir()['basedir'] .'/speedycache-binary';
	
	if(!file_exists($binary_dir)){
		return;
	}
	
	$binaries = @scandir($binary_dir);
	$binaries = array_diff($binaries, ['.', '..']);
	
	if(empty($binaries)){
		@rmdir($binary_dir);
		return;
	}
	
	foreach($binaries as $binary){
		if(file_exists($binary_dir.'/'.$binary)){
			@unlink($binary_dir.'/'.$binary);
		}
	}
}

function speedycache_is_trailing_slash(){
	// no need to check if Custom Permalinks plugin is active (https://tr.wordpress.org/plugins/custom-permalinks/)
	if(get_option('custom-permalinks/custom-permalinks.php')){
		return false;
	}

	$permalink_structure = get_option('permalink_structure');
	if(empty($permalink_structure)){
		return false;
	}
	
	if(!preg_match('/\/$/', $permalink_structure)){
		return false;
	}

	return true;
}

function speedycache_read_file($url){
	$path = '';
	
	if(preg_match('/\.php/', $url)){
		return false;
	}

	$url = preg_replace('/\?.*/', '', $url);
	
	if(preg_match('/'.SPEEDYCACHE_WP_CONTENT_DIR.'/', $url)){
		$path = preg_replace('/.+\/'.SPEEDYCACHE_WP_CONTENT_DIR.'\/(.+)/', WP_CONTENT_DIR.'/'."$1", $url);
	}else if(preg_match('/'.WPINC.'/', $url)){
		$path = preg_replace('/.+\/' . WPINC . '\/(.+)/', ABSPATH . WPINC . '/'."$1", $url);
	}

	if(empty($path) || !@file_exists($path)){
		return false;
	}

	$filesize = filesize($path);

	if($filesize <= 0){
		return false;
	}

	$myfile = fopen($path, 'r') or die('Unable to open file!');
	$data = fread($myfile, $filesize);
	fclose($myfile);

	return $data;
}

function speedycache_get_operating_systems(){
	$operating_systems  = array(
		'Android',
		'blackberry|\bBB10\b|rim\stablet\sos',
		'PalmOS|avantgo|blazer|elaine|hiptop|palm|plucker|xiino',
		'Symbian|SymbOS|Series60|Series40|SYB-[0-9]+|\bS60\b',
		'Windows\sCE.*(PPC|Smartphone|Mobile|[0-9]{3}x[0-9]{3})|Window\sMobile|Windows\sPhone\s[0-9.]+|WCE;',
		'Windows\sPhone\s10.0|Windows\sPhone\s8.1|Windows\sPhone\s8.0|Windows\sPhone\sOS|XBLWP7|ZuneWP7|Windows\sNT\s6\.[23]\;\sARM\;',
		'\biPhone.*Mobile|\biPod|\biPad',
		'Apple-iPhone7C2',
		'MeeGo',
		'Maemo',
		'J2ME\/|\bMIDP\b|\bCLDC\b', // '|Java/' produces bug #135
		'webOS|hpwOS',
		'\bBada\b',
		'BREW'
	);

	return $operating_systems;
}

function speedycache_get_mobile_browsers(){
	$mobile_browsers = array(
		'\bCrMo\b|CriOS|Android.*Chrome\/[.0-9]*\s(Mobile)?',
		'\bDolfin\b',
		'Opera.*Mini|Opera.*Mobi|Android.*Opera|Mobile.*OPR\/[0-9.]+|Coast\/[0-9.]+',
		'Skyfire',
		'Mobile\sSafari\/[.0-9]*\sEdge',
		'IEMobile|MSIEMobile', // |Trident/[.0-9]+
		'fennec|firefox.*maemo|(Mobile|Tablet).*Firefox|Firefox.*Mobile|FxiOS',
		'bolt',
		'teashark',
		'Blazer',
		'Version.*Mobile.*Safari|Safari.*Mobile|MobileSafari',
		'Tizen',
		'UC.*Browser|UCWEB',
		'baiduboxapp',
		'baidubrowser',
		'DiigoBrowser',
		'Puffin',
		'\bMercury\b',
		'Obigo',
		'NF-Browser',		'NokiaBrowser|OviBrowser|OneBrowser|TwonkyBeamBrowser|SEMC.*Browser|FlyFlow|Minimo|NetFront|Novarra-Vision|MQQBrowser|MicroMessenger',
		'Android.*PaleMoon|Mobile.*PaleMoon'
	);

	return $mobile_browsers;
}

function speedycache_load_admin_toolbar(){

	if(defined('SPEEDYCACHE_HIDE_TOOLBAR') && !empty(SPEEDYCACHE_HIDE_TOOLBAR)){
		return;
	}

	$user = wp_get_current_user();
	$allowed_roles = array('administrator');

	$speedycache_role_status = get_option('speedycache_toolbar_settings');
	if(is_array($speedycache_role_status) && !empty($speedycache_role_status)){
		foreach($speedycache_role_status as $key => $value){
			$key = str_replace('speedycache_toolbar_', '', $key);
			array_push($allowed_roles, strtolower($key));
		}
	}

	if(array_intersect($allowed_roles, $user->roles)){
		\SpeedyCache\Toolbar::add();
	}
}

function speedycache_get_mobile_user_agents(){
	return implode('|', speedycache_get_mobile_browsers()).'|'.implode('|', speedycache_get_operating_systems());
}

// Check if a field is posted via GET else return default value
function speedycache_optget($name, $default = ''){
	
	if(!empty($_GET[$name])){
		return speedycache_clean($_GET[$name]);
	}
	
	return $default;	
}

function speedycache_optserver($index){
	return !empty($index) && !empty($_SERVER[$index]) ? sanitize_text_field(wp_unslash($_SERVER[$index])) : '';
}

// Check if a field is posted via POST else return default value
function speedycache_optpost($name, $default = ''){
	
	if(!empty($_POST[$name])){
		return speedycache_clean($_POST[$name]);
	}
	
	return $default;	
}

function speedycache_single_delete_cache($comment_id = false, $post_id = false){
	return \SpeedyCache\Delete::single_cache($comment_id, $post_id);
}

function speedycache_delete_home_page_cache($log = true){
	return \SpeedyCache\Delete::home_page_cache($log);
}

function speedycache_delete_cache($delete_min = false, $delete_fonts = false, $single_delete = false){
	return \SpeedyCache\Delete::cache($delete_min, $delete_fonts, $single_delete);
}

// Shows a message when anything is saved
function speedycache_notify($message = array()){
	if(!empty($message[0])){
		if(function_exists('add_settings_error')){
			add_settings_error('speedycache-notice', esc_attr( 'settings_updated' ), $message[0], $message[1]);
		}
	}
}

function speedycache_get_excluded_useragent(){
	return 'facebookexternalhit|Twitterbot|LinkedInBot|WhatsApp|Mediatoolkitbot|SpeedyCacheCCSS';
}

function speedycache_set_custom_interval(){
	add_filter('cron_schedules', 'speedycache_cron_add_minute', 40);
}

function speedycache_remote_get($url, $user_agent){
	
	$response = wp_remote_get($url, array('user-agent' => $user_agent, 'timeout' => 10, 'sslverify' => false, 'headers' => array('cache-control' => 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0')));

	if(empty($response) || is_wp_error($response)){
		echo esc_html($response->get_error_message()).' - ';

		return false;
	}
	
	if(wp_remote_retrieve_response_code($response) != 200){
		return false;
	}

	return true;
}

function speedycache_is_dir_install(){
	if(strlen(site_url()) > strlen(home_url())){
		return true;
	}
	return false;
}

function speedycache_cron_add_minute($schedules){
	
	$schedules['everyminute'] = array(
		'interval' => 60,
		'display' => __( 'Once Every 1 Minute' ),
		'speedycache' => true
	);

	$schedules['everyfiveminute'] = array(
		'interval' => 300,
		'display' => __( 'Once Every 5 Minutes' ),
		'speedycache' => true
	);

	$schedules['everyfifteenminute'] = array(
		'interval' => 900,
		'display' => __( 'Once Every 15 Minutes' ),
		'speedycache' => true
	);

	$schedules['twiceanhour'] = array(
		'interval' => 1800,
		'display' => __( 'Twice an Hour' ),
		'speedycache' => true
	);

	$schedules['onceanhour'] = array(
		'interval' => 3600,
		'display' => __( 'Once an Hour' ),
		'speedycache' => true
	);

	$schedules['everytwohours'] = array(
		'interval' => 7200,
		'display' => __( 'Once Every 2 Hours' ),
		'speedycache' => true
	);

	$schedules['everythreehours'] = array(
		'interval' => 10800,
		'display' => __( 'Once Every 3 Hours' ),
		'speedycache' => true
	);

	$schedules['everyfourhours'] = array(
		'interval' => 14400,
		'display' => __( 'Once Every 4 Hours' ),
		'speedycache' => true
	);

	$schedules['everyfivehours'] = array(
		'interval' => 18000,
		'display' => __( 'Once Every 5 Hours' ),
		'speedycache' => true
	);

	$schedules['everysixhours'] = array(
		'interval' => 21600,
		'display' => __( 'Once Every 6 Hours' ),
		'speedycache' => true
	);

	$schedules['everysevenhours'] = array(
		'interval' => 25200,
		'display' => __( 'Once Every 7 Hours' ),
		'speedycache' => true
	);

	$schedules['everyeighthours'] = array(
		'interval' => 28800,
		'display' => __( 'Once Every 8 Hours' ),
		'speedycache' => true
	);

	$schedules['everyninehours'] = array(
		'interval' => 32400,
		'display' => __( 'Once Every 9 Hours' ),
		'speedycache' => true
	);

	$schedules['everytenhours'] = array(
		'interval' => 36000,
		'display' => __( 'Once Every 10 Hours' ),
		'speedycache' => true
	);

	$schedules['onceaday'] = array(
		'interval' => 86400,
		'display' => __( 'Once a Day' ),
		'speedycache' => true
	);

	$schedules['everythreedays'] = array(
		'interval' => 259200,
		'display' => __( 'Once Every 3 Days' ),
		'speedycache' => true
	);

	$schedules['everysevendays'] = array(
		'interval' => 604800,
		'display' => __( 'Once Every 7 Days' ),
		'speedycache' => true
	);

	$schedules['everytendays'] = array(
		'interval' => 864000,
		'display' => __( 'Once Every 10 Days' ),
		'speedycache' => true
	);

	$schedules['everyfifteendays'] = array(
		'interval' => 1296000,
		'display' => __( 'Once Every 15 Days' ),
		'speedycache' => true
	);

	$schedules['montly'] = array(
		'interval' => 2592000,
		'display' => __( 'Once a Month' ),
		'speedycache' => true
	);

	$schedules['yearly'] = array(
		'interval' => 31104000,
		'display' => __( 'Once a Year' ),
		'speedycache' => true
	);

	return $schedules;
}

function speedycache_set_schedule($args = '') {
	global $speedycache;
	
	if('all' === $args['prefix']){
		speedycache_delete_cache();
	}else if('homepage' === $args['prefix']){
		@unlink(speedycache_cache_path('all/index.html'));
		@unlink(speedycache_cache_path('mobile-cache/index.html'));

		if(!empty($speedycache->options['preload_homepage'])){
			speedycache_remote_get(get_option('home'), 'speedycache_preload Bot - After Cache Timeout');
			speedycache_remote_get(get_option('home'), 'speedycache_preload iPhone Mobile Bot - After Cache Timeout');
		}
	}else if('startwith' === $args['prefix']){
			if(!is_dir(speedycache_cache_path('tmp_cache'))){
				@mkdir(speedycache_cache_path('tmp_cache'), 0755, true);
			}

			$args['content'] = trim($args['content'], '/');

			$files = glob(speedycache_cache_path('all/').$args['content'].'*');

			foreach((array)$files as $file){
				$mobile_file = str_replace('/speedycache/'.SPEEDYCACHE_SERVER_HOST.'/all', '/speedycache/'.SPEEDYCACHE_SERVER_HOST.'/mobile-cache/', $file);
				
				@rename($file, speedycache_cache_path('tmp_cache/').time());
				@rename($mobile_file, speedycache_cache_path('tmp_cache/mobile_').time());
			}
	}else if('exact' === $args['prefix']){
		$args['content'] = trim($args['content'], '/');

		@unlink(speedycache_cache_path('all/').$args['content'].'/index.html');
		@unlink(speedycache_cache_path('mobile-cache/').$args['content'].'/index.html');
	}

	if(defined('SPEEDYCACHE_PRO') && 'all' !== $args['prefix']){
		\SpeedyCache\Logs::log('delete');
		\SpeedyCache\Logs::action($args);
	}
}

function speedycache_is_mobile(){

	if(!empty($_SERVER['HTTP_USER_AGENT'])){
		$user_agent = sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT']));
		
		foreach(speedycache_get_mobile_browsers() as $value){
			if(preg_match('/'.$value.'/i', $user_agent)){
				return true;
			}
		}

		foreach(speedycache_get_operating_systems() as $key => $value){
			if(preg_match('/'.$value.'/i', $user_agent)){
				return true;
			}
		}
	}

	if(!empty($_SERVER['HTTP_CLOUDFRONT_IS_MOBILE_VIEWER']) && 'true' === $_SERVER['HTTP_CLOUDFRONT_IS_MOBILE_VIEWER']){
		return true;
	}

	if(!empty($_SERVER['HTTP_CLOUDFRONT_IS_TABLET_VIEWER']) && 'true' === $_SERVER['HTTP_CLOUDFRONT_IS_TABLET_VIEWER']){
		return true;
	}
	
	return false;

}

function speedycache_page_header(){
	
	global $speedycache;
	
	if(empty($_GET['page']) || strpos($_GET['page'], 'speedycache') === FALSE){
		return;
	}
	
	// To hide screenoptions on speedycache settings page
	add_filter('screen_options_show_screen', '__return_false');
	
	$title = 'SpeedyCache Settings';
	$active_license = '';
	$active_settings = 'speedycache-header-active';
	
	if($_GET['page'] == 'speedycache-license'){
		$title = 'SpeedyCache License';
		$active_license = 'speedycache-header-active';
		$active_settings = '';
	}

	echo '<div class="speedycache-admin-header">
	<div class="speedycache-brand-title"><img src="'.esc_attr(SPEEDYCACHE_URL) .'/assets/images/icon.svg" width="30px" height="30px"/><span style="margin-left:7px;">'.esc_html($title).'</span><span style="color:rgba(255,255,255,0.5); margin-left:3px; font-weight:400; font-size:0.9rem; line-height:2;"> - v'.esc_html(SPEEDYCACHE_VERSION).'</span></div>';
	
	if(defined('SPEEDYCACHE_PRO')){
		$speedycache->settings['brand_data'] = apply_filters('speedycache_brand_data', []);
	}
	
	if(empty($speedycache->settings['brand_data'])){
		echo '<div class="speedycache-header-actions">
			<a href="?page=speedycache" class="'.esc_attr($active_settings).'" title="SpeedyCache Settings">Settings</a>
			'.(!defined('SITEPAD') && defined('SPEEDYCACHE_PRO') ? '<a href="?page=speedycache-license" class="'.esc_attr($active_license).'" title="SpeedyCache License">License</a>' : '').
			'<a href="https://speedycache.com/docs" target="_blank" title="SpeedyCache Docs">Docs</a>
			<a href="https://softaculous.deskuss.com/" target="_blank" title="SpeedyCache Docs">Support</a>
			'.(!defined('SITEPAD') ? '<a href="https://wordpress.org/support/plugin/speedycache/reviews/?filter=5#new-post" target="_blank" class="button button-secondary" style="margin-right:10px">Review SpeedyCache</a>' : '').'
		</div>';
	}
	
	echo '</div>';
}

function speedycache_sanitize_url($url){
	
	if(defined('SITEPAD') || version_compare(get_bloginfo('version'), '5.9.0', '<')){
		return esc_url_raw($url);
	}
	
	return sanitize_url($url);
}

function speedycache_cache_path($path = ''){
	return speedycache_get_wp_content_dir('/cache/speedycache/' . SPEEDYCACHE_SERVER_HOST . '/' . $path);
}

function speedycache_set_host(){
	global $blog_id;
	
	if(defined('SPEEDYCACHE_SERVER_HOST')){
		return;
	}

	$url = get_option('home');
	
	if(!empty($blog_id) && is_multisite()){
		switch_to_blog($blog_id);
		$url = get_option('home');
		restore_current_blog();
	}

	$url = wp_parse_url($url);
	
	if(empty($url) || !is_array($url) || empty($url['host'])){
		define('SPEEDYCACHE_SERVER_HOST', !empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'all');
		return;
	}

	define('SPEEDYCACHE_SERVER_HOST', $url['host']);
}

function speedycache_check_cache_path_writeable(){
	
	$message = array();

	if(!is_dir(speedycache_get_wp_content_dir('/cache/speedycache/'))){
		if(!mkdir(speedycache_get_wp_content_dir('/cache/speedycache/'), 0755, true)){
			array_push($message, '- /'.SPEEDYCACHE_WP_CONTENT_DIR.'/cache/speedycache/ is needed to be created');
		}
	} else {
		if(@mkdir(speedycache_cache_path('testspeedycache/'), 0755, true)){
			rmdir(speedycache_cache_path('testspeedycache/'));
		} else {
			array_push($message, '- /'.SPEEDYCACHE_WP_CONTENT_DIR.'/cache/speedycache/ permission has to be 755');
		}
	}

	if(!is_dir(speedycache_cache_path('all/'))){
		if(!mkdir(speedycache_cache_path('all/'), 0755, true)){
			array_push($message, '- /'.SPEEDYCACHE_WP_CONTENT_DIR.'/cache/speedycache/'.SPEEDYCACHE_SERVER_HOST.'/ is needed to be created');
		}
	} else {
		if(@mkdir(speedycache_cache_path('all/testspeedycache/'), 0755, true)){
			rmdir(speedycache_cache_path('all/testspeedycache/'));
		} else {
			array_push($message, '- /'.SPEEDYCACHE_WP_CONTENT_DIR.'/cache/speedycache/'.SPEEDYCACHE_SERVER_HOST.'/ permission has to be 755');
		}
	}

	if(count($message) > 0){
		return array(implode('<br>', $message), 'error');
	}
	
	return true;
}
<?php
/*
* SPEEDYCACHE
* https://speedycache.com/
* (c) SpeedyCache Team
*/

if(!defined('ABSPATH')){
	die('HACKING ATTEMPT!');
}

if(!isset($_REQUEST['security']) || strpos($_REQUEST['action'], 'speedycache_') !== 0){
	return;
}

// This is called FIRST by WP when AJAX is loaded. We will verify in this and if the nonce fails, this will die !
add_action('admin_init', 'speedycache_ajax_verify');

// AJAX Call Actions
add_action('wp_ajax_speedycache_delete_cache', 'speedycache_delete_cache_toolbar');
add_action('wp_ajax_speedycache_delete_cache_and_minified', 'speedycache_delete_css_and_js_cache_toolbar');
add_action('wp_ajax_speedycache_delete_current_page_cache', 'speedycache_delete_current_page_cache'); // Not
add_action('wp_ajax_speedycache_clear_cache_of_allsites', 'speedycache_clear_cache_of_allsites_callback'); // fn being called somewhere else too

// Toolbar AJAX actions
add_action('wp_ajax_speedycache_toolbar_save_settings', 'speedycache_toolbar_save_settings_callback');
add_action('wp_ajax_speedycache_toolbar_get_settings', 'speedycache_toolbar_get_settings_callback');

add_action('wp_ajax_speedycache_save_timeout_pages', 'speedycache_save_timeout_pages_callback');
add_action('wp_ajax_speedycache_save_exclude_pages', 'speedycache_save_exclude_pages_callback');

// CDN AJAX Actions
add_action('wp_ajax_speedycache_check_url', '\SpeedyCache\CDN::check_url');
add_action('wp_ajax_speedycache_cdn_options', '\SpeedyCache\CDN::options');
add_action('wp_ajax_speedycache_remove_cdn_integration', '\SpeedyCache\CDN::remove');
add_action('wp_ajax_speedycache_pause_cdn_integration', '\SpeedyCache\CDN::pause');
add_action('wp_ajax_speedycache_start_cdn_integration', '\SpeedyCache\CDN::start');
add_action('wp_ajax_speedycache_save_cdn_integration','\SpeedyCache\CDN::save');

// DB AJAX Actions
add_action('wp_ajax_speedycache_db_statics', 'speedycache_db_statics_callback');
add_action('wp_ajax_speedycache_db_fix', 'speedycache_db_fix_callback');

// Misc
add_action('wp_ajax_speedycache_cache_statics_get', 'speedycache_cache_statics_get_callback');
add_action('wp_ajax_get_server_time_ajax_request', 'speedycache_get_server_time_ajax_request');
add_action('wp_ajax_save_varniship', 'speedycache_save_varniship');
add_action('wp_ajax_speedycache_hide_promo', 'speedycache_hide_promo');
add_action('wp_ajax_speedycache_hide_nag', 'speedycache_hide_nag');

// Image AJAX call actions
if(defined('SPEEDYCACHE_PRO') && file_exists(SPEEDYCACHE_PRO_DIR . '/main/image.php')){
	add_action('wp_ajax_speedycache_revert_image_ajax_request', 'speedycache_img_revert_image_ajax_request');
	add_action('wp_ajax_speedycache_statics_ajax_request', 'speedycache_img_statics_ajax_request');
	add_action('wp_ajax_speedycache_optimize_image_ajax_request', 'speedycache_img_optimize_image_ajax_request');
	add_action('wp_ajax_speedycache_update_image_list_ajax_request', 'speedycache_update_image_list_ajax_request');
	add_action('wp_ajax_speedycache_update_image_settings', 'speedycache_img_update_settings');
	add_action('wp_ajax_speedycache_img_revert_all', 'speedycache_img_revert_all_ajax');
}

if(defined('SPEEDYCACHE_PRO')){
	add_action('wp_ajax_speedycache_critical_css', 'speedycache_critical_css');
	add_action('wp_ajax_speedycache_generate_single_ccss', 'speedycache_generate_single_ccss');
	add_action('wp_ajax_speedycache_flush_objects', 'speedycache_flush_objects');
	
	// Preloading Actions
	add_action('wp_ajax_speedycache_preloading_add_settings', 'speedycache_preloading_add_settings');
	add_action('wp_ajax_speedycache_preloading_delete_resource', 'speedycache_preloading_delete_resource');
}

// PageSpeed Test Actions
add_action('wp_ajax_speedycache_check_domain', 'speedycache_check_domain');
add_action('wp_ajax_speedycache_test_score', 'speedycache_test_score');
add_action('wp_ajax_speedycache_create_test_cache', 'speedycache_create_test_cache');
add_action('wp_ajax_speedycache_copy_test_settings', 'speedycache_copy_test_settings');

// Clear Cache Column
add_action('wp_ajax_speedycache_clear_cache_column',  'speedycache_column_clear_cache');

/****************************************************
*					Functions
*****************************************************/

function speedycache_ajax_verify(){
	
	$promo_nonce = ['speedycache_hide_nag', 'speedycache_hide_promo'];
	
	if(in_array($_REQUEST['action'], $promo_nonce)){
		if(empty(wp_verify_nonce($_REQUEST['security'], 'speedycache_promo_nonce'))){
			wp_send_json(array('success' => false, 'message' => 'Security check Failed'));
		}

		return;
	}
	
	if(empty(wp_verify_nonce($_REQUEST['security'], 'speedycache_nonce'))){
		wp_send_json(array('success' => false, 'message' => 'Security check Failed'));
	}
}

function speedycache_delete_cache_toolbar(){
	
	if(!current_user_can('manage_options')){
		wp_die('Must be admin');
	}

	speedycache_delete_cache();
}

function speedycache_delete_css_and_js_cache_toolbar(){
	
	if(!current_user_can('manage_options')){
		wp_die('Must be admin');
	}
	
	speedycache_delete_cache(true);
}

function speedycache_delete_current_page_cache(){
	
	if(!current_user_can('manage_options')){
		wp_die('Must be admin');
	}
	
	\SpeedyCache\CDN::purge();
	$path = '';

	if(!isset($_GET['path'])){
		wp_send_json(array('Path has NOT been defined', 'error', 'alert'));
	}

	if(!empty($_GET['path'])){
		if($_GET['path'] == '/'){
			$path = sanitize_text_field($_GET['path']).'index.html';
		}
	}else{
		$path = '/index.html';
	}

	$path = urldecode($path);

	// for security
	if(preg_match('/\.{2,}/', $path)){
		die('May be Directory Traversal Attack');
	}

	$paths = array();

	array_push($paths, speedycache_cache_path('all') . $path);

	if(defined('SPEEDYCACHE_PRO_DIR') && file_exists(SPEEDYCACHE_PRO_DIR . '/main/mobile.php')){
		\SpeedyCache\Mobile::cache();
		array_push($paths, speedycache_cache_path('mobile-cache'). $path);
	}

	foreach($paths as $key => $value){
		if(file_exists($value)){
			if(preg_match("/\/(all|mobile-cache)\/index\.html$/i", $value)){
				@unlink($value);
			}else{
				\SpeedyCache\Delete::rm_dir($value);
			}
		}
	}

	\SpeedyCache\Delete::multiple_domain_mapping_cache();

	wp_send_json(array('The cache of page has been cleared','success'));
}


function speedycache_clear_cache_of_allsites_callback(){

	if(!current_user_can('manage_options')){
		wp_die('Must be admin');
	}

	\SpeedyCache\CDN::purge();

	$path = speedycache_cache_path('*');

	$files = glob(speedycache_cache_path('*'));

	if(!is_dir(speedycache_cache_path('tmp_cache'))){
		if(@mkdir(speedycache_cache_path('tmp_cache'), 0755, true)){
			//tmp_cache has been created
		}
	}

	foreach((array)$files as $file){
		@rename($file, speedycache_cache_path('tmp_cache/').basename($file).'-'.time());
	}

	if(is_admin() && defined('DOING_AJAX') && DOING_AJAX){
		wp_send_json(array('message' => 'The cache of page has been cleared', 'success' => 'true'));
	}
}

function speedycache_toolbar_save_settings_callback(){
	//Security check
	speedycache_verify_nonce(speedycache_optget('security'), 'speedycache_nonce');
	
	if(!current_user_can('manage_options')){
		wp_die('Must be admin');
	}
	
	$roles = speedycache_optget('roles');
	
	if(empty($roles) || !is_array($roles)){
		delete_option('speedycache_toolbar_settings');
		
		wp_send_json(array('success' => true));
	}

	$roles_arr = array();

	foreach($roles as $key => $value){
		$value = esc_html(esc_sql($value));
		$key = esc_html(esc_sql($key));

		$roles_arr[$key] = $value;
	}

	if(get_option('speedycache_toolbar_settings') === false){
		update_option('speedycache_toolbar_settings', $roles_arr, 1, 'no');
	}else{
		update_option('speedycache_toolbar_settings', $roles_arr);
	}

	wp_send_json(array('success' => true));
}


function speedycache_toolbar_get_settings_callback(){
	//Security Check
	speedycache_verify_nonce(speedycache_optget('security'), 'speedycache_nonce');
	
	if(!current_user_can('manage_options')){
		wp_die('Must be admin');
	}
	
	$result = array('success' => true, 'roles' => false);

	$speedycache_role_status = get_option('speedycache_toolbar_settings');
	if(is_array($speedycache_role_status) && !empty($speedycache_role_status)){
		$result['roles'] = $speedycache_role_status;
	}

	wp_send_json($result);
}


function speedycache_save_timeout_pages_callback(){
	speedycache_verify_nonce(speedycache_optpost('security'), 'speedycache_nonce');

	if(!current_user_can('manage_options')){
		wp_die('Must be admin');
	}
	
	speedycache_set_custom_interval();

	$crons = get_option('cron');

	foreach($crons as $cron_key => $cron_value){
		foreach( (array) $cron_value as $hook => $events){

			if(preg_match('/^speedycache(.*)/', $hook, $id)){

				if(isset($id[1]) || preg_match('/^\_(\d+)$/', $id[1])){
					
					foreach((array) $events as $event_key => $event){
						if(isset($id[1])){
							wp_clear_scheduled_hook('speedycache'.$id[1], $event['args']);
						}
					}
				}
			}
		}
	}
	
	$rules = speedycache_optpost('rules');
	
	if(!empty($rules) && count($rules) > 0){
		$i = 0;

		foreach($rules as $key => $value){
			if(preg_match('/^(daily|onceaday)$/i', $value['schedule']) && isset($value['hour']) && isset($value['minute']) && strlen($value['hour']) > 0 && strlen($value['minute']) > 0){
				$args = array('prefix' => $value['prefix'], 'content' => $value['content'], 'hour' => $value['hour'], 'minute' => $value['minute']);

				$timestamp = mktime($value['hour'], $value['minute'], 0, date('m'), date('d'), date('Y'));

				$timestamp = $timestamp > time() ? $timestamp : $timestamp + 60*60*24;
			}else{
				$args = array('prefix' => $value['prefix'], 'content' => $value['content']);
				$timestamp = time();
			}

			wp_schedule_event($timestamp, $value['schedule'], 'speedycache_'.$i, array($args));
			$i = $i + 1;
		}
	}

	wp_send_json(array('success' => true));
}


function speedycache_save_exclude_pages_callback(){
	speedycache_verify_nonce(speedycache_optpost('security'), 'speedycache_nonce');
	
	if(!current_user_can('manage_options')){
		wp_die('Must be admin');
	}
	
	$rules = speedycache_optpost('rules');
	
	if(!empty($rules)){
		
		foreach($rules as $key => &$value){
			$value['prefix'] = strip_tags($value['prefix']);
			$value['content'] = strip_tags($value['content']);

			$value['prefix'] = preg_replace("/\'|\"/", '', $value['prefix']);
			$value['content'] = preg_replace("/\'|\"/", '', $value['content']);

			$value['content'] = trim($value['content'], '/');

			$value['content'] = preg_replace("/(\#|\s|\(|\)|\*)/", '', $value['content']);

			if($value['prefix'] == 'homepage'){
				speedycache_delete_home_page_cache(false);
			}
		}

		if(get_option('speedycache_exclude')){
			update_option('speedycache_exclude', $rules);
		}else{
			update_option('speedycache_exclude', $rules, null, 'yes');
		}
	}else{
		delete_option('speedycache_exclude');
	}

	\SpeedyCache\htaccess::add_exclude();

	wp_send_json(array('success' => true));
}

function speedycache_cache_statics_get_callback(){
	speedycache_verify_nonce(speedycache_optget('security'), 'speedycache_nonce');
	
	if(!current_user_can('manage_options')){
		wp_die('Must be admin');
	}
	
	if(defined('SPEEDYCACHE_PRO') && file_exists(SPEEDYCACHE_PRO_DIR.'/main/statistics.php')){
		\SpeedyCache\Statistics::init();
		$res = \SpeedyCache\Statistics::get();
		wp_send_json($res);
	}
}

function speedycache_db_statics_callback(){
	global $wpdb;
	
	if(!wp_verify_nonce(speedycache_optpost('security'), 'speedycache_nonce')){
		wp_send_json(array('success' => false, 'message' => 'Security check'));
	}
	
	if(!current_user_can('manage_options')){
		wp_die('Must be admin');
	}
	
	$statics = array('all_warnings' => 0, 'post_revisions' => 0, 'trashed_contents' => 0, 'trashed_spam_comments' => 0, 'trackback_pingback' => 0, 'transient_options' => 0, 'expired_transient' => 0);

	
	$statics['post_revisions'] = $wpdb->get_var("SELECT COUNT(*) FROM `$wpdb->posts` WHERE post_type = 'revision';");

	$statics['trashed_contents'] = $wpdb->get_var("SELECT COUNT(*) FROM `$wpdb->posts` WHERE post_status = 'trash';");

	$statics['trashed_spam_comments'] = $wpdb->get_var("SELECT COUNT(*) FROM `$wpdb->comments` WHERE comment_approved = 'spam' OR comment_approved = 'trash' ;");

	$statics['trackback_pingback'] = $wpdb->get_var("SELECT COUNT(*) FROM `$wpdb->comments` WHERE comment_type = 'trackback' OR comment_type = 'pingback' ;");

	$element = "SELECT COUNT(*) FROM `$wpdb->options` WHERE option_name LIKE '%\_transient\_%' ;";
	$statics['transient_options'] = $wpdb->get_var( $element ) > 20 ? $wpdb->get_var( $element ) : 0;	

	$statics['expired_transient'] = $wpdb->get_var( "SELECT COUNT(*) FROM `$wpdb->options` WHERE option_name LIKE '_transient_timeout%' AND option_value < " . time() );

	$statics['all_warnings'] = $statics['all_warnings'] + $statics['transient_options'] + $statics['trackback_pingback']+ $statics['trashed_spam_comments']+ $statics['trashed_contents']+ $statics['post_revisions'];
	
	wp_send_json($statics);
}

function speedycache_db_fix_callback(){
	if(!defined('SPEEDYCACHE_PRO')){
		return;
	}
	
	if(!wp_verify_nonce(speedycache_optget('security'), 'speedycache_nonce')){
		wp_send_json(array('success' => false, 'message' => 'Security check'));
	}
	
	if(!current_user_can('manage_options')){
		wp_die('Must be admin');
	}
	
	\SpeedyCache\DB::clean(speedycache_optget('type'));
}

function speedycache_get_server_time_ajax_request(){
	
	if(!current_user_can('manage_options')){
		wp_die('Must be admin');
	}
	
	$servers = speedycache_optget('servers');
	
	foreach((array)$servers as $key => $value){
		$servers[$key]['time'] = speedycache_get_server_time($value['url']);

		if($servers[$key]['time']['time'] === 0){
			unset($servers[$key]);
		}
	}

	wp_send_json($servers);
}

function speedycache_get_server_time($url){
	
	if(!current_user_can('manage_options')){
		wp_die('Must be admin');
	}
	
	$result = array('success' => true,
					'time' => 0);

	if(function_exists('fsockopen')){
		$port = preg_match('/^https/', $url) ? 443 : 80;

		$url = preg_replace("/https?\:\/\//", '', $url);

		$start_time = microtime(true);

		$file      = @fsockopen($url, 443, $errno, $errstr, 1);
		$stoptime  = microtime(true);
		$status    = 0;

		//echo $stoptime."\n\n";

		if(!$file){
			$status = 1000;  // Site is down
		}else{
			fclose($file);
			$status = ($stoptime - $start_time);
		}

		$result['time'] = round($status, 3);

	}else if(function_exists('curl_init')){
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 1);

		if(curl_exec($ch)){
			$info = curl_getinfo($ch);
		}

		curl_close($ch);

		if(isset($info['http_code']) && ($info['http_code'] == 200)){
			$result['time'] = round($info['total_time'], 3);
		}else{
			$result['time'] = 1000;
		}
	}else{
		$result['time'] = 0;
		$result['success'] = false;
	}

	return $result;
}

/****************************************************
*					Image Functions
*****************************************************/

function speedycache_img_revert_all_ajax(){
	
	if(!current_user_can('manage_options')){
		wp_die('Must be admin');
	}
	
	\SpeedyCache\Image::revert_all();
}

function speedycache_img_update_settings(){
	global $speedycache;
	
	if(!current_user_can('manage_options')){
		wp_die('Must be admin');
	}

	$settings = speedycache_optpost('settings');
	
	foreach($settings as $key => $setting){		
		$new_key = str_replace('img_', '', $key);
		
		$settings[$new_key] = $setting;
		unset($settings[$key]);
	}
	

	$speedycache->image['settings'] = $settings;
	
	if(update_option('speedycache_img', $speedycache->image['settings'])){		
		wp_send_json(array('success' => true));
	}
	
	wp_send_json(array('success' => false));
}

function speedycache_update_image_list_ajax_request(){
	
	if(!current_user_can('manage_options')){
		wp_die('Must be admin');
	}
	
	$query_images_args = array();
	$query_images_args['offset'] = intval(speedycache_optget('page')) * intval(speedycache_optget('per_page'));
	$query_images_args['order'] = 'DESC';
	$query_images_args['orderby'] = 'ID';
	$query_images_args['post_type'] = 'attachment';
	$query_images_args['post_mime_type'] = array('image/jpeg', 'image/png', 'image/gif');
	$query_images_args['post_status'] = 'inherit';
	$query_images_args['posts_per_page'] = speedycache_optget('per_page');
	$query_images_args['meta_query'] = array(
								array(
									'key' => 'speedycache_optimisation',
									'compare' => 'EXISTS'
									)
								);

	$query_images_args['s'] = speedycache_optget('search');

	if(!empty($_GET['filter'])){
		if($_GET['filter'] == 'error_code'){
			
			$filter = array(
				'key' => 'speedycache_optimisation',
				'value' => base64_encode('"error_code"'),
				'compare' => 'LIKE'
			);

			$filter_second = array(
				'key' => 'speedycache_optimisation',
				'compare' => 'NOT LIKE'
			);

			array_push($query_images_args['meta_query'], $filter);
			array_push($query_images_args['meta_query'], $filter_second);
		}
	}

	$result = array(
		'content' => \SpeedyCache\Image::list_content($query_images_args),
		'result_count' => \SpeedyCache\Image::count_query($query_images_args)
	);

	wp_send_json($result);
}

function speedycache_img_optimize_image_ajax_request(){
	
	if(!current_user_can('manage_options')){
		wp_die('Must Be admin');
	}
	
	$res = \SpeedyCache\Image::optimize_single();
	$res[1] = isset($res[1]) ? $res[1] : '';
	$res[2] = isset($res[2]) ? $res[2] : '';
	$res[3] = isset($res[3]) ? $res[3] : '';
	
	$response = array(
		'message' => $res[0],
		'success' => $res[1],
		'id' => $res[2],
		'percentage' => $res[3],
	);
	
	wp_send_json($response);
}

function speedycache_img_statics_ajax_request(){
	
	if(!current_user_can('manage_options')){
		wp_die('Must be admin');
	}
	
	$res = \SpeedyCache\Image::statics_data();
	wp_send_json($res);
}

function speedycache_img_revert_image_ajax_request(){
	global $speedycache;
	
	if(!current_user_can('manage_options')){
		wp_die('Must Be admin');
	}
	
	if(!empty($_GET['id'])){
		$speedycache->image['id'] = (int) speedycache_optget('id');
	}
	
	wp_send_json(\SpeedyCache\Image::revert());
}


function speedycache_column_clear_cache(){
	
	if(!current_user_can('manage_options')){
		wp_die('Must be admin');
	}
	
	speedycache_single_delete_cache(false, esc_sql($_GET['id']));
	wp_send_json(array('success' => true));
}

function speedycache_save_varniship(){
	global $speedycache;
	
	if(!current_user_can('manage_options')){
		wp_die('Must be admin');
	}

	$speedycache->options['varniship'] = speedycache_optget('varniship', '127.0.0.1');
	update_option('speedycache_options', $speedycache->options);
}

function speedycache_critical_css(){
	global $speedycache;
	
	if(!current_user_can('manage_options')){
		wp_die('Must be admin');
	}
	
	if(empty($speedycache->license['license'])){
		wp_send_json_error(array('message' => 'You have not linked your License, please do it before creating Critical CSS'));
	}

	$urls = \SpeedyCache\CriticalCss::get_url_list();

	if(empty($urls)){
		wp_send_json_error(array('message' => 'No URL found to create critical CSS'));
	}
	
	\SpeedyCache\CriticalCss::schedule('speedycache_generate_ccss', $urls);
	
	wp_send_json_success(array('message' => 'The URLs have been queued to generate Critical CSS'));
}

function speedycache_generate_single_ccss(){
	global $speedycache;
	
	if(!current_user_can('manage_options')){
		wp_die('Must be admin');
	}
	
	if(empty($speedycache->license['license'])){
		wp_send_json_error(array('message' => 'You have not linked your License, please do it before creating Critical CSS'));
	}
	
	$post_id = speedycache_optpost('post_id');
	
	if(empty($post_id)){
		wp_send_json_error(array('message' => 'No post ID found'));
	}
	
	$url = get_permalink($post_id);
	
	if(empty($url)){
		wp_send_json_error(array('message' => 'NO URL found for the given post'));
	}
	
	$res = \SpeedyCache\CriticalCss::generate([$url]);
	
	if($res === true){
		wp_send_json_success(array('message' => 'CriticalCSS created successfully for this page'));
	}

	wp_send_json_error(array('message' => !empty($res) ? esc_html($res) : __('Was unable to generate CriticalCss', 'speedycache')));
	
}

function speedycache_hide_promo(){
	
	if(!current_user_can('manage_options')){
		wp_die('Must be admin');
	}
	
	update_option('speedycache_promo_time', (0 - time()));
	die('DONE');
}

function speedycache_flush_objects(){
	
	if(!current_user_can('manage_options')){
		wp_die('Must be admin');
	}
	
	try{
		\SpeedyCache\ObjectCache::boot();
	} catch(Exception $e){
		wp_send_json_error(array('message' => $e->getMessage()));
	}
	
	$res = \SpeedyCache\ObjectCache::flush_db();
	
	if(!empty($res)){
		wp_send_json_success(array('message' => 'Object DB purged successfully'));
	}
	
	wp_send_json_error(array('message' => 'There was some issue purging the Object DB'));
}

function speedycache_hide_nag(){
	
	if(!current_user_can('manage_options')){
		wp_die('Must be admin');
	}
	
	update_option('speedycache_enable_nag', time());
	die('DONE');
}

function speedycache_check_domain(){
	
	if(!current_user_can('manage_options')){
		wp_die('Must be admin');
	}

	$url = sanitize_url($_REQUEST['url']);
	$settings = map_deep($_REQUEST['settings'], 'sanitize_text_field');
	
	// We will always use Delay JS mode as All in the test
	if(defined('SPEEDYCACHE_PRO') && !empty($settings['delay_js'])){
		$settings['delay_js_mode'] = 'all';
	}
	
	// Test will always be in test mode enabled.
	set_transient('speedycache_test_mode', true, 1800);
	
	set_transient('speedycache_test_settings', $settings, 1800);

	$ip = gethostbyname($url);
	
	if(empty($ip)){
		wp_send_json_error();
	}

	\SpeedyCache\Delete::rm_dir(speedycache_cache_path('test'));
	
	// Purging Old test pages
	wp_send_json_success();

}

function speedycache_test_score(){
	
	if(!current_user_can('manage_options')){
		wp_die('Must be admin');
	}

	$url = sanitize_url($_GET['url']);

	$api_url = SPEEDYCACHE_API . 'pagespeed.php?url='. $url; 
	
	if(!empty($_GET['test_speedycache'])){
		$api_url .= '?test_speedycache=1';
	}

	$res = wp_remote_post($api_url, array(
		'sslverify' => false,
		'timeout' => 30
	));
	
	if(empty($res) || is_wp_error($res)){
		wp_send_json_error();
	}
	
	if(empty($res['body'])){
		wp_send_json_error();
	}
	
	$body = json_decode($res['body'], 1);
	
	if(empty($body['success'])){
		wp_send_json_error();
	}
	
	if(empty($body['results'])){
		wp_send_json_error();
	}

	// Saving data to keep last test
	if(!empty($_GET['test_speedycache'])){
		update_option('speedycache_new_speed', $body['results']);
	} else {
		update_option('speedycache_old_speed', $body['results']);
	}
	
	wp_send_json_success($body['results']);
	
}

function speedycache_create_test_cache(){
	
	if(!current_user_can('manage_options')){
		wp_send_json_error('You dont have required privilage to use this feature.');
	}
	
	$url = esc_url(sanitize_url('url'));

	$res = wp_safe_remote_get($url . '?test_speedycache=1', array('timeout' => 30, 'headers' => ['User-agent' => 'SpeedyCacheTest']));
	
	wp_send_json_success();
}

function speedycache_copy_test_settings(){
	global $speedycache;
	
	if(!current_user_can('manage_options')){
		wp_die('Must be admin');
	}
	
	$test_settings = get_transient('speedycache_test_settings');
	
	if(empty($test_settings)){
		wp_send_json_error('The Test settings has expired, please analyse again.');
	}
	
	$speedycache->options = $test_settings;
	$speedycache->options['status'] = true;
	
	update_option('speedycache_options', $speedycache->options);
	
	wp_send_json_success();
}

// Adds settings of Preload and preconnect options.
function speedycache_preloading_add_settings(){
	global $speedycache;
	
	if(!current_user_can('manage_options')){
		wp_die('Must be admin');
	}
	
	if(empty($_REQUEST['type'])){
		wp_send_json_error('Unable to find the settings type');
	}
	
	$type = sanitize_text_field($_REQUEST['type']);

	if(!in_array($type, ['pre_connect_list', 'preload_resource_list'])){
		wp_send_json_error('Could not figure out type of the setting being saved!');
	}

	if(empty($_REQUEST['settings'])){
		wp_send_json_error('No settings provided to save');
	}
	
	if(empty($speedycache->options[$type])){
		$speedycache->options[$type] = [];
	}

	$settings = map_deep($_REQUEST['settings'], 'sanitize_text_field');
	$settings['resource'] = esc_url_raw($settings['resource']);

	if(empty($settings['resource'])){
		wp_send_json_error('No resource provided!');
	}

	$index = count($speedycache->options[$type]);
	
	if(empty($speedycache->options[$type])){
		$speedycache->options[$type][$index] = $settings;
		update_option('speedycache_options', $speedycache->options);
		wp_send_json_success($index);
	}
	
	foreach($speedycache->options[$type] as $pre_connect){
		if($pre_connect['resource'] == $settings['resource']){
			wp_send_json_error('This resource has already been added before');
		}
	}
	
	$speedycache->options[$type][$index] = $settings;
	update_option('speedycache_options', $speedycache->options);

	wp_send_json_success($index);

}

function speedycache_preloading_delete_resource(){
	global $speedycache;
	
	if(!current_user_can('manage_options')){
		wp_die('Must be admin');
	}
	
	if($_REQUEST['key'] == NULL || empty($_REQUEST['type'])){
		wp_send_json_error('Key or Type is empty so can not delete this resource');
	}
	
	$type = sanitize_text_field($_REQUEST['type']);
	$key = sanitize_text_field($_REQUEST['key']);
	
	if(!in_array($type, ['pre_connect_list', 'preload_resource_list'])){
		wp_send_json_error('Could not figure out type of the resource being deleted!');
	}

	if(empty($speedycache->options[$type])){
		wp_send_json_error('Nothing there to delete');
	}
	
	if(array_key_exists($key, $speedycache->options[$type])){
		unset($speedycache->options[$type][$key]);
		update_option('speedycache_options', $speedycache->options);
	}

	wp_send_json_success();
}
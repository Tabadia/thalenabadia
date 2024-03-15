<?php
/*
* SPEEDYCACHE
* https://speedycache.com/
* (c) SpeedyCache Team
*/

//ABSPATH is required.	
if(!defined('ABSPATH')) exit;

define('SPEEDYCACHE_VERSION', '1.1.4');
define('SPEEDYCACHE_DIR', dirname( __FILE__ ));
define('SPEEDYCACHE_BASE', plugin_basename(SPEEDYCACHE_FILE));
define('SPEEDYCACHE_URL', plugins_url('', __FILE__));
define('SPEEDYCACHE_BASE_NAME', basename(SPEEDYCACHE_DIR));
define('SPEEDYCACHE_WP_CONTENT_DIR', defined('WP_CONTENT_FOLDERNAME') ? WP_CONTENT_FOLDERNAME : 'wp-content'); 
define('SPEEDYCACHE_CACHE_URL', content_url('/cache/speedycache'));
define('SPEEDYCACHE_DEV', file_exists(dirname(__FILE__).'/DEV.php') ? 1 : 0);

function speedycache_died(){
	speedycache_log(error_get_last());
}

if(SPEEDYCACHE_DEV){
	include_once SPEEDYCACHE_DIR.'/DEV.php';
	//register_shutdown_function('speedycache_died');
}

if(!defined('SPEEDYCACHE_API')){
	define('SPEEDYCACHE_API', 'https://api.speedycache.com/');
}

if(!class_exists('SpeedyCache')){
#[\AllowDynamicProperties]
class SpeedyCache{
	public $options = array();
	public $brand_name = 'SpeedyCache';
	public $logs;
	public $settings;
	public $license;
	public $image;
	public $mobile_cache;
	public $columnjs;
	public $js;
	public $css_util;
	public $render_blocking;
	public $enhanced;
	public $object;
	public $bloat;
}
}

function speedycache_autoloader($class){
	
	if(!preg_match('/^SpeedyCache\\\(.*)/is', $class, $m)){
		return;
	}
	
	// For Free
	if(file_exists(SPEEDYCACHE_DIR.'/main/'.strtolower($m[1]).'.php')){
		include_once(SPEEDYCACHE_DIR.'/main/'.strtolower($m[1]).'.php');
	}
	
	// For Pro
	if(defined('SPEEDYCACHE_PRO_DIR') && file_exists(SPEEDYCACHE_PRO_DIR.'/main/'.strtolower($m[1]).'.php')){
		include_once(SPEEDYCACHE_PRO_DIR.'/main/'.strtolower($m[1]).'.php');
	}
}

spl_autoload_register(__NAMESPACE__.'\speedycache_autoloader');

include_once SPEEDYCACHE_DIR . '/functions.php';

register_activation_hook( __FILE__, '\SpeedyCache\Install::activate');
register_deactivation_hook( __FILE__, '\SpeedyCache\Install::deactivate');

// Looks if SpeedyCache just got updated
function speedycache_update_check(){

	$sql = array();
	$current_version = get_option('speedycache_version');	
	$version = (int) str_replace('.', '', $current_version);

	// No update required
	if($current_version == SPEEDYCACHE_VERSION){
		return true;
	}
	
	speedycache_check_cache_path_writeable();
	
	// Is it first run ?
	if(empty($current_version)){
		\SpeedyCache\Install::activate();
		return;
	}

	if($version < 102){
	
		\SpeedyCache\htaccess::modify();
		
		global $wp_filesystem;

		include_once ABSPATH . 'wp-admin/includes/file.php';
		WP_Filesystem();
		
		$old_dir = speedycache_get_wp_content_dir('/speedycache');
		
		if(file_exists($old_dir) && method_exists($wp_filesystem, 'delete')){
			$wp_filesystem->delete($old_dir, true);
		}

	}

	// Save the new Version
	update_option('speedycache_version', SPEEDYCACHE_VERSION);
	
}

//Add action to load SpeedyCache
add_action('plugins_loaded', 'speedycache_load_plugin');

function speedycache_load_plugin(){

	global $speedycache;
	
	if(empty($speedycache)){
		$speedycache = new SpeedyCache();
	}

	$speedycache->options = get_option('speedycache_options', []);
	$speedycache->options['post_types'] = empty($speedycache->options['post_types']) ? ['page', 'post', 'product', 'docs'] :$speedycache->options['post_types'];
	$speedycache->settings['noscript'] = '';
	$speedycache->settings['content_url'] = '';
	$speedycache->settings['is_multi'] = is_multisite();
	$speedycache->settings['system_message'] = array();
	$speedycache->settings['cron_job_settings'] = ''; //only used in function.php
	$speedycache->settings['block_cache'] = false;
	$speedycache->settings['preload_exclude_rules'] = false; //only used in preload file
	$speedycache->settings['deleted_before'] = false;
	$speedycache->settings['cdn'] = get_option('speedycache_cdn');
	$speedycache->image['settings'] = get_option('speedycache_img') ? get_option('speedycache_img') : array();
	$speedycache->license = get_option('speedycache_license');
	$speedycache->object = get_option('speedycache_object_cache', ['admin' => true, 'persistent' => true]);
	$speedycache->bloat = get_option('speedycache_bloat', []);
	
	if(wp_doing_ajax()){
		include_once SPEEDYCACHE_DIR . '/main/ajax.php';
	}
	
	// Load the bloat class and remove the bloat
	if(defined('SPEEDYCACHE_PRO') && !empty($speedycache->bloat)){
		\SpeedyCache\Bloat::actions();
	}

	speedycache_set_host();
	speedycache_update_check();
	speedycache_check_cron();
	speedycache_get_content_url();
	
	// Load any schedule if there !
	if(wp_next_scheduled('speedycache')){
		$speedycache->settings['cron_job_settings']['period'] = wp_get_schedule('speedycache');
		$speedycache->settings['cron_job_settings']['time'] = wp_next_scheduled('speedycache');
	}

	add_action('edit_terms', '\SpeedyCache\Delete::cache_of_term', 10, 1);
	add_action('speedycache_preload', '\SpeedyCache\Precache::create');

	speedycache_set_custom_interval();

	if(is_admin()){
		include_once SPEEDYCACHE_DIR . '/main/admin.php';
	}

	// Handle other plugins actions
	speedycache_other_plugins_actions();
	
	// To clear /tmp_cache folder
	if(is_dir(speedycache_cache_path('tmp_cache'))){
		\SpeedyCache\Delete::rm_dir(speedycache_cache_path('tmp_cache'));
	}

	add_action('transition_post_status', '\SpeedyCache\Delete::on_status_transitions', 10, 3);
	
	// Comment hooks
	add_action('wp_set_comment_status', 'speedycache_single_delete_cache', 10, 1); // Works when the status of a comment changes
	add_action('comment_post', '\SpeedyCache\Delete::comment_post', 10, 2); // Works when a comment is saved in the database
	add_action('edit_comment', '\SpeedyCache\Delete::edit_comment', 10, 2); // Works when a comment is updated

	if(defined('SPEEDYCACHE_PRO') && is_admin() && current_user_can('activate_plugins')){
		// The promo time
		$promo_time = get_option('speedycache_promo_time');
		if(empty($promo_time)){
			$promo_time = time();
			update_option('speedycache_promo_time', $promo_time);
		}

		// Are we to show the SpeedyCache promo
		if(!empty($promo_time) && $promo_time > 0 && $promo_time < (time() - (7 * 86400))){
			add_action('admin_notices', 'speedycache_promo');
		}
		
		// Enable caching nag.
		$enable_nag = get_option('speedycache_enable_nag');
		
		// Are we to show the SpeedyCache promo
		if((empty($enable_nag) || (time() - 604800 > $enable_nag)) && empty($speedycache->options['status']) && empty($speedycache->object['enable'])){
			add_action('admin_notices', 'speedycache_enable_nag_handler');
		}
	}

	if(!is_admin()){

		// Optimizes images when a page gets loaded and it finds no image optimized
		if(defined('SPEEDYCACHE_PRO') && !empty($speedycache->image['settings']['automatic_optm'])){
			add_filter('the_content', '\SpeedyCache\Image::optimize_on_fly');
		}

		// Image URL rewrite
		if(defined('SPEEDYCACHE_PRO') && file_exists(SPEEDYCACHE_PRO_DIR . '/main/image.php') && !empty($speedycache->image['settings']['url_rewrite'])){
			add_filter('the_content', 'SpeedyCache\Image::rewrite_url_to_webp', 10);
		}

		include_once SPEEDYCACHE_DIR . '/main/front.php';

	}
}

function speedycache_other_plugins_actions(){
	
	global $speedycache;
	
	if(defined('SPEEDYCACHE_CLEAR_CACHE_AFTER_PLUGIN_UPDATE')){
		add_action('upgrader_process_complete', '\SpeedyCache\TPP::clear_after_update_plugin', 10, 2);
	}

	if(defined('SPEEDYCACHE_CLEAR_CACHE_AFTER_THEME_UPDATE')){
		add_action('upgrader_process_complete', '\SpeedyCache\TPP::clear_after_update_theme', 10, 2);
	}

	if(defined('SPEEDYCACHE_DISABLE_CLEARING_CACHE_AFTER_WOOCOMMERCE_CHECKOUT_ORDER_PROCESSED')){
		// do nothing
	}else if(defined('SPEEDYCACHE_DISABLE_CLEARING_CACHE_AFTER_WOOCOMMERCE_ORDER_STATUS_CHANGED')){
		
	}else{
		// to clear cache after new Woocommerce orders
		add_action('woocommerce_order_status_changed', '\SpeedyCache\TPP::clear_cache_after_woocommerce_order_status_changed', 1, 1);
	}
	
	add_action('rate_post', '\SpeedyCache\TPP::postratings_clear_cache', 10, 2);
	
	// kk Star Ratings: to clear the cache of the post after voting
	add_action('kksr_rate', '\SpeedyCache\TPP::clear_cache_on_kksr_rate');

	// Elementor: to clear cache after Maintenance Mode activation/deactivation
	add_action('elementor/maintenance_mode/mode_changed', 'speedycache_delete_cache');

	// to clear cache after ajax request by other plugins
	if(isset($_POST['action'])){
		// All In One Schema.org Rich Snippets
		if(preg_match('/bsf_(update|submit)_rating/i', sanitize_text_field(wp_unslash($_POST['action'])))){
			if(isset($_POST['post_id'])){
				speedycache_single_delete_cache(false, sanitize_text_field(wp_unslash($_POST['post_id'])));
			}
		}

		// Yet Another Stars Rating
		if($_POST['action'] == 'yasr_send_visitor_rating'){
			if(isset($_POST['post_id'])){
				// to need call like that because get_permalink() does not work if we call speedycache_single_delete_cache() directly
				add_action('init', 'speedycache_single_delete_cache');
			}
		}
	}
	
	// When the regular price is updated, the transition_post_status action cannot catch it
	add_action('woocommerce_update_product', '\SpeedyCache\TPP::clear_cache_after_woo_update_product', 10, 1);
	
}

function speedycache_get_abspath(){
	$path = ABSPATH;
	
	if(!speedycache_is_dir_install()){
		return $path;
	}
	
	$site_url = site_url();
	$home_url = home_url();
	$diff = str_replace($home_url, '', $site_url);
	$diff = trim($diff,'/');

	$pos = strrpos($path, $diff);

	if($pos !== false){
		$path = substr_replace($path, '', $pos, strlen($diff));
		$path = trim($path,'/');
		$path = '/'.$path.'/';
	}
	
	return $path;
}

function speedycache_get_content_url(){
	
	global $speedycache;
	
	$content_url = content_url();

	// Hide My WP
	if(speedycache_is_plugin_active('hide_my_wp/hide-my-wp.php')){
		$hide_my_wp = get_option('hide_my_wp');

		if(isset($hide_my_wp['new_content_path']) && $hide_my_wp['new_content_path']){
			$hide_my_wp['new_content_path'] = trim($hide_my_wp['new_content_path'], '/');
			$content_url = str_replace(basename(WP_CONTENT_DIR), $hide_my_wp['new_content_path'], $content_url);
		}
	}

	// To change content url if a different url is used for other langs
	if(speedycache_is_plugin_active('polylang/polylang.php')){
		$url =  parse_url($content_url);

		if(!empty($_SERVER['HTTP_HOST']) && $url['host'] !== $_SERVER['HTTP_HOST']){
			$protocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? 'https://' : 'http://';
			$content_url = $protocol . sanitize_text_field($_SERVER['HTTP_HOST']) . $url['path'];
		}
	}

	if(!defined('SPEEDYCACHE_WP_CONTENT_URL')){
		define('SPEEDYCACHE_WP_CONTENT_URL', $content_url);
	}
	
	$speedycache->settings['content_url'] = $content_url;
	
}

function speedycache_check_cron(){
	$crons = _get_cron_array();

	foreach ((array)$crons as $cron_key => $cron_value) {
		foreach ( (array) $cron_value as $hook => $events ) {
			if(preg_match("/^speedycache(.*)/", $hook, $id)){
				if(!$id[1] || preg_match("/^\_(\d+)$/", $id[1])){
					foreach ( (array) $events as $event_key => $event ) {
						add_action('speedycache'.$id[1],  'speedycache_set_schedule');
					}
				}
			}
		}
	}

	add_action('speedycache_preload',  '\SpeedyCache\Precache::create', 11);
}

function speedycache_get_wp_content_dir($path = false){
	/*
	Sample Paths;

	/speedycache/

	/speedycache/{HOST}/all/
	./all
	./all/page
	./all/index.html
	./all/testspeedycache/

	/speedycache/assets

	/speedycache/{HOST}/mobile-cache/
	./mobile-cache/page
	./mobile-cache/index.html
	
	/speedycache/tmp_cache
	./tmp_cache/
	./tmp_cache/mobile_
	./tmp_cache/m
	./tmp_cache/w

	/speedycache/testspeedycache/	
	*/
	
	if(empty($path)){
		return WP_CONTENT_DIR;
	}
	
	if(!preg_match('/\/speedycache\/('.preg_quote(SPEEDYCACHE_SERVER_HOST).')/', $path)){
		return WP_CONTENT_DIR . $path;
	}

	//WPML language switch
	//https://wpml.org/forums/topic/wpml-language-switch-speedycache-issue/
	$language_negotiation_type = apply_filters('wpml_setting', false, 'language_negotiation_type');
	if(($language_negotiation_type == 2) && speedycache_is_plugin_active('sitepress-multilingual-cms/sitepress.php')){
		$my_home_url = apply_filters('wpml_home_url', get_option('home'));
		$my_home_url = preg_replace('/https?\:\/\//i', '', $my_home_url);
		$my_home_url = trim($my_home_url, '/');

		$path = str_replace(SPEEDYCACHE_SERVER_HOST, $my_home_url.'/', $path);
	}

	return WP_CONTENT_DIR.$path;
}

// Show the promo
function speedycache_promo(){
	if(!function_exists('speedycache_base_promo')){
		include_once(SPEEDYCACHE_DIR.'/main/promo.php');
	}
	speedycache_base_promo();
}

function speedycache_enable_nag_handler(){
	if(!function_exists('speedycache_enable_nag')){
		include_once SPEEDYCACHE_DIR . '/main/promo.php';
	}
	speedycache_enable_nag();
}

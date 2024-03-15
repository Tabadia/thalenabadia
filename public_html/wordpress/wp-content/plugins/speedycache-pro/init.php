<?php

if(!defined('ABSPATH')){
	die('Hacking Attempt!');
}

// Constants
define('SPEEDYCACHE_PRO_VERSION', '1.1.4');
define('SPEEDYCACHE_PRO_DIR', dirname(__FILE__));
define('SPEEDYCACHE_PRO_BASE', 'speedycache-pro/speedycache-pro.php');
define('SPEEDYCACHE_PRO_BASE_NAME', basename(SPEEDYCACHE_PRO_DIR));
define('SPEEDYCACHE_PRO_URL', plugins_url('', __FILE__));

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

add_action('plugins_loaded', 'speedycache_pro_load_plugin');
register_activation_hook( __FILE__, 'speedycache_pro_activate');


function speedycache_pro_load_plugin(){
	global $speedycache;
	
	if(empty($speedycache)){
		$speedycache = new SpeedyCache();
	}

	// Actions to handle WP Cron schedules
	add_action('speedycache_auto_optm', '\SpeedyCache\Image::auto_optimize', 10, 1);
	add_action('speedycache_img_delete', '\SpeedyCache\Image::scheduled_delete', 10, 1);
	add_action('speedycache_generate_ccss', '\SpeedyCache\CriticalCss::generate', 10, 1);
	add_action('speedycache_unused_css', '\SpeedyCache\UnusedCss::generate', 10, 1);
	
	speedycache_pro_update_check();
	
	if(!is_admin() && !current_user_can('activate_plugins')){
		return;
	}

	include_once SPEEDYCACHE_PRO_DIR . '/main/admin.php';
}
	
// Nag when plugins dont have same version.
function speedycachepro_free_version_nag(){
	if(!defined('SPEEDYCACHE_VERSION')){
		return;
	}

	if(version_compare(SPEEDYCACHE_VERSION, SPEEDYCACHE_PRO_VERSION) > 0){
		echo '<div class="notice notice-error">
		<p style="font-size:16px;">You are using an Older version of SpeedyCache Pro. We suggest you upgrade SpeedyCache Pro to be able to use SpeedyCache without any issue.</p>
	</div>';
	}elseif(version_compare(SPEEDYCACHE_VERSION, SPEEDYCACHE_PRO_VERSION) < 0){
		echo '<div class="notice notice-error">
		<p style="font-size:16px;">You are using an Older version of the Free version of SpeedyCache. We suggest you update the free version of SpeedyCache to be able to use SpeedyCache without any issue.</p>
	</div>';
	}
}

function speedycache_pro_activate(){
	global $speedycache;
	
	if(empty($speedycache)){
		$speedycache = new \SpeedyCache();
	}

	$speedycache->options = get_option('speedycache_options', []);
	$speedycache->options['minify_html'] = true;
	$speedycache->options['minify_js'] = true;
	$speedycache->options['render_blocking'] = true;

	update_option('speedycache_options', $speedycache->options);
	update_option('speedycache_pro_version', SPEEDYCACHE_PRO_VERSION);
}
//register_deactivation_hook( __FILE__, '\SpeedyCache\Install::deactivate');

// Looks if SpeedyCache just got updated
function speedycache_pro_update_check(){

	$sql = array();
	$current_version = get_option('speedycache_pro_version');
	$version = (int) str_replace('.', '', $current_version);

	// No update required
	if($current_version == SPEEDYCACHE_PRO_VERSION){
		return true;
	}

	// If the user was using SpeedyCache Pro before seperation
	// then we need to clear the cache as we have updated the location of assets of the Pro version.
	if(empty($current_version)){
		$free_version = get_option('speedycache_version');
		
		if(!empty($free_version)){
			$desk_cache = glob(WP_CONTENT_DIR . '/cache/speedycache/'. sanitize_text_field($_SERVER['HTTP_HOST']) .'/all/*', GLOB_ONLYDIR);
			$mobile_cache = glob(WP_CONTENT_DIR . '/cache/speedycache/'. sanitize_text_field($_SERVER['HTTP_HOST']) .'/mobile-cache/*', GLOB_ONLYDIR);
			$deletable_dir = [];
			
			if(!empty($desk_cache)){
				$deletable_dir = $desk_cache;
			}

			if(!empty($mobile_cache)){
				$deletable_dir = array_merge($mobile_cache, $deletable_dir);
			}

			if(!empty($deletable_dir)){
				global $wp_filesystem;
				
				include_once(ABSPATH . 'wp-admin/includes/file.php');
				WP_Filesystem();
				
				foreach($deletable_dir as $dir){
					if(method_exists($wp_filesystem, 'delete')){
						$wp_filesystem->delete($dir, true);
					}
				}
			}
		}

		speedycache_pro_activate();
		return;
	}

	// Save the new Version
	update_option('speedycache_pro_version', SPEEDYCACHE_PRO_VERSION);

}

// Load WP CLI command(s) on demand.
if(defined('WP_CLI') && !empty(WP_CLI) && defined('SPEEDYCACHE_PRO')){
	include_once SPEEDYCACHE_PRO_DIR.'/main/cli.php';
}
<?php
/*
* SPEEDYCACHE
* https://speedycache.com/
* (c) SpeedyCache Team
*/

if(!defined('ABSPATH')){
	die('Hacking Attempt');
}

// Loads Instant Page to improve load page speed by 1%
if(defined('SPEEDYCACHE_PRO') && !empty($speedycache->options['instant_page'])){
	add_action('wp_enqueue_scripts', 'speedycache_enqueue_instant_page');
}

$speedycache->options['test_mode'] = get_transient('speedycache_test_mode', false);

// Delete the Cache if using test mode with ?delete_test as the parameter.
if(!empty($speedycache->options['test_mode']) && !empty($_GET['delete_test'])){
	\SpeedyCache\Delete::rm_dir(speedycache_cache_path('test'));
}

// For test mode
if(!empty($_GET['test_speedycache'])){	
	$speedycache_test_options = get_transient('speedycache_test_settings', $speedycache->options);

	if(!empty($speedycache_test_options) && is_array($speedycache_test_options)){
		$speedycache->options = array_merge($speedycache->options, $speedycache_test_options);
	}

	if(!empty($speedycache->options['test_mode'])){
		$speedycache->options['status'] = true;
	}
}

$cache_dir = speedycache_cache_path();

if(defined('SPEEDYCACHE_PRO') && is_dir($cache_dir . 'fonts')){
	$dir_cont = scandir($cache_dir . 'fonts');
	
	if(!empty($dir_cont)){
		add_filter('speedycache_content_via_php', '\SpeedyCache\GoogleFonts::replace', 10, 1);
	}
}

// Filter for Gravatar cache. We are updating the URL of the gravatar here so the local hosted Gravatar URL will be cached.
if(!empty($speedycache->options['gravatar_cache'])){
	add_filter('get_avatar_data', '\SpeedyCache\Gravatar::get_avatar_data', 10, 2);
}

// CSS / JS
if(preg_match('/\/([^\/]+)\/([^\/]+(\.css|\.js))(\?.+)?$/', speedycache_current_url(), $path)){

	// Security : Prevent Directory Traversal !
	if(preg_match("/\.{2,}/", speedycache_current_url())){
		die('May be Directory Traversal Attack');
	}

	// Outputs the cached minified data
	if(file_exists(speedycache_cache_path('assets/').$path[1])){
		
		if($sources = @scandir(speedycache_cache_path('assets/').$path[1], 1)){
			if(isset($sources[0])){
				if(preg_match('/\.css/', speedycache_current_url())){
					header('Content-type: text/css');
				}else if(preg_match('/\.js/', speedycache_current_url())){
					header('Content-type: text/js');
				}

				die(file_get_contents(speedycache_cache_path('assets/').$path[1].'/'.$sources[0]));
			}
		}
	}

	//for non-exists files
	if(preg_match('/\.css/', speedycache_current_url())){
		header('Content-type: text/css');
		die('/* File not found */');
	}else if(preg_match('/\.js/', speedycache_current_url())){
		header('Content-type: text/js');
		die('/*File not found*/');
	}

// Regular HTML
}else{
	
	// To show if the user is logged-in
	add_action('wp_loaded', 'speedycache_load_admin_toolbar');

	// For cache
	speedycache_cache();
	
}

if(!empty($speedycache->options['status']) && !empty($speedycache->options['lazy_load'])){

	\SpeedyCache\LazyLoad::init();
	
	// Instagram
	add_filter('script_loader_tag', '\SpeedyCache\LazyLoad::instagram', 10, 1);

	if(!empty($speedycache->options['lazy_load_exclude_full_size_img'])){
		add_filter('wp_get_attachment_image_attributes', '\SpeedyCache\LazyLoad::mark_attachment_page_images', 10, 2);
		add_filter('the_content', '\SpeedyCache\LazyLoad::mark_images', 99);
	}
}

if(!empty($speedycache->options['mobile_theme']) && !empty($speedycache->options['mobile_theme_name'])){
	add_action('plugins_loaded', 'speedycache_mts_init', 1);
}

// To delete/ preload cache through URL
if(isset($_GET['action'])  && $_GET['action'] == 'speedycache'){
	if(isset($_GET['type'])  && $_GET['type'] == 'preload'){
		// /?action=speedycache&type=preload
		add_action('init',  '\SpeedyCache\Precache::create', 11);
	}

	if(isset($_GET['type']) && preg_match('/^clearcache(andminified|allsites)*$/i', $_GET['type'])){
		// /?action=speedycache&type=clearcache&token=123
		// /?action=speedycache&type=clearcacheandminified&token=123

		if(empty($_GET['token'])){
			die('Security token must be set.');
		}
		
		if(!defined('SPEEDYCACHE_CLEAR_CACHE_URL_TOKEN') && empty(SPEEDYCACHE_CLEAR_CACHE_URL_TOKEN)){
			die('SPEEDYCACHE_CLEAR_CACHE_URL_TOKEN must be defined');
		}
		
		if(SPEEDYCACHE_CLEAR_CACHE_URL_TOKEN != $_GET['token']){
			die('Wrong token');
		}
		
		if($_GET['type'] == 'clearcache'){
			speedycache_delete_cache();
		}

		if($_GET['type'] == 'clearcacheandminified'){
			speedycache_delete_cache(true);
		}

		if($_GET['type'] == 'clearcacheallsites'){
			if(!function_exists('speedycache_clear_cache_of_allsites_callback')){
				include_once SPEEDYCACHE_DIR . '/main/ajax.php';
			}
			
			speedycache_clear_cache_of_allsites_callback();
		}

		die('Done');
	}
	
	die();
}

function speedycache_mts_init(){
	global $speedycache;

	if(!empty(speedycache_is_mobile())){
		$themes = wp_get_themes();
		$speedycache->options['mobile_theme_obj'] = $themes[$speedycache->options['mobile_theme_name']];

		add_filter('stylesheet', 'speedycache_load_mobile_style');
		add_filter('template', 'speedycache_load_mobile_theme');
	}
}

function speedycache_load_mobile_style(){
	global $speedycache;
	
	return $speedycache['speedycache_mobile_theme_obj']->get_template();
}

function speedycache_load_mobile_theme(){
	global $speedycache;
	
	return $speedycache['speedycache_mobile_theme_obj']->get_stylesheet();
}

function speedycache_cache(){
	
	if(!empty($_SERVER['HTTP_HOST'])){
		\SpeedyCache\Cache::init();
		\SpeedyCache\Cache::create();		
	}
}

function speedycache_current_url(){
	$url = home_url();
	$url = parse_url($url);

	$url = $url['host'] . speedycache_sanitize_url($_SERVER['REQUEST_URI']);
	return esc_url($url);
}

// Loads InstantPage which helps load pages faster
function speedycache_enqueue_instant_page(){
	wp_enqueue_script('speedycache_instant_page', SPEEDYCACHE_PRO_URL . '/assets/js/instantpage.js', array(), SPEEDYCACHE_PRO_VERSION, true);
}
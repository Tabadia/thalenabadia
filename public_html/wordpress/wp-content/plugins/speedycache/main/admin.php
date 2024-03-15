<?php
/*
* SPEEDYCACHE
* https://speedycache.com/
* (c) SpeedyCache Team
*/

if(!defined('ABSPATH')){
	die('Hacking Attempt');
}

// Admin Side Actions
add_action('user_register','\SpeedyCache\htaccess::new_user', 10, 1);
add_action('profile_update', '\SpeedyCache\htaccess::new_user', 10, 1);
add_action('edit_terms', '\SpeedyCache\Delete::cache_of_term', 10, 1);

if(defined('SPEEDYCACHE_PRO_DIR') && file_exists(SPEEDYCACHE_PRO_DIR . '/main/image.php')){
	add_action('add_attachment', '\SpeedyCache\Image::convert_on_upload');
	add_action('delete_attachment', '\SpeedyCache\Image::revert_on_delete');
}


// To add links to Plugin on Plugins list page
if($GLOBALS['pagenow'] == 'plugins.php'){
	add_filter('plugin_action_links_speedycache-pro/speedycache-pro.php', 'speedycache_action_links');
	add_filter('plugin_action_links_speedycache/speedycache.php', 'speedycache_action_links');
}

if(!wp_doing_ajax()){

	// Delete Column
	if(!empty($speedycache->options['post_types'])){
		foreach($speedycache->options['post_types'] as $k => $v){
			add_filter($v.'_row_actions', 'speedycache_column_delete_cache_link', 10, 2);
		}
	}

	add_action('plugins_loaded', 'speedycache_load_plugin_textdomain');
	add_action('wp_loaded', 'speedycache_load_admin_toolbar');
	add_action('admin_menu', 'speedycache_menu_page');
	
}

// To add metabox
if(!empty($speedycache->options['status'])){

	// The Classic Editor and Woocommerce editor send a post request with post_ID in place of GET request with post
	if(!empty($_REQUEST['post']) || (!empty($_REQUEST['post_ID']) && !empty($_REQUEST['hidden_post_status']) && $_REQUEST['hidden_post_status'] === 'publish')){
		add_action('add_meta_boxes', '\SpeedyCache\Metabox::add', 10, 2);
		add_action('save_post', '\SpeedyCache\Metabox::save_settings', 10, 2);
		add_action('save_post', 'speedycache_check_auto_cache', 10, 2);
	}

}

function speedycache_check_auto_cache($post_id, $post){
	global $pagenow, $speedycache;
	
	if(empty($_REQUEST['speedycache_metabox_save_nonce'])){
		return;
	}

	if((!empty($_REQUEST['action']) && $_REQUEST['action'] == 'trash') || $pagenow != 'post.php' || !$post || !is_object($post)){
		return;
	}
	
	if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE){
		return;
	}
	
	if(empty($speedycache->options['status']) || empty($speedycache->options['automatic_cache'])){
		return;
	}
	
	if(!wp_verify_nonce($_REQUEST['speedycache_metabox_save_nonce'], 'speedycache_metabox_save')){
		die('Security Check Failed');
	}

	$post_meta = get_post_meta($post_id, 'speedycache_post_meta', true);
	if(!empty($post_meta['disable_cache'])){
		return;
	}
	
	$post_status = get_post_status($post_id);

	if($post_status == 'publish'){
		\SpeedyCache\AutoCache::init($post_id);
	}

}

function speedycache_column_delete_cache_link($actions, $post){
	
	global $speedycache;
	
	if(empty($speedycache->columnjs)){
		wp_enqueue_script('speedycache-column', SPEEDYCACHE_URL . '/assets/js/column.js', array(), time(), true);
		$speedycache->columnjs = 1;
	}
	
	$actions['clear_cache_link'] = '<a data-id="' . $post->ID . '" data-nonce="' . wp_create_nonce('speedycache_nonce') . '" id="speedycache-clear-cache-link-' . $post->ID . '" style="cursor:pointer;">' . __('Delete Cache') . '</a>';

	return $actions;
}

function speedycache_action_links($actions){
	$actions['powered_settings'] = sprintf(__( '<a href="%s">Settings</a>', 'speedycache'), esc_url( admin_url( 'admin.php?page=speedycache')));
	
	if(!defined('SPEEDYCACHE_PRO')){
		$actions[] =  '<a href="https://speedycache.com/pricing" style="color:#3db634;" target="_blank">'._x('Go Pro', 'Upgrade to SpeedyCache Pro for many more features', 'speedycache').'</a>';
	}
	
	return array_reverse($actions);
}

function speedycache_load_plugin_textdomain(){
	load_plugin_textdomain('speedycache', FALSE, SPEEDYCACHE_DIR . '/languages/');
}

function speedycache_menu_page(){
	global $speedycache;
	
	$capability = 'activate_plugins';

	$speedycache->settings['disabled_tabs'] = apply_filters('speedycache_disabled_tabs', []);
	
	$hooknames[] = add_menu_page('SpeedyCache Settings', 'SpeedyCache', $capability, 'speedycache', 'speedycache_settings_page_include', SPEEDYCACHE_URL.'/assets/images/icon.svg');
	
	$hooknames[] = add_submenu_page('speedycache', 'SpeedyCache Settings', 'Settings', $capability, 'speedycache', 'speedycache_settings_page_include');
	
	$hooknames[] = add_submenu_page('speedycache', 'SpeedyCache Manage Cache', 'Manage Cache', $capability, 'speedycache-manage-cache', 'speedycache_settings_page_include');
	
	if(defined('SPEEDYCACHE_PRO') && !in_array('speedycache-image-optimisation' , $speedycache->settings['disabled_tabs'])){
		$hooknames[] = add_submenu_page('speedycache', 'SpeedyCache Image Optimization', 'Image Optimization', $capability, 'speedycache-image-optimisation', 'speedycache_settings_page_include');
	}
	
	if(!in_array('speedycache-cdn' , $speedycache->settings['disabled_tabs'])){
		$hooknames[] = add_submenu_page('speedycache', 'SpeedyCache CDN', 'CDN', $capability, 'speedycache-cdn', 'speedycache_settings_page_include');
	}
	
	$hooknames[] = add_submenu_page('speedycache', 'SpeedyCache Excludes', 'Excludes', $capability, 'speedycache-exclude', 'speedycache_settings_page_include');
	
	if(!in_array('speedycache-object' , $speedycache->settings['disabled_tabs'])){
		$hooknames[] = add_submenu_page('speedycache', 'Object Cache', 'Object', $capability, 'speedycache-object', 'speedycache_settings_page_include');
	}
	
	if(!defined('SITEPAD') && !in_array('speedycache-db' , $speedycache->settings['disabled_tabs'])){
		$hooknames[] = add_submenu_page('speedycache', 'SpeedyCache Database Optimization', 'Database', $capability, 'speedycache-db', 'speedycache_settings_page_include');
	}
	
	if(!defined('SITEPAD')){
		$hooknames[] = add_submenu_page('speedycache', 'SpeedyCache Bloat Remover', 'Bloat Remover', $capability, 'speedycache-bloat', 'speedycache_settings_page_include');
	}
	
	$hooknames[] = add_submenu_page('speedycache', 'Test PageSpeed', 'Test PageSpeed', $capability, 'speedycache-test', 'speedycache_settings_test_speed');
	
	if(!in_array('speedycache-support' , $speedycache->settings['disabled_tabs'])){
		$hooknames[] = add_submenu_page('speedycache', 'SpeedyCache Support', 'Support', $capability, 'speedycache-support', 'speedycache_settings_page_include');
	}

	if(!defined('SITEPAD') && defined('SPEEDYCACHE_PRO') && !in_array('speedycache-license' , $speedycache->settings['disabled_tabs'])){
		$license_hook_name = add_submenu_page('speedycache', 'SpeedyCache License', 'License', $capability, 'speedycache-license', 'speedycache_license_page_callback');
		add_action('load-'.$license_hook_name, 'speedycache_admin_load');
	}

	foreach($hooknames as $hookname){
		add_action('load-'.$hookname, 'speedycache_admin_load');
	}
	
}

function speedycache_admin_load(){
	add_action('admin_enqueue_scripts', 'speedycache_enqueue_admin_scripts');
}

// Enqueues Admin CSS on load of the page
function speedycache_enqueue_admin_scripts(){	
	wp_enqueue_style('speedycache-admin', SPEEDYCACHE_URL.'/assets/css/speedycache-admin.css', array(), SPEEDYCACHE_VERSION);
	wp_enqueue_style('speedycache-fontawesome', SPEEDYCACHE_URL . '/assets/css/fontawesomeicons.css', array(), SPEEDYCACHE_VERSION, 'all');
}

add_action('in_admin_header', 'speedycache_page_header', 1);

function speedycache_settings_page_include(){
	
	include_once SPEEDYCACHE_DIR.'/main/settings.php';
	
	speedycache_add_javascript();
	speedycache_settings_page();
	
	add_filter('admin_footer_text', 'speedycache_admin_footer_text', 1);
}

function speedycache_settings_test_speed(){
	include_once SPEEDYCACHE_DIR.'/main/settings.php';
	
	speedycache_add_javascript();
	speedycache_test_page();
	
	add_filter('admin_footer_text', 'speedycache_admin_footer_text', 1);
}

function speedycache_license_page_callback(){
	include_once SPEEDYCACHE_PRO_DIR . '/main/license.php';
	speedycache_license_page();
}

function speedycache_admin_footer_text($footer_text){
	
	global $speedycache;

	$stars = '<span class="wporg-ratings rating-stars"><span class="dashicons dashicons-star-filled" style="color:#ffb900 !important;"></span><span class="dashicons dashicons-star-filled" style="color:#ffb900 !important;"></span><span class="dashicons dashicons-star-filled" style="color:#ffb900 !important;"></span><span class="dashicons dashicons-star-filled" style="color:#ffb900 !important;"></span><span class="dashicons dashicons-star-filled" style="color:#ffb900 !important;"></span></span>';
	
	$rate_us = '';
	
	if(empty($speedycache->settings['brand_data'])){
		$rate_us = '&nbsp;|&nbsp;' . '<a href="https://wordpress.org/support/plugin/speedycache/reviews/?filter=5#new-post" rel="noopener noreferer" target="_blank">'. sprintf( __( 'Rate %s on %s', 'speedycache' ), '<strong>' . __( 'SpeedyCache', 'speedycache' ) . $stars . '</strong>', 'WordPress.org' ) . '</a>' ;
	}
	
	$version = 'SpeedyCache Version ' . SPEEDYCACHE_VERSION;
	
	return $version . $rate_us;
}
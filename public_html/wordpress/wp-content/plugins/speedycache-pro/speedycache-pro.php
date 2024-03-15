<?php
/*
Plugin Name: SpeedyCache Pro
Plugin URI: https://speedycache.com
Description: SpeedyCache is a plugin that helps you reduce the load time of your website by means of caching, minification, and compression of your website.
Version: 1.1.4
Author: Softaculous Team
Author URI: https://speedycache.com/
Text Domain: speedycache
*/

// We need the ABSPATH
if(!defined('ABSPATH')) exit;

if(!function_exists('add_action')){
	echo 'You are not allowed to access this page directly.';
	exit;
}

// If SPEEDYCACHE_PRO_VERSION exists then the plugin is loaded already !
if(defined('SPEEDYCACHE_PRO_VERSION')){
	return;
}

define('SPEEDYCACHE_PRO', plugin_basename(__FILE__));
define('SPEEDYCACHE_PRO_FILE', __FILE__);

$_tmp_plugins = get_option('active_plugins');

if(!in_array('speedycache/speedycache.php', $_tmp_plugins)){
	add_action('plugins_loaded', 'speedycache_pro_load_plugin');

	function speedycache_pro_load_plugin(){

		// Nag informing the user to install the free version.
		if(current_user_can('activate_plugins')){
			add_action('admin_notices', 'speedycachepro_free_version_nag');
			add_action('admin_menu', 'speedycachepro_add_menu');

			if(!empty(get_option('speedycache_free_installed'))){
				return;
			}
			
			update_option('speedycache_free_installed', time());

			// Include the necessary stuff
			include_once(ABSPATH . 'wp-admin/includes/plugin-install.php');
			include_once(ABSPATH . 'wp-admin/includes/plugin.php');
			include_once(ABSPATH . 'wp-admin/includes/file.php');
			// Includes necessary for Plugin_Upgrader and Plugin_Installer_Skin
			include_once(ABSPATH . 'wp-admin/includes/misc.php');
			include_once(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');

			// Filter to prevent the activate text
			add_filter('install_plugin_complete_actions', 'speedycache_pro_prevent_activation_text', 10, 3);

			$upgrader = new Plugin_Upgrader(new WP_Ajax_Upgrader_Skin());
			$installed = $upgrader->install('https://downloads.wordpress.org/plugin/speedycache.zip');

			if(!is_wp_error($installed) && $installed){
				$activate = activate_plugin('speedycache/speedycache.php');

				//wp_safe_redirect(admin_url('/'));
			}
		}
	}
	
	// Do not shows the activation text if 
	function speedycache_pro_prevent_activation_text($install_actions, $api, $plugin_file){
		if($plugin_file == 'speedycache/speedycache.php'){
			return array();
		}

		return $install_actions;
	}

	function speedycachepro_free_version_nag(){
		echo '<div class="notice notice-error">
			<p style="font-size:16px;">You have not installed the free version of SpeedyCache. SpeedyCache Pro depends on the free version, so you must install it first in order to use SpeedyCache. <a href="'.admin_url('plugin-install.php?s=speedycache&tab=search').'" class="button button-primary">Install Now</a></p>
		</div>';
	}

	function speedycachepro_add_menu(){
		add_menu_page('SpeedyCache Settings', 'SpeedyCache', 'activate_plugins', 'speedycache', 'speedycachepro_menu_page');
	}

	function speedycachepro_menu_page(){
		echo '<div style="color: #333;padding: 50px;text-align: center;">
			<h1 style="font-size: 2em;margin-bottom: 10px;">SpeedyCache Free version is not installed!</h>
			<p style=" font-size: 16px;margin-bottom: 20px; font-weight:400;">SpeedyCache Pro depends on the free version of SpeedyCache, so you need to install the free version first.</p>
			<a href="'.admin_url('plugin-install.php?s=speedycache&tab=search').'" style="text-decoration: none;font-size:16px;">Install Now</a>
		</div>';
	}
	
	return;
}

include_once(__DIR__ . '/init.php');
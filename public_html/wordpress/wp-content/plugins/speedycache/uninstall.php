<?php
/*
* SPEEDYCACHE
* https://speedycache.com/
* (c) SpeedyCache Team
*/

// If uninstall.php is not called by WordPress, die
if(!defined('WP_UNINSTALL_PLUGIN')){
	die;
}

$plugin_base = basename(dirname(__FILE__));

include_once(plugin_dir_path(__FILE__).'/'. $plugin_base .'.php');

\SpeedyCache\Install::deactivate();
speedycache_delete_binaries();

$deleteables = array('speedycache_version', 'speedycache_autocache', 'speedycache_options', 'speedycache_delete_cache_logs', 'speedycache_cdn', 'speedycache_exclude', 'speedycache_preload', 'speedycache_css', 'speedycache_css_size', 'speedycache_js', 'speedycache_js_size', 'speedycache_toolbar_settings', 'speedycache-group', 'speedycache_img', 'speedycache_license', 'speedycache_ccss_logs', 'speedycache_object_cache');

foreach($deleteables as $d){
	delete_option($d);
}

foreach( (array)_get_cron_array() as $cron_key => $cron_value){
	
	foreach( (array)$cron_value as $hook => $events){
		
		if(!preg_match('/^speedycache/is', $hook)){
			continue;
		}
		
		$r = array();
		
		foreach( (array) $events as $event_key => $event ){
			if(isset($event['args']) && isset($event['args'][0])){
				$r = array($event['args'][0]);
			}
		}

		wp_clear_scheduled_hook($hook, $r);

	}
	
}


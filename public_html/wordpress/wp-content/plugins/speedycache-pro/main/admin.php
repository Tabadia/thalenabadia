<?php

if(!defined('ABSPATH')){
	die('HACKING ATTEMPT!');
}

// Show menu with error if speedy cache is installed but is older than 1.1.0
// as after that we dont short circuit the free version
if(!defined('SPEEDYCACHE_VERSION') || version_compare(SPEEDYCACHE_VERSION, '1.1.1') < 0){
	add_action('admin_menu', 'speedycachepro_add_menu');
	return; // Return else going forward will break things.
}

add_action('admin_notices', 'speedycachepro_free_version_nag');
speedycache_load_license();
include_once SPEEDYCACHE_PRO_DIR . '/main/premium.php';

if(defined('SPEEDYCACHE_PRO') && file_exists(SPEEDYCACHE_PRO_DIR . '/main/image.php')){
	\SpeedyCache\Image::init();
	add_action('wp_ajax_speedycache_download_cwebp', '\SpeedyCache\Image::download_cwebp');
}

// Load license data
function speedycache_load_license(){
	global $speedycache;
	
	// Load license
	$speedycache->license = get_option('speedycache_license');
	
	if(empty($speedycache->license['last_update'])){
		$speedycache->license['last_update'] = time() - 86600;
	}

	// Update license details as well
	if((time() - @$speedycache->license['last_update']) >= 86400){
		
		$license = '';
		if(!empty($speedycache->license) && !empty($speedycache->license['license'])){
			$license = strpos($speedycache->license['license'], 'SPDFY') !== 0 ? '' : $speedycache->license['license'];
		}

		$resp = wp_remote_get(SPEEDYCACHE_API.'license.php?license='.$license);

		//Did we get a response ?
		if(!is_array($resp)){
			return;
		}

		$tosave = json_decode($resp['body'], true);
		
		//Is it the license ?
		if(!empty($tosave['license'])){
			$tosave['last_update'] = time();
			update_option('speedycache_license', $tosave);
		}
	}
}

function speedycachepro_add_menu(){
	add_menu_page('SpeedyCache Settings', 'SpeedyCache', 'activate_plugins', 'speedycache', 'speedycachepro_menu_page');
}

function speedycachepro_menu_page(){
	echo '<div style="color: #333;padding: 50px;text-align: center;">
		<h1 style="font-size: 2em;margin-bottom: 10px;">Update Speedycache to Latest Version!</h>
		<p style=" font-size: 16px;margin-bottom: 20px; font-weight:400;">SpeedyCache Pro depends on the free version of SpeedyCache, so you need to update the free version to use SpeedyCache without any issue.</p>
		<a href="'.admin_url('plugin-install.php?s=speedycache&tab=search').'" style="text-decoration: none;font-size:16px;">Install Now</a>
	</div>';
}
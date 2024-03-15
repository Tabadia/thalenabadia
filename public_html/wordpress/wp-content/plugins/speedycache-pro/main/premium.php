<?php

if(!defined('ABSPATH')){
	die('HACKING ATTEMPT');
}

// Check for updates
include_once(SPEEDYCACHE_PRO_DIR . '/main/plugin-update-checker.php');
$speedycache_updater = SpeedyCache_PucFactory::buildUpdateChecker(SPEEDYCACHE_API.'/updates.php?version='.SPEEDYCACHE_PRO_VERSION, SPEEDYCACHE_PRO_FILE);
	
// Add the license key to query arguments
$speedycache_updater->addQueryArgFilter('speedycache_updater_filter_args');
	
// Show the text to install the license key
add_filter('puc_manual_final_check_link-speedycache-pro', 'speedycache_updater_check_link', 10, 1);

global $pagenow;
if(in_array($pagenow, ['post-new.php', 'post.php'], true)){
	add_action('admin_enqueue_scripts', '\SpeedyCache\MetaboxPro::enqueue_scripts');
	add_filter('speedycache_pro_metabox', '\SpeedyCache\MetaboxPro::html', 10, 2);
	add_filter('speedycache_metabox_fields', '\SpeedyCache\MetaboxPro::options');
}

// Add our license key if ANY
function speedycache_updater_filter_args($queryArgs){

	$license = get_option('speedycache_license');
	
	if (!empty($license['license'])){
		$queryArgs['license'] = $license['license'];
	}
	
	return $queryArgs;
}

// Handle the Check for update link and ask to install license key
function speedycache_updater_check_link($final_link){
	
	$license = get_option('speedycache_license');
	
	if(empty($license['license'])){
		return '<a href="'.admin_url('admin.php?page=speedycache-license').'">Install SpeedyCache Pro License Key</a>';
	}
	
	return $final_link;
}


<?php

if(!defined('ABSPATH')){
	die('Hacking Attempt!');
}


// Cron for Calling Auto Backup
add_action('backuply_auto_backup_cron', 'backuply_auto_backup_execute');

// Adds a Wp-Cron for autobackup
function backuply_add_auto_backup_schedule($schedule = '') {
	global $backuply;

	if(empty($schedule)){
		$schedule = backuply_optpost('backuply_cron_schedule');
	}
	
	if (!wp_next_scheduled( 'backuply_auto_backup_cron' ) && !empty($backuply['bcloud_key'])){
		wp_schedule_event(time(), $schedule, 'backuply_auto_backup_cron');
	}
}

// Initiates auto backup
function backuply_auto_backup_execute(){
	global $backuply;
	
	if(empty($backuply['bcloud_key'])){
		return false;
	}

	$access_key = backuply_bcloud_isallowed();

	if(empty($access_key)){
		return;
	}

	//$backuply['auto_backup'] = true;
	backuply_create_log_file();
	backuply_backup_rotation();

	if($auto_backup_settings = get_option('backuply_cron_settings')){
		$auto_backup_settings['auto_backup'] = true;
		update_option('backuply_status', $auto_backup_settings);
		backuply_backup_execute();
	}
}

// Rotate the backups
function backuply_backup_rotation() {
	global $backuply;
	
	if(empty($backuply['cron']['backup_rotation']) || empty($backuply['bcloud_key'])) {
		return;
	}

	$backup_info = backuply_get_backups_info();

	if(empty($backup_info)) {
		return;
	}

	$backup_info = array_filter($backup_info, 'backuply_bcloud_filter_backups_on_loc');
	usort($backup_info, 'backuply_bcloud_oldest_backup');
	
	if(count($backup_info) >= $backuply['cron']['backup_rotation']) {
		if(empty($backup_info[0])) {
			return;
		}
		
		backuply_log('Deleting Files because of Backup rotation');
		backuply_status_log('Deleting backup because of Backup rotation', 39);
		
		$extra_backups = count($backup_info) - $backuply['cron']['backup_rotation'];
		
		if($extra_backups > 0) {
			for($i = 0; $i <= $extra_backups; $i++) {
				backuply_delete_backup($backup_info[$i]->name .'.'. $backup_info[$i]->ext);
			}
		}
	}
}

function backuply_bcloud_oldest_backup($a, $b) {
	return $a->btime > $b->btime;
}

// Returns backups based on location
function backuply_bcloud_filter_backups_on_loc($backup) {
	global $backuply;
	
	if(!isset($backup->backup_location)){
		return ($backup->auto_backup);
	}
	
	return ($backuply['cron']['backup_location'] == $backup->backup_location && $backup->auto_backup);
}

function backuply_bcloud_isallowed(){
	global $backuply;
	
	
	if(!empty(get_transient('bcloud_data'))){
		return true;
	}

	$url = BACKUPLY_API . '/cloud/token.php';
	
	// Check if License is present and active.
	if(empty($backuply['license']['license'] || empty($backuply['license']['active']))){
		return false;
	}
	
	// Check if Bcloud key is there.
	if(empty($backuply['bcloud_key'])){
		return false;
	}

	$args = array(
		'sslverfiy' => false,
		'body' => array(
			'license' => $backuply['license']['license'],
			'bcloud_key' => $backuply['bcloud_key'],
			'url' => site_url()
		),
		'timeout' => 30
	);

	$res = wp_remote_post($url, $args);
	
	if(empty($res) && is_wp_error($res)){
		return false;
	}
	
	if(empty($res['body'])){
		return false;
	}
	
	$body = json_decode($res['body'], true);
	
	if(empty($body['success'])){
		return false;
	}
	
	return true;
}
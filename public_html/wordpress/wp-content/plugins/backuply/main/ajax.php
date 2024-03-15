<?php
/*
* BACKUPLY
* https://backuply.com
* (c) Backuply Team
*/

// Are we being accessed directly ?
if(!defined('BACKUPLY_VERSION')) {
	exit('Hacking Attempt !');
}

set_time_limit(60);
ignore_user_abort(true);

// Is the nonce there ?
if(empty($_REQUEST['security'])){
	return;
}

// AJAX Actions
add_action('wp_ajax_backuply_create_backup', 'backuply_create_backup');
add_action('wp_ajax_backuply_stop_backup', 'backuply_stop_backup');
add_action('wp_ajax_backuply_download_backup', 'backuply_download_backup');
add_action('wp_ajax_backuply_multi_backup_delete', 'backuply_multi_backup_delete');
add_action('wp_ajax_backuply_check_status', 'backuply_check_status');
add_action('wp_ajax_backuply_check_backup_status', 'backuply_backup_progress_status');
add_action('wp_ajax_backuply_checkrestorestatus_action', 'backuply_checkrestorestatus_action');
add_action('wp_ajax_backuply_restore_curl_query', 'backuply_restore_curl_query');
add_action('wp_ajax_backuply_retry_htaccess', 'backuply_retry_htaccess');
add_action('wp_ajax_backuply_kill_proccess', 'backuply_force_stop');
add_action('wp_ajax_backuply_get_loc_details', 'backuply_get_loc_details');
add_action('wp_ajax_backuply_sync_backups', 'backuply_sync_backups');
add_action('wp_ajax_nopriv_backuply_restore_response', 'backuply_restore_response');
add_action('wp_ajax_nopriv_backuply_update_serialization', 'backuply_update_serialization_ajax');
add_action('wp_ajax_backuply_creating_session', 'backuply_creating_session');
add_action('wp_ajax_nopriv_backuply_creating_session', 'backuply_creating_session');
add_action('wp_ajax_backuply_last_logs', 'backuply_get_last_logs');
add_action('wp_ajax_backuply_save_excludes', 'backuply_save_excludes');
add_action('wp_ajax_backuply_exclude_rule_delete', 'backuply_exclude_rule_delete');
add_action('wp_ajax_backuply_get_jstree', 'backuply_get_jstree');
add_action('wp_ajax_backuply_hide_backup_nag', 'backuply_hide_backup_nag');
add_action('wp_ajax_backuply_get_restore_key', 'backuply_get_restore_key');
add_action('wp_ajax_backuply_handle_backup', 'backuply_handle_backup_request');
add_action('wp_ajax_backuply_download_bcloud', 'backuply_download_bcloud');
add_action('wp_ajax_backuply_update_quota', 'backuply_update_quota');

// Backuply CLoud
add_action('wp_ajax_bcloud_trial', 'backuply_bcloud_trial');
add_action('wp_ajax_backuply_verify_trial', 'backuply_verify_trial');
add_action('wp_ajax_backuply_trial_settings', 'backuply_trial_settings');

function backuply_ajax_nonce_verify($key = 'security'){
	
	if(!wp_verify_nonce($_REQUEST[$key], 'backuply_nonce')) {
		wp_send_json(array('success' => false, 'message' => 'Security Check Failed.'));
	}
	
	// Check capability
	if(!current_user_can( 'activate_plugins' )){
		wp_send_json(array('success' => false, 'message' => 'You cannot create backups as per your capabilities !'));
	}
	
	return true;
}

// AJAX handle to download backup
function backuply_download_backup() {
	
	backuply_ajax_nonce_verify();
	
	if(empty($_GET['backup_name'])) {
		wp_send_json(array('success' => false, 'message' => 'Wrong File name provided.'));
	}
	
	$filename = backuply_optget('backup_name');
	$filename = backuply_clean_file_name($filename);
	$backups_dir = backuply_glob('backups');
	
	$file = $backups_dir . '/'. $filename;
	
	if(!file_exists($file)) {
		wp_send_json(array('success' => false, 'message' => 'File not found.'));
	}
	
	ob_start();
	header('Content-Type: application/octet-stream');
	header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
	header('Content-Transfer-Encoding: binary');
	header('Content-Length: ' . filesize($file));
	header('Content-Range: 0-' . (filesize($file) - 1) . '/' . filesize($file));
	wp_ob_end_flush_all();

	readfile($file);
	wp_die();
}

// AJAX handle to delete Multiple backups
function backuply_multi_backup_delete() {
	global $error;
	
	backuply_ajax_nonce_verify();
	
	if(empty($_POST['backup_name'])) {
		wp_send_json(array('success' => false, 'message' => 'No Backup selected for deletion.'));
	}
	
	$backup = backuply_optpost('backup_name');
	
	if(empty($backup)) {
		wp_send_json(array('success' => false, 'message' => 'No File was provided to be deleted'));
	}
	
	if(!backuply_delete_backup($backup)) {
		$error_string = __('Below are the errors faced', 'backuply');
		
		foreach($error as $e) {
			$error_string .= $e . "\n";
		}
		
		wp_send_json(array('success' => false, 'message' => $error_string));
	}
	
	wp_send_json(array('success' => true));
}

// AJAX handle to create backup
function backuply_create_backup() {
	
	backuply_ajax_nonce_verify();
	
	$bak_options = json_decode(sanitize_text_field(wp_unslash($_POST['values'])), true);

	backuply_create_log_file();
	
	update_option('backuply_backup_stopped', false);
	update_option('backuply_status', $bak_options);
	backuply_status_log('Initializing...', 'info', 13);
	backuply_status_log('Creating a job to start Backup', 'info', 17);

	if(backuply_backup_request()) {
		wp_schedule_single_event(time() + BACKUPLY_TIMEOUT_TIME, 'backuply_timeout_check', array('is_restore' => false));
		wp_send_json(array('success' => true));
	}
	
	// Failed !
	wp_send_json(array('success' => false, 'message' => 'Unable to start a backup at this moment, Please try again later'));
}

function backuply_backup_request(){

	$url = admin_url('admin-ajax.php') . '?action=backuply_handle_backup&security=' . wp_create_nonce('backuply_create_backup');

	$res = wp_remote_get($url, array(
		'timeout' => 0.01,
		'blocking' => false,
		'cookies' => array(LOGGED_IN_COOKIE => $_COOKIE[LOGGED_IN_COOKIE]),
		'sslverify' => false
	));

	if(empty($res) || is_wp_error($res)){
		return false;
	}
	
	$res_code = wp_remote_retrieve_response_code($res);	

	if(!empty($res_code) && $res_code != 200){
		return false;
	}
	
	if(isset($res[0]) && $res[0] == false){
		return false;
	}

	return true;
	
}

function backuply_handle_backup_request(){

	if(!wp_verify_nonce($_REQUEST['security'], 'backuply_create_backup')) {
		backuply_status_log('Security Failed', 'error', 100);

		return false;
	}

	// Check capability
	if(!current_user_can( 'activate_plugins' )){
		backuply_status_log('You cannot create backups as per your capabilities !', 'error', 100);
		return false;
	}

	backuply_backup_execute();
	return true;
}

// Returns status for backup and restore
function backuply_backup_progress_status() {
	// Security Check
	backuply_ajax_nonce_verify();
	
	$last_status = !empty($_POST['last_status']) ? backuply_optpost('last_status') : 0;
	
	// negative progress means backup is being stopped
	wp_send_json(array(
		'success' => true,
		'is_stoppable' => true,
		'progress_log' => backuply_get_status($last_status)
		)
	);
}

function backuply_stop_backup() {
	
	backuply_ajax_nonce_verify();
	
	backuply_status_log('Stopping the Backup', 'info', -1);
	update_option('backuply_backup_stopped', true);
	wp_send_json(array('success' => true));
}

// Retries creating htaccess
function backuply_retry_htaccess() {
	
	backuply_ajax_nonce_verify();

	$res = backuply_add_htaccess();
	
	if($res) {
		wp_send_json(array('success' => true));
	}
	
	wp_send_json(array('success' => false, 'message' => 'We are not able to create the htaccess file, So please use the manual way.'));
}

// AJAX handle to restore backupp
function backuply_restore_curl_query(){

	backuply_ajax_nonce_verify();

	if(!empty($_POST['fname'])) {
		$info = map_deep($_POST, 'sanitize_text_field');

		backuply_init_restore($info);
		exit();
	}
}

// Kills process
function backuply_force_stop() {
	backuply_ajax_nonce_verify();
	
	delete_option('backuply_status');
	update_option('backuply_backup_stopped', true);
	
	if(file_exists(BACKUPLY_BACKUP_DIR . 'restoration/restoration.php')){
		@unlink(BACKUPLY_BACKUP_DIR . 'restoration/restoration.php');
	}
	
	wp_send_json(['success' => true]);
}

// Fetches details of a specific location
function backuply_get_loc_details() {
	
	//Security check
	backuply_ajax_nonce_verify();
	
	$loc_id = backuply_optpost('loc_id');
	
	if(empty($loc_id)){
		wp_send_json(array('success' => false, 'message' => __('No location id specified', 'backuply')));
	}
	
	// The filters are the fields which we dont want to show in edit as these are keys.
	$filter = ['ftp_pass', 'aws_secretKey', 'aws_accessKey'];
	$loc_info = backuply_get_loc_by_id($loc_id, $filter);
	
	if(empty($loc_info)) {
		wp_send_json(array('success' => false, 'message' => __('The specified location not found', 'backuply')));
	}
	
	wp_send_json(array('success' => true, 'data' => $loc_info));
}

// Syncs the backups in the remote location with us
function backuply_sync_backups(){
	//Security check
	backuply_ajax_nonce_verify();
	
	$loc_id = backuply_optget('id');
	
	if(empty($loc_id)){
		wp_send_json(array('success' => false, 'message' => __('No location id specified', 'backuply')));
	}
	
	$sync = backuply_sync_remote_backup_infos($loc_id);
	
	wp_send_json(array('success' => true));
}

// Deletes all remote info files on Restore Success
function backuply_delete_rinfo_on_restore() {
	$backup_info_f = backuply_glob('backups_info') . '/';
	$backup_info = backuply_get_backups_info();

	foreach($backup_info as $info){
		if(!isset($info->backup_location)){
			continue;
		}
		
		@unlink($backup_info_f . $info->name . '.php');
	}
}


// Handles WP related functionality after restore has happened Restore
function backuply_restore_response($is_last = false) {
	
	$keepalive = (int) time() + 25;

	if(!backuply_verify_self(backuply_optreq('security'))){
		backuply_status_log('Security Check Failed', 'error');
		die();
	}

	if(!$is_last && !empty($_REQUEST['restore_db']) && !empty($_REQUEST['is_migrating'])){
		$session_data = array('time' => time(), 'key' => backuply_optreq('sess_key'), 'user_id' => backuply_optreq('user_id'));
	
		update_option('backuply_restore_session_key', $session_data);
		
		backuply_status_log('Repairing database serialization', 'info', 78);
		$clones = ['options' => 'option', 'postmeta' => 'meta', 'commentmeta' => 'meta'];
		backuply_update_serialization($keepalive, $clones);
	}

	// Clears WP Cron for timeout
	if($timestamp = wp_next_scheduled('backuply_timeout_check', array('is_restore' => true))) {
		wp_unschedule_event($timestamp, 'backuply_timeout_check', array('is_restore' => true));
	}
	
	// Delete the config file.
	if(file_exists(BACKUPLY_BACKUP_DIR . 'backuply_config.php')){
		backuply_keys_to_db(); // After restore we need push the keys back to db.
	}
	
	$email = get_option('backuply_notify_email_address');
	$site_url = get_site_url();
	$dir_path = backuply_cleanpath(ABSPATH);
	$backuply_backup_dir = backuply_cleanpath(BACKUPLY_BACKUP_DIR);
	
	// Was there an error ?
	if(backuply_optget('error')){
	
		$error_string = html_entity_decode(backuply_optget('error_string'));
		// Send mail
		$mail = array();
		$mail['to'] = $email;   
		$mail['subject'] = 'Restore of your WordPress installation failed - Backuply';
		$mail['headers'] = "Content-Type: text/html; charset=UTF-8\r\n";
		$mail['message'] = 'Hi, <br><br>

The last restore operation of your WordPress installation was failed. <br>
Installation URL : '.$site_url.' <br>
'.$error_string.' <br><br>


Regards,<br>
Backuply';

		if(!empty($mail['to'])){
			wp_mail($mail['to'], $mail['subject'], $mail['message'], $mail['headers']);
		}
		backuply_report_error(backuply_optget('error_string'));
		
		backuply_status_log('Restore Failed!.','error');
		backuply_copy_log_file(true);
		backuply_clean_restoration_file();

		exit(1);
	}

	backuply_status_log('Sending an Email notification.','working', 84);
	
	// Send mail
	$mail = array();
	$mail['to'] = $email;   
	$mail['subject'] = 'Restore of your WordPress - Backuply';
	$mail['headers'] = "Content-Type: text/html; charset=UTF-8\r\n";
	$mail['message'] = 'Hi, <br><br>

The restore of your WordPress backup was completed successfully. <br>
The details are as follows : <br><br>

Installation Path : '.$dir_path.'<br>
Installation URL : '.$site_url.'<br>
Regards,<br>
Backuply';

	if(!empty($mail['to'])){
		wp_mail($mail['to'], $mail['subject'], $mail['message'], $mail['headers']);
	}

	backuply_status_log('Restore performed successfully.', 'success', 100);
	update_option('backuply_last_restore', time());
	backuply_delete_rinfo_on_restore();
	backuply_copy_log_file(true);
	backuply_clean_restoration_file();
	
	die();
}

// Loops(based on timeout) through the fixing serialized
function backuply_update_serialization($keepalive, $options = array(), $i = null) {
	
	if(!function_exists('backuply_wp_clone_sql')){
		include_once BACKUPLY_DIR . '/functions.php';
	}
	

	$keys = array_keys($options);
	$repair_log = get_option('backuply_sql_repair_log');
	
	if(empty($repair_log)) {
		$repair_log = array('table' => $keys[0], 'try' => 0);
		update_option('backuply_sql_repair_log', $repair_log);
	}

	$res = backuply_wp_clone_sql($keys[0], $options[$keys[0]], $keepalive, $i);

	if(!is_numeric($res)) {
		delete_option('backuply_sql_repair_log');
		unset($options[$keys[0]]);
	}
	
	if($res === false){
		backuply_status_log('Something went wrong while repairing database', 'error');
		die();
	}
	
	if(empty($options)){
		backuply_status_log('Successfully repaired the database', 'info', 81);
		backuply_restore_response(true);
		die();
	}
	
	$config = backuply_get_config();
	if(empty($config['BACKUPLY_KEY'])){
		backuply_status_log('Backuply key not found!', 'error');
		die();
	}
	
	$body = array('options' => $options);
	
	if(is_numeric($res)){
		
		if($keys[0] == $repair_log['table'] && $repair_log['try'] > 1){
			backuply_status_log('Repairing of the database serialization failed, Restoration has completed but some plugins may work weirdly', 'error', 100);
		}
		
		if(!empty($restore_log['i']) && $repair_log['i'] == $res && $keys[0] == $repair_log['table']){
			$repair_log['try'] += 1;
		}
		
		$repair_log['i'] = $res;
		
		update_option('backuply_sql_repair_log', $repair_log);
		
		$body['i'] = $res;
	}
	
	$url = admin_url('admin-ajax.php'). '?action=backuply_update_serialization&security='. $config['BACKUPLY_KEY'];
	
	wp_remote_post($url, array(
		'body' => $body,
		'timeout' => 2,
		'sslverify' => false
	));
	
	die();
}

// Ajax to handle the loop of fixing serialized
function backuply_update_serialization_ajax() {

	//Security Check
	if(!backuply_verify_self(backuply_optreq('security'))){
		backuply_status_log('Security Check Failed', 'error');
		die();
	}
	
	@touch(BACKUPLY_BACKUP_DIR.'/restoration/restoration.php'); // Updating restore time
	@touch(BACKUPLY_BACKUP_DIR.'/status.lock'); // Updating Status Lock mtime
	
	$keepalive = time() + 25;

	
	$i = !empty(backuply_optpost('i')) ? backuply_optpost('i') : null;
	
	backuply_update_serialization($keepalive, sanitize_post($_POST['options']) ,$i);
}

function backuply_get_restore_key(){
	
	backuply_ajax_nonce_verify();
	backuply_update_restore_key();

	$config_set = backuply_set_config_file(); // Add the config keys to the file.
	
	if(empty($config_set)){
		backuply_status_log('Unable to set Config file', 'error', 100);
		unlink($backuply_backup_dir.'/restoration/restoration.php');
		wp_send_json(array('success' => false, 'message' => 'Unable to create the config file!'));
	}

	@touch(BACKUPLY_BACKUP_DIR. '/status.lock');
	
	$config = backuply_get_config();
	
	if(empty($config) || empty($config['RESTORE_KEY'])){
		wp_send_json(array('success' => false, 'message' => 'Config is empty, please try again'));
	}
	
	wp_send_json(array('success' => true, 'restore_key' => $config['RESTORE_KEY'], 'backuply_key' => $config['BACKUPLY_KEY']));
}


// Creates a WP session if user is not logged in
function backuply_creating_session(){
	
	// Security Check
	if(!backuply_verify_self(backuply_optreq('security'))){
		backuply_status_log('Security Check Failed', 'error');
		die();
	}
	
	$key = get_option('backuply_restore_session_key');
	
	// Search for the user ID
	if(empty($key)){
		wp_send_json(array('success' => false, 'error' => true, 'message' => 'Session Key has not been updated yet!'));
	}
	
	if((time() - $key['time']) > 600 || (isset($_REQUEST['sess_key']) && $_REQUEST['sess_key'] != $key['key'])){
		//backuply_status_log('Session key verification failed', 'error');
		die();
	}
	
	if(is_user_logged_in()){
		wp_send_json(array('success' => false, 'message' => 'Already logged in'));
	}
	
	// Setting Session
	if(!empty($key['user_id'])){
		$user = get_user_by('id', $key['user_id']);
	}else{
		$user = get_userdata(1);
		
		// Try to find an admin if we do not have any admin with ID => 1
		if(empty($user) || empty($user->user_login)){
			$admin_id = get_users(array('role__in' => array('administrator'), 'number' => 1, 'fields' => array('ID')));
			$user = get_userdata($admin_id[0]->ID);
		}
		
		$username = $user->user_login;
		$user = get_user_by('login', $username);
	}
	
	if(isset($user) && is_object($user) && property_exists($user, 'ID')){
		clean_user_cache(get_current_user_id());
		clean_user_cache($user->ID);
		wp_clear_auth_cookie();
		wp_set_current_user($user->ID, $user->user_login);
		wp_set_auth_cookie($user->ID, 1, is_ssl());
		do_action('wp_login', $user->user_login, $user);
		update_user_caches($user);

		wp_send_json(array('success' => true));
	}
}

// Returns logs of last backup/restore
function backuply_get_last_logs(){
	
	backuply_ajax_nonce_verify();
	
	$is_restore = backuply_optget('is_restore');
	$backup_log_name = !empty($_GET['file_name']) ? backuply_optget('file_name') : 'backuply_backup_log.php';
	$location_id = !empty($_GET['proto_id']) ? backuply_optget('proto_id') : '';	
	$backup_log_name = backuply_clean_file_name($backup_log_name);
	
	$log_fname = !empty($is_restore) ? 'backuply_restore_log.php' : $backup_log_name;
	$log_file = BACKUPLY_BACKUP_DIR . $log_fname;
	$logs = array();
	
	if(!file_exists($log_file)){
		if(!backuply_sync_remote_backup_logs($location_id, $log_fname)){
			wp_send_json(array('success' => false, 'progress_log' => 'No log found!|writing\n'));
		}
	}
	
	$fh = fopen($log_file, 'r');

	@fseek($fh, 16);
	
	$lines = fread($fh, fstat($fh)['size']);
	fclose($fh);
	$fh = null;
	
	wp_send_json(array('success' => true, 'progress_log' => $lines));
}

function backuply_save_excludes() {
	
	global $backuply;
	
	backuply_ajax_nonce_verify();
	
	$type = backuply_optpost('type');
	$pattern = backuply_optpost('pattern'); // Pattern is also used as path when the type is specific
	
	// Clean the pattern in case its a full path
	if('exact' == $type){
		$pattern = str_replace(['..', './'], '', $pattern);
		$pattern = backuply_cleanpath($pattern);
	}
	
	if(!empty($_POST['key'])) {
		$key = backuply_optpost('key');
	}
	
	// Remove dot in case the user adds it
	if($type == 'extension'){
		$pattern = trim($pattern, '.');
	}
	
	if(empty($backuply['excludes'][$type])){
		$backuply['excludes'][$type] = array();
	}
	
	$this_type = &$backuply['excludes'][$type];

	if(in_array($pattern, $this_type)){
		wp_send_json(array('success' => false, 'message' => 'Exclude rule is already present'));
	}

	if(empty($key)){
		do{
			$key = wp_generate_password(6, false);
		} while(array_key_exists($key, $this_type));
	}
	$this_type[$key] = $pattern;

	update_option('backuply_excludes', $backuply['excludes']);
	
	wp_send_json(array('success' => true, 'key' => $key));
	
}

function backuply_exclude_rule_delete() {
	
	global $backuply;
	
	backuply_ajax_nonce_verify();
	
	$type = backuply_optget('type');
	$key = backuply_optget('key');
	
	if(empty($type) || empty($key)){
		wp_send_json(array('success' => false, 'message' => 'Unable to delete this Exclude rule'));
	}
	
	$this_type = &$backuply['excludes'][$type];

	// Deleting the rule
	if(empty($this_type)){
		wp_send_json(array('success' => false, 'message' => 'Rule not found'));
	}
	
	unset($this_type[$key]);
	update_option('backuply_excludes', $backuply['excludes']);
	
	wp_send_json(array('success' => true));
}


function backuply_get_jstree() {
	
	backuply_ajax_nonce_verify();
	
	$node = map_deep($_POST['nodeid'], 'sanitize_text_field');
	$scan_path = $node['id'];

	if('#' == $node['id']){
		$scan_path = WP_CONTENT_DIR;
	}
	
	// Cleaning the path to prevent directory traversal
	$scan_path = str_replace('..', '', $scan_path);
	$scan_path = backuply_cleanpath($scan_path);

	$nodes = array();

	if(empty($scan_path)){
		wp_send_json(array('success' => true, 'nodes' => $nodes));
	}

	$contents = @scandir($scan_path);
	
	if(empty($contents)){
		wp_send_json(array('success' => true, 'nodes' => $nodes));
	}
	
	foreach($contents as $con){
		if(in_array($con, array('.', '..'))){
			continue;
		}
		
		if(is_dir($scan_path . DIRECTORY_SEPARATOR . $con)){
			$nodes[] = array(
				'text' => $con,
				'children' => true,
				'id' => backuply_cleanpath($scan_path . DIRECTORY_SEPARATOR . $con),
				'type' => 'folder',
				'icon' => 'jstree-folder'
			);
		} else {
			$ext = pathinfo($con, PATHINFO_EXTENSION);
			
			$icon = 'jstree-file';
			
			if('php' == $ext){
				$icon = BACKUPLY_URL . '/assets/images/php-logo32.svg';
			}
			
			$nodes[] = array(
				'text' => $con,
				'children' => false,
				'id' => backuply_cleanpath($scan_path . DIRECTORY_SEPARATOR . $con),
				'type' => 'file',
				'icon' => $icon
			);
		}
	}

	wp_send_json(array('success' => true, 'nodes' => $nodes));
	
}

// Updates the backuply_backup_nag to current timestamp
function backuply_hide_backup_nag(){

	backuply_ajax_nonce_verify();
	
	update_option('backuply_backup_nag', time());
	
	wp_send_json(true);
}

function backuply_bcloud_trial(){
	global $backuply;

	if(!wp_verify_nonce($_POST['security'], 'backuply_trial_nonce')){
		wp_send_json_error(null, 401);
	}
	
	$remote_locs = get_option('backuply_remote_backup_locs', []);

	// Getting the bcloud ID
	foreach($remote_locs as $loc){
		if($loc['protocol'] === 'bcloud'){
			wp_send_json_error(__('Backuply Cloud is already added as a Location', 'backuply'));
		}
	}

	$url = BACKUPLY_API . '/cloud/token.php';
	$args = array(
		'body' => array(
			'url' => site_url(),
		),
		'sslverify' => false,
		'timeout' => 30
	);

	if(empty($_POST['value'])){
		wp_send_json_error(__('Please enter a license to Proceed', 'backuply'), 422);
	}
	
	$args['body']['license'] = sanitize_text_field($_POST['value']);
	$license['license'] = sanitize_text_field($_POST['value']);

	// Checking if License is inactive.
	if(!empty($backuply['license']) && !empty($backuply['license']['license']) && empty($backuply['license']['active'])){
		wp_send_json_error(__('Your license is not activated, please go to License page and activate it', 'backuply'));
	}

	// Sending Request
	$res = wp_remote_post($url, $args);
	
	if(empty($res) || is_wp_error($res)){
		wp_send_json_error(__('Something went wrong while sending request to Backuply API', 'backuply'), 500);
	}

	// Reading Body
	$body = wp_remote_retrieve_body($res);
	
	if(empty($body)){
		wp_send_json_error(__('Did not get any response from Backuply API', 'backuply'));
	}
	
	$body = json_decode($body, 1);
	
	// Updating the license if we create a trial account else the request will not return license as an array.
	if(!empty($body['license']) && is_array($body['license'])){
		$license = map_deep($body['license'], 'sanitize_text_field');
		update_option('backuply_license', $body['license']);
	}
	
	if(empty($body['success'])){
		wp_send_json_error($body['message']);
	} 
	
	if(empty($body['data']) || empty($body['data']['bcloud_key'])){
		wp_send_json_error(__('Did not got the Backuply Cloud key, contact support!', 'backuply'));
	}
	
	// Adding bacloud as a location
	$bcloud_keys = map_deep($body['data'], 'sanitize_text_field');
	$location_id = (empty($remote_locs) ? 1 : max(array_keys($remote_locs)) + 1);
	
	// Basic info for remote location
	$remote_locs[$location_id]['id'] = $location_id;
	$remote_locs[$location_id]['name'] = 'Backuply Cloud';
	$remote_locs[$location_id]['protocol'] = 'bcloud';
	$remote_locs[$location_id]['backup_loc'] = '';
	$remote_locs[$location_id]['bcloud_key'] = !empty($bcloud_keys['bcloud_key']) ? sanitize_key($bcloud_keys['bcloud_key']) : '';
	
	// Building full path, it will contain access key and other details to use.
	$remote_locs[$location_id]['full_backup_loc'] = !empty($bcloud_keys) ? 'bcloud://'.$bcloud_keys['access_key'].':'.$bcloud_keys['secret_key'].'@'.$license['license'].'/'.$bcloud_keys['region'].'/us-east-1/'.$bcloud_keys['bcloud_key'] : '';

	// Adding the bcloud key
	update_option('bcloud_key', $bcloud_keys['bcloud_key']);
	update_option('backuply_remote_backup_locs', $remote_locs);
	$backuply['bcloud_key'] = $bcloud_keys['bcloud_key'];

	/* We are updating Cron and default backup location if the Cron is not set yet,
		we are doing so to make sure we do not break users cron if its already set.*/
	if(empty($backuply['cron'])){
		$backuply['settings']['backup_location'] = $location_id;
		update_option('backuply_settings', $backuply['settings']);
		
		$backuply['cron']['backuply_cron_schedule'] = 'backuply_daily';
		$backuply['cron']['backup_dir'] = 1;
		$backuply['cron']['backup_db'] = 1;
		$backuply['cron']['backup_rotation'] = 4;
		$backuply['cron']['backup_location'] = $location_id;
		
		update_option('backuply_cron_settings', $backuply['cron']);
		
		if(!function_exists('backuply_add_auto_backup_schedule')){
			include_once BACKUPLY_DIR . '/main/bcloud-cron.php';
		}
		
		backuply_add_auto_backup_schedule($backuply['cron']['backuply_cron_schedule']); // Will create a WP Cron event
	}
	
	if(!defined('BACKUPLY_PRO')){
		update_option('bcloud_trial_time', time() + 2592000);
	}

	wp_send_json_success(__('Backuply Cloud has been integrated Successfully, It\'s ready to use now', 'backuply'));
}

// Verify if the user has verified the email for the trial account by checking if the license is active.
function backuply_verify_trial(){
	global $backuply;
	
	// Security Check
	if(!wp_verify_nonce($_POST['security'], 'backuply_trial_nonce')){
		wp_send_json_error(null, 401);
	}
	
	if(empty($backuply['license']) || empty($backuply['license']['license'])){
		wp_send_json_error(__('You could not find a license key to verify the confirmation', 'backuply'));
	}

	$resp = wp_remote_get(BACKUPLY_API.'/license.php?license='.$backuply['license']['license'], array('timeout' => 30));
	
	$json = json_decode($resp['body'], true);

	if(empty($json['license'])){
		wp_send_json_error(__('There was issue fetching License details', 'backuply'));
	}
	
	$is_active = false;
	
	if(!empty($json['active'])){
		$is_active = true;
	}

	update_option('backuply_license', $json);
	$backuply['license'] = $json;

	if(!empty($is_active)){
		wp_send_json_success(array('message' => __('Verification Completed!','backuply'), 'license' => $json['license']), 200);
	}

	wp_send_json_error(__('The license is still inactive, please check if you have checked the Confirmation email sent to your email', 'backuply'));
	
}

// Updates the settings When the user is PRO
function backuply_trial_settings(){
	global $backuply;
	
	// Security Check
	if(!wp_verify_nonce($_POST['security'], 'backuply_trial_nonce')){
		wp_send_json_error(null, 401);
	}

	$remote_locs = get_option('backuply_remote_backup_locs', []);
	
	if(empty($remote_locs)){
		wp_send_json_success();
	}
	
	// Getting the bcloud ID
	foreach($remote_locs as $loc){
		if(empty($loc['protocol']) || $loc['protocol'] != 'bcloud'){
			continue;
		}
		
		$bcloud_id = $loc['id'];
	}
	
	if(empty($bcloud_id)){
		wp_send_json_success();
	}
	
	// Updating Backup Location
	$backuply['settings']['backup_location'] = $bcloud_id;
	update_option('backuply_settings', $backuply['settings']);

	// Updating the Cron settings
	$backuply['cron']['backuply_cron_schedule'] = 'backuply_daily';
	$backuply['cron']['backup_dir'] = 1;
	$backuply['cron']['backup_db'] = 1;
	$backuply['cron']['backup_rotation'] = 4;
	$backuply['cron']['backup_location'] = $bcloud_id; 
	
	update_option('backuply_cron_settings', $backuply['cron']);
	
	if(!function_exists('backuply_add_auto_backup_schedule')){
		include_once BACKUPLY_DIR . '/main/bcloud-cron.php';
	}

	backuply_add_auto_backup_schedule($backuply['cron']['backuply_cron_schedule']); // Will create a WP Cron event

	wp_send_json_success();
	
}

// Download Backuply Cloud Files
function backuply_download_bcloud(){

	// Security Check
	if(!wp_verify_nonce($_POST['security'], 'backuply_nonce')){
		wp_send_json_error('Security Check failed!');
	}
	
	if(empty($_POST['filename'])){
		wp_send_json_error(esc_html__('File name was not given, so download can not proceed.', 'backuply'));
	}
	
	$filename = sanitize_text_field($_POST['filename']);
	$bcloud = backuply_load_remote_backup('bcloud');
	
	if(empty($bcloud)){
		wp_send_json_error(esc_html__('Backuply Cloud Not found', 'backuply'));
	}

	$bcloud_info = backuply_load_remote_backup_info('bcloud');

	if(empty($bcloud_info['full_backup_loc'])){
		wp_send_json_error(esc_html__('Backuply Cloud has not been added yet as a Backup location, if you have already added it then remove it and add it again.', 'backuply'));
	}
	
	$download_path = $bcloud_info['full_backup_loc'] . '/' . $filename;
	$download_link = $bcloud->download_direct($download_path);
	
	if(empty($download_link)){
		wp_send_json_error(esc_html__('Unable to download the file', 'backuply'));
	}
	
	wp_send_json_success(array('url' => $download_link, 'filename' => $filename));
}

// Fetches the usage on the given storage location.
function backuply_update_quota(){
	
	// Security Check
	if(!wp_verify_nonce($_POST['security'], 'backuply_nonce')){
		wp_send_json_error('Security Check failed!');
	}

	$storage_loc = sanitize_text_field($_POST['location']);
	
	if(empty($storage_loc)){
		wp_send_json_error(__('No Storage location provided', 'backuply'));
	}

	$quota = backuply_get_quota($storage_loc);
	
	if(empty($quota)){
		wp_send_json_error();
	}
	
	$info = get_option('backuply_remote_backup_locs', []);
	
	if(!empty($info)){
		foreach($info as $key => $locs){
			if($locs['protocol'] === $storage_loc){
				$info[$key]['backup_quota'] = (int) $quota['used'];
				$info[$key]['allocated_storage'] = (int) $quota['total'];
			}
		}
		
		update_option('backuply_remote_backup_locs', $info);
	}

	wp_send_json_success();
	
}
<?php
/*
* BACKUPLY
* https://backuply.com
* (c) Backuply Team
*/

if(!function_exists('add_action')){
	echo 'You are not allowed to access this page directly.';
	exit;
}

define('BACKUPLY_VERSION', '1.2.2');
define('BACKUPLY_DIR', dirname(BACKUPLY_FILE));
define('BACKUPLY_URL', plugins_url('', BACKUPLY_FILE));
define('BACKUPLY_BACKUP_DIR', str_replace('\\' , '/', WP_CONTENT_DIR).'/backuply/');
define('BACKUPLY_TIMEOUT_TIME', 300);
define('BACKUPLY_DEV', file_exists(dirname(__FILE__).'/DEV.php') ? 1 : 0);

if(BACKUPLY_DEV){
	include_once BACKUPLY_DIR.'/DEV.php';
}

// Some other constants
if(!defined('BACKUPLY_API')){
	define('BACKUPLY_API', 'https://api.backuply.com');
}

define('BACKUPLY_DOCS', 'https://backuply.com/docs/');
define('BACKUPLY_WWW_URL', 'https://backuply.com/');
define('BACKUPLY_PRO_URL', 'https://backuply.com/pricing?from=plugin');

include_once(BACKUPLY_DIR.'/functions.php');

function backuply_died() {
	//backuply_log(serialize(error_get_last()));
	
	$last_error = error_get_last();

	if(!$last_error){
		return false;
	}

	// To show the memory limit error.
	if(!empty($last_error['message'] && strpos($last_error['message'], 'Allowed memory size') !== FALSE)){
		backuply_status_log($last_error['message'], 'error');
	}
	
	// To show maximum time out error.
	if(!empty($last_error['message']) && strpos($last_error['message'], 'Maximum execution time') !== FALSE){
		backuply_status_log('The Backup Failed because the script reached Maximum Execution time while waiting for response from remote server', 'error');
		backuply_kill_process();
	}

	if(!empty($last_error['message']) && !empty($last_error['types']) && $last_error['types'] == 1){
		backuply_status_log($last_error['message'] . ' ' . $last_error['line'] . ' ' .$last_error['file'] . '', 'warning');
	}

}
register_shutdown_function('backuply_died');

// Ok so we are now ready to go
register_activation_hook(BACKUPLY_FILE, 'backuply_activation');

// Is called when the ADMIN enables the plugin
function backuply_activation(){
	global $wpdb, $error;

	update_option('backuply_version', BACKUPLY_VERSION);
	
	backuply_create_backup_folders();
	backuply_add_htaccess();
	backuply_add_web_config();
	backuply_add_index_files();
	backuply_set_config();
	backuply_set_status_key();
}

// The function that will be called when the plugin is loaded
add_action('plugins_loaded', 'backuply_load_plugin');

function backuply_load_plugin(){
	global $backuply;
	
	// Set the array
	$backuply = array();
	$backuply['settings'] = get_option('backuply_settings', []);
	$backuply['cron'] = get_option('backuply_cron_settings', []);
	$backuply['auto_backup'] = false;
	$backuply['license'] = get_option('backuply_license', []);
	$backuply['status'] = get_option('backuply_status');
	$backuply['excludes'] = get_option('backuply_excludes');
	$backuply['htaccess_error'] = true;
	$backuply['index_html_error'] = true;
	$backuply['debug_mode'] = !empty(get_option('backuply_debug')) ? true : false;
	$backuply['bcloud_key'] = get_option('bcloud_key', '');
	
	backuply_update_check();
	
	if(!defined('BACKUPLY_PRO') && !empty($backuply['bcloud_key'])){
		include_once BACKUPLY_DIR . '/main/bcloud-cron.php';
	}
	
	add_action('init', 'backuply_handle_self_call'); // To make sure all plugins are loaded.
	
	if(file_exists(BACKUPLY_BACKUP_DIR . '.htaccess')) {
		$backuply['htaccess_error'] = false;
	}

	if(file_exists(BACKUPLY_BACKUP_DIR . 'index.html')) {
		$backuply['index_html_error'] = false;
	}

	add_action('admin_menu', 'backuply_admin_menu');
	add_filter('cron_schedules', 'backuply_add_cron_interval');
	
	$showing_promo = false; // flag for single nag
	
	if(is_admin() && current_user_can('install_plugins')){
		if(isset($_REQUEST['backuply_trial_promo']) && (int)$_REQUEST['backuply_trial_promo'] == 0 ){
			if(!wp_verify_nonce(backuply_optreq('security'), 'backuply_trial_nonce')) {
				die('Security Check Failed');
			}

			update_option('backuply_hide_trial', (0 - time()));
			die('DONE');
		}

		$trial_time = get_option('backuply_hide_trial', 0);
		
		// It will show one day after install
		if(empty($trial_time)){
			$trial_time = time();
			update_option('backuply_hide_trial', $trial_time);
		}

		if($trial_time >= 0 && empty($backuply['bcloud_key']) && $trial_time < (time() - (86400))){
			$showing_promo = true;
			add_action('admin_notices', 'backuply_free_trial_promo');
		}
	}
	
	// Are we pro ?
	if(is_admin() && !defined('BACKUPLY_PRO') && current_user_can('install_plugins')) {

		// The holiday promo time
		$holiday_time = get_option('backuply_hide_holiday');
		if(empty($holiday_time) || (time() - abs($holiday_time)) > 172800){
			$holiday_time = time();
			update_option('backuply_hide_holiday', $holiday_time);
		}

		$time = date('nj');
		$days = array(1225, 1224, 11);
		if(!empty($holiday_time) && $holiday_time > 0 && isset($_GET['page']) && $_GET['page'] === 'backuply' && in_array($time, $days) && empty($showing_promo)){
			$showing_promo = true;
			add_action('admin_notices', 'backuply_holiday_promo');
		}
		
		// Are we to disable the holiday promo for 48 hours
		if(isset($_REQUEST['backuply_holiday_promo']) && (int)$_REQUEST['backuply_holiday_promo'] == 0 ){
			if(!wp_verify_nonce(backuply_optreq('security'), 'backuply_promo_nonce')) {
				die('Security Check Failed');
			}

			update_option('backuply_hide_holiday', (0 - time()));
			die('DONE');
		}

		// The promo time
		$promo_time = get_option('backuply_promo_time');
		if(empty($promo_time)){
			$promo_time = time();
			update_option('backuply_promo_time', $promo_time);
		}

		// Are we to show the backuply promo, and it will show up after 7 days of install.
		if(empty($showing_promo) && !empty($promo_time) && $promo_time > 0 && $promo_time < (time() - (7 * 86400))){
			$showing_promo = true;
			add_action('admin_notices', 'backuply_promo');
		}
		
		// Are we to disable the promo
		if(isset($_REQUEST['backuply_promo']) && (int)$_REQUEST['backuply_promo'] == 0 ){
			if(!wp_verify_nonce(backuply_optreq('security'), 'backuply_promo_nonce')) {
				die('Security Check Failed');
			}

			update_option('backuply_promo_time', (0 - time()) );
			die('DONE');
		}
		
		// The offer time
		$offer_time = get_option('backuply_offer_time', '');
		if(empty($offer_time)){
			$offer_time = time();
			update_option('backuply_offer_time', $offer_time);
		}

		// Are we to show the backuply offer, and it will show up after 7 days of install.
		if(empty($showing_promo) && !empty($offer_time) && ($offer_time > 0  || abs($offer_time + time()) > 15780000) && $offer_time  < time() - (7 * 86400) && get_option('backuply_last_restore')){
			add_action('admin_notices', 'backuply_offer_handler');
		}
		
		// Are we to disable the offer
		if(isset($_REQUEST['backuply_offer']) && (int)$_REQUEST['backuply_offer'] == 0 ){
			if(!wp_verify_nonce(backuply_optreq('security'), 'backuply_promo_nonce')) {
				die('Security Check Failed');
			}

			update_option('backuply_offer_time', (0 - time()) );
			die('DONE');
		}
	}
	
	// Backup notice for user to backup the if its been a week user took a backup
	$last_backup = get_option('backuply_last_backup');
	$backup_nag = get_option('backuply_backup_nag');
	
	// We want to show it one day after install.
	if(empty($backup_nag)){
		update_option('backuply_backup_nag', time() - 518400);
	}
	
	if(current_user_can( 'activate_plugins' ) && (time() - $last_backup) >= 604800 && (time() - $backup_nag) >= 604800){
		add_action('admin_notices', 'backuply_backup_nag');
	}
	
	// Cron for Backing Up Files/Database
	add_action('backuply_backup_cron', 'backuply_backup_execute');
	
	// Cron to check for timeout
	add_action('backuply_timeout_check', 'backuply_timeout_check');
}

// If we are doing ajax and its a backuply ajax
if(wp_doing_ajax()){
	include_once(BACKUPLY_DIR.'/main/ajax.php');
}

/**
  * Looks if Backuply just got updated.
 */
function backuply_update_check(){
	
	$sql = array();
	$current_version = get_option('backuply_version');	
	$version = (int) str_replace('.', '', $current_version);
	
	// No update required
	if($current_version == BACKUPLY_VERSION){
		return true;
	}
	
	// Is it first run ?
	if(empty($current_version)){
		backuply_activation();
		return;
	}
	
	if($version < 108){
		backuply_create_backup_folders();
		backuply_add_web_config();
		backuply_add_index_files();
	}
	
	if($version < 109){
		backuply_update_restore_key();
	}
	
	if($version < 120){
		backuply_keys_to_db();
		
		$cron_settings = get_option('backuply_cron_settings');
		
		// Updates both Backuply key and Restore key if custom cron is not enabled.
		if(!empty($cron_settings) && !empty($cron_settings['backuply_cron_schedule']) && $cron_settings['backuply_cron_schedule'] !== 'custom'){
			backuply_set_config();
		}

		backuply_set_status_key();
	}
	
	// Save the new Version
	update_option('backuply_version', BACKUPLY_VERSION);
	
}

// List of core files to backup
function backuply_core_fileindex(){
	$default_fileindex = array('index.php', 'license.txt', 'readme.html', 'wp-activate.php', 'wp-admin', 'wp-blog-header.php', 'wp-comments-post.php', 'wp-config-sample.php', 'wp-content', 'wp-cron.php', 'wp-includes', 'wp-links-opml.php', 'wp-load.php', 'wp-login.php', 'wp-mail.php', 'wp-settings.php', 'wp-signup.php', 'wp-trackback.php', 'xmlrpc.php', '.htaccess', 'wp-config.php');

	return $default_fileindex;
}

// Shows the admin menu of Backuply
function backuply_admin_menu(){
	global $backuply;

	$capability = 'activate_plugins';
	
	// Add the menu page
	add_menu_page(__('Backuply Dashboard', 'backuply'), __('Backuply', 'backuply'), $capability, 'backuply', 'backuply_settings_page_handle', BACKUPLY_URL .'/assets/images/icon.svg');
	
	// Dashboard
	add_submenu_page('backuply', __('Backuply Dashboard', 'backuply'), __('Dashboard', 'backuply'), $capability, 'backuply', 'backuply_settings_page_handle');
	
	if(defined('BACKUPLY_PRO')){
		add_submenu_page('backuply', __('License', 'backuply'), __('License', 'backuply'), $capability, 'backuply-license', 'backuply_license_page_handle');
	} else {
		add_submenu_page('backuply', __('Backuply Cloud', 'backuply'), __('Backuply Cloud', 'backuply'), $capability, 'backuply-license', 'backuply_license_page_handle');
	}

	// Its Free
	if(!defined('BACKUPLY_PRO')){

		// Go Pro link
		add_submenu_page('backuply', __('Backuply Go Pro'), __('Go Pro'), $capability, BACKUPLY_PRO_URL);

	}
}

// Backuply - Backup Page
function backuply_settings_page_handle(){
	include_once BACKUPLY_DIR . '/main/settings.php';
	backuply_page_backup();
	backuply_page_theme();
}

// Backuply - License Page
function backuply_license_page_handle(){
	include_once BACKUPLY_DIR . '/main/license.php';
	backuply_license_page();
}

// Shows a nag to the user, 1 week after last backup
function backuply_backup_nag(){
	
	$last_backup = get_option('backuply_last_backup');

	echo 
	'<div class="notice notice-error is-dismissible backuply-backup-nag">';
	
	if(!empty($last_backup)){
		$time_diff = time() - $last_backup;
		$days = floor(abs($time_diff / 86400));
		
		echo '<p>'. sprintf(esc_html__( 'It\'s been %1$s days you took a backup, would you like to take a backup with Backuply and secure your website!', 'backuply' ), $days).'&nbsp; <a href="'.menu_page_url('backuply', false).'" class="button button-primary">Backup Now</a>'.(!defined('BACKUPLY_PRO') ? ' <span style="float:right;">For automatic backup schedules please  <a href="https://backuply.com/pricing" target="_blank" class="button" style="background-color:#64b450; border-color:#64b450; color:white;">Upgrade to Pro</a></span>' : '').'</p>';
	} else{
		echo '<p>'. esc_html__( 'You haven\'t taken a backup since you activated Backuply, Take a backup and secure your website!', 'backuply' ).'&nbsp; <a href="'.menu_page_url('backuply', false).'" class="button button-primary">Backup Now</a></p>';
	}

	echo '</div>';
	
	wp_register_script('backuply_time_nag', '', array('jquery'), '', true);
	wp_enqueue_script('backuply_time_nag');
	
	wp_add_inline_script('backuply_time_nag' ,'

		jQuery(document).ready(function(){
			jQuery(".backuply-backup-nag .notice-dismiss").click(function(){
			
				jQuery.ajax({
					method : "GET",
					url : "' . admin_url('admin-ajax.php') .'?action=backuply_hide_backup_nag&security=' . wp_create_nonce('backuply_nonce'). '",
					success : function(res){
						console.log(res);
					}
				});
			});
		});'
	);

}

// Cron Schedules for WordPress cron
function backuply_add_cron_interval($schedules){
	// 30 Min
	$schedules['backuply_thirty_min'] = array(
		'interval' => 1800,
		'display'  => esc_html__( 'Every 30 Minutes' )
	);
	
	$schedules['backuply_one_hour'] = array(
		'interval' => 3600,
		'display'  => esc_html__( 'Every One Hour' )
	);

	$schedules['backuply_two_hours'] = array(
		'interval' => 7200,
		'display'  => esc_html__( 'Every Two Hours' )
	);
	
	$schedules['backuply_daily'] = array(
		'interval' => 86400,
		'display'  => esc_html__( 'Once a day' )
	);

	$schedules['backuply_weekly'] = array(
		'interval' => 604800,
		'display' => esc_html__('Once a Week')
	);
	
	$schedules['backuply_monthly'] = array(
		'interval' => 2635200,
		'display' => esc_html__('Once a month')
	);
	
	return $schedules;
}

// Initiates the backup
function backuply_backup_execute(){
	global $wpdb, $backuply, $data;
	
	// Updates the $backuply['status'] var
	$is_active = backuply_active();
	
	if(empty($backuply['status'])){
		return;
	}

	// Update the last active time
	$backuply['status']['last_update'] = time();
	update_option('backuply_status', $backuply['status']);
	
	// Informaton regarding remote location
	$remote_location = '';

	if(!empty($backuply['status']['backup_location'])){
		$backuply_remote_backup_locs = get_option('backuply_remote_backup_locs');
		$backup_location_id = $backuply['status']['backup_location'];
		$remote_location = $backuply_remote_backup_locs[$backup_location_id];
	}

	include(BACKUPLY_DIR.'/backup_ins.php');
	
}

function backuply_handle_self_call(){
	// CURL call for bacukup when its incomplete
	if(isset($_GET['action'])  && ($_GET['action'] == 'backuply_curl_backup' || $_GET['action'] == 'backuply_curl_upload')) {

		if(!wp_verify_nonce(backuply_optreq('security'), 'backuply_nonce')){
			backuply_status_log('Security Check Failed', 'error');
			die();
		}

		backuply_backup_execute();
		wp_send_json(array('success' => true));
	}
}

// Show the promo
function backuply_promo(){
	include_once(BACKUPLY_DIR.'/main/promo.php');
	
	backuply_base_promo();
}

function backuply_holiday_promo(){
	include_once(BACKUPLY_DIR.'/main/promo.php');
	
	backuply_holiday_offers();
}

function backuply_free_trial_promo(){
	if(!function_exists('backuply_free_trial')){
		include_once(BACKUPLY_DIR.'/main/promo.php');
	}
	
	backuply_promo_scripts();
	backuply_free_trial();
}

function backuply_offer_handler(){
	if(!function_exists('backuply_regular_offer')){
		include_once(BACKUPLY_DIR.'/main/promo.php');
	}
	
	backuply_regular_offer();
}


// Sorry to see you going
register_uninstall_hook(BACKUPLY_FILE, 'backuply_deactivation');

function backuply_deactivation(){	
	delete_option('backuply_version');
	delete_option('backuply_cron_schedules');
	delete_option('backuply_cron_settings');
	delete_option('backuply_remote_backup_locs');
	delete_option('backuply_notify_email_address');
	delete_option('backuply_settings');
	delete_option('backuply_license');
	delete_option('backuply_hide_trial');
	delete_option('backuply_promo_time');
	delete_option('backuply_backup_stopped');
	delete_option('backuply_last_restore');
	delete_option('backuply_last_backup');
	delete_option('backuply_hide_holiday');
	delete_option('backuply_excludes');
	delete_option('backuply_black_friday');
	delete_option('backuply_debug');
	delete_option('external_updates-backuply-pro');
	delete_option('backuply_offer_time');	
	delete_option('backuply_backup_nag');
	delete_option('backuply_config_keys');
}
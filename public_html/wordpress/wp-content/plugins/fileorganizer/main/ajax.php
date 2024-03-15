<?php
/*
* FILEORGANIZER
* https://fileorganizer.net/
* (c) FileOrganizer Team
*/

if(!defined('FILEORGANIZER_VERSION')){
	die('Hacking Attempt!');
}

add_action('wp_ajax_fileorganizer_file_folder_manager', 'fileorganizer_ajax_handler');
function fileorganizer_ajax_handler(){
	global $fileorganizer;
	
	// Check nonce
	check_admin_referer( 'fileorganizer_ajax' , 'fileorganizer_nonce' );
	
	// Check capability
	$capability = fileorganizer_get_capability();
	
	if(!current_user_can($capability)){
		return;
	}

	// Load saved settings
	$url = site_url();
	$path = !empty($fileorganizer->options['root_path']) ? fileorganizer_cleanpath($fileorganizer->options['root_path']) : ABSPATH;
	
	if(!defined('FILEORGANIZER_PRO') || empty($fileorganizer->options['disable_path_restriction'])){
		$path = fileorganizer_validate_path($path) ? $path : ABSPATH;
	}

	if(is_multisite()){
		$url = network_home_url();
	}

	// Set restrictions
	$restrictions = [
		array(
			'pattern' => '/.tmb/',
			'read' => false,
			'write' => false,
			'hidden' => true,
			'locked' => false,
		),
		array(
			'pattern' => '/.quarantine/',
			'read' => false,
			'write' => false,
			'hidden' => true,
			'locked' => false,
		)
	];
	
	// Hide .htaccess?
	if(!empty($fileorganizer->options['hide_htaccess'])) {
		$restrictions[] = array(
			'pattern' => '/.htaccess/',
			'read' => false,
			'write' => false,
			'hidden' => true,
			'locked' => false
		);
	}

	$disable_commands = array('help', 'preference', 'hide', 'netmount');	

	$config = array();

	// Configure elfinder
	$config[0] = array(
		'driver' => 'LocalFileSystem',
		'path' => $path,
		'URL' => $url,
		'winHashFix' => DIRECTORY_SEPARATOR !== '/',
		'accessControl' => 'access',
		'acceptedName' => 'validName',
		'uploadMaxSize' => 0,
		'disabled' => $disable_commands,
		'attributes' => $restrictions
	);

	// Is trash enabled?
	if (!empty($fileorganizer->options['enable_trash'])) {
		
		$uploads_dir = wp_upload_dir();
		$trash_dir = fileorganizer_cleanpath($uploads_dir['basedir'].'/fileorganizer/.trash');
	
		if(!file_exists($trash_dir)){
			mkdir($trash_dir.'/.tmb', 0755, true);
		}
	
		// Configure trash
		$config[1] = array(
			'id' => '1',
			'driver' => 'Trash',
			'path' => $trash_dir,
			'tmbURL' => $uploads_dir['baseurl'].'/fileorganizer/.trash/.tmb/',
			'winHashFix' => DIRECTORY_SEPARATOR !== '/',
			'uploadDeny' => array(''),
			'uploadAllow' => array(''),
			'uploadOrder' => array('deny', 'allow'),
			'accessControl' => 'access',
			'disabled' => $disable_commands,
			'attributes' => $restrictions,
		);
		$config[0]['trashHash'] = 't1_Lw';
	}

	$config = apply_filters('fileorganizer_manager_config', $config);

	$el_config = array(
		'locale' => 'zh_CN',
		'debug' => false,
		'roots' => $config
	);

	// Load autoloader
	require FILEORGANIZER_DIR.'/manager/php/autoload.php';

	// Load FTP driver?
	if(defined('FILEORGANIZER_PRO') && !empty($fileorganizer->options['enable_ftp'])){	
		elFinder::$netDrivers['ftp'] = 'FTP';
	}
	
	// run elFinder
	$connector = new elFinderConnector(new elFinder($el_config));
	$connector->run();
}

// Change fileorganizer theme
add_action('wp_ajax_fileorganizer_switch_theme', 'fileorganizer_switch_theme');
function fileorganizer_switch_theme(){
	
	//Check nonce
	check_admin_referer( 'fileorganizer_ajax' , 'fileorganizer_nonce' );

	if(!current_user_can('manage_options')){
		wp_send_json(array( 'error' => 'Permision Denide!' ), 400);
	}

	$theme = fileorganizer_optpost('theme');

	$options = get_option('fileorganizer_options', array());
	$options['theme'] = $theme;
    update_option('fileorganizer_options', $options);
	
	$theme_path = !empty($theme) ? '/themes/'.$theme : '';
	
	// Return requested theme path
	$path = FILEORGANIZER_URL.'/manager'.$theme_path.'/css/theme.css';

	$response = array(
		'success' => true,
		'stylesheet' => $path
	);

	wp_send_json($response, 200);
}

add_action('wp_ajax_fileorganizer_hide_promo', 'fileorganizer_hide_promo');
function fileorganizer_hide_promo(){
	
	//Check nonce
	check_admin_referer( 'fileorganizer_promo_nonce' , 'security' );
	
	// Save value in minus
	update_option('fileorganizer_promo_time', (0 - time()));
	die('DONE');
}
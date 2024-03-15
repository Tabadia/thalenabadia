<?php

/*
* BACKUPLY
* https://backuply.com
* (c) Backuply Team
*/


header('Content-Type: application/json; charset=utf-8');

if(!_verify_self()){
	echo json_encode(array('success' => false, 'progress_log' => 'Security Check Failed!|error'));
	die();
}

_get_status($_REQUEST['last_status']);

// Returns the Security key
function _get_config(){

	$config_file = dirname(__FILE__, 3) . '/backuply/status_key.php';
	
	if(!file_exists($config_file) || 0 == filesize($config_file)) {
		return false;
	}

	$fp = @fopen($config_file, 'r');
	@fseek($fp, 16);

	$content = @fread($fp, filesize($config_file));
	@fclose($fp);

	$status_key = $content;

	return $status_key;
}

// Verifies the backuply key
function _verify_self(){
	
	if(empty($_REQUEST['status_key'])) {
		return false;
	}
	
	$status_lock = dirname(__FILE__, 3). '/backuply/status.lock';
	
	if(!file_exists($status_lock) || (time() - filemtime($status_lock) > 1800)){
		return false;
	}

	$status_key = _get_config();
	
	if(!$status_key) {
		return false;
	}

	if(urldecode($_REQUEST['status_key']) == $status_key) {
		return true;
	}

	return false;
}

// Returns array of logs
function _get_status($last_log = 0){
	$log_file = dirname(__FILE__, 3). '/backuply/backuply_log.php';
	$logs = [];
	$last_log = (int) $last_log;
	
	if(!file_exists($log_file)){
		$logs[] = 'Something went wrong!|error';
		echo json_encode(array('success' => false, 'progress_log' => $logs));
		die();
	}
	
	$fh = fopen($log_file, 'r');
	
	$seek_to = $last_log + 16; // 16 for php exi
	
	@fseek($fh, $seek_to);
	
	$lines = fread($fh, fstat($fh)['size']);
	fclose($fh);
	$fh = null;
	
	echo json_encode(array('success' => true, 'progress_log' => $lines));
	die();
}
